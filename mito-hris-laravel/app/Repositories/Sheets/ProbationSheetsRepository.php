<?php

namespace App\Repositories\Sheets;

use App\Repositories\Contracts\ProbationRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use RuntimeException;

class ProbationSheetsRepository implements ProbationRepositoryInterface
{
    public function __construct(
        protected GoogleSheetsService $sheets
    ) {}

    protected function sheetName(): string
    {
        return config('google.sheets.candidates_probation', 'kandidat_probation');
    }

    /** @return list<string> */
    protected function canonicalHeaders(): array
    {
        $headers = config('hris.schemas.kandidat_probation', []);
        if (!is_array($headers) || $headers === [] || count($headers) !== count(array_unique($headers))) {
            throw new RuntimeException('Schema kandidat_probation tidak valid atau memiliki header duplikat.');
        }

        return array_values($headers);
    }

    public function getAllRows(): array
    {
        return $this->sheets->getRowsAsAssoc($this->sheetName());
    }

    public function appendEvalRow(array $data): void
    {
        $sheetName = $this->sheetName();
        $canonicalHeaders = $this->canonicalHeaders();

        $headerRow = $this->sheets->getRange($sheetName, '1:1', false)[0] ?? [];
        if (empty($headerRow) || empty(array_filter($headerRow))) {
            $this->sheets->ensureSheetHeaders($sheetName, $canonicalHeaders);
            $headers = $canonicalHeaders;
        } else {
            $existingHeaders = array_map('trim', $headerRow);
            if ($existingHeaders !== $canonicalHeaders) {
                throw new RuntimeException(
                    'Header kandidat_probation tidak sesuai schema canonical. Migrasikan header Sheet sebelum menulis evaluation.'
                );
            }
            $headers = $existingHeaders;
        }

        $row = [];
        foreach ($headers as $h) {
            $row[] = $data[$h] ?? '';
        }
        if (count($row) !== count($headers)) {
            throw new RuntimeException('Jumlah nilai row kandidat_probation tidak sama dengan jumlah header sheet.');
        }

        $this->sheets->appendRow($sheetName, $row);
        $this->invalidateCache();
    }

    public function invalidateCache(): void
    {
        $this->sheets->clearCache($this->sheetName());
    }
}
