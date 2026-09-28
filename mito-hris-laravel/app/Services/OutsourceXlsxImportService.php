<?php

namespace App\Services;

use App\DTOs\OutsourceEmployeeData;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Support\OutsourceEmployeeAttributeMap;
use App\Support\OutsourceEmployeeFilter;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

/**
 * Reads the HR outsource workbook ("RAW DATA", columns A–V) into OutsourceEmployeeData.
 * Formula cells (VLOOKUP) are read from Excel's cached result; nothing is recalculated.
 */
class OutsourceXlsxImportService
{
    /** Column letter => DTO property (A–V of the source workbook). */
    public const COLUMNS = [
        'A' => 'outsourceId',
        'B' => 'fullName',
        'C' => 'citizenIdAddress',
        'D' => 'birthDate',
        'E' => 'birthPlace',
        'F' => 'lastEducation',
        'G' => 'whatsappNumber',
        'H' => 'email',
        'I' => 'jobTitle',
        'J' => 'workLocation',
        'K' => 'workCity',
        'L' => 'bankAccount',
        'M' => 'mitoJoinDate',
        'N' => 'contractStartDate',
        'O' => 'contractEndDate',
        'P' => 'costCenter',
        'Q' => 'entity',
        'R' => 'payrollScheme',
        'S' => 'umkAmount',
        'T' => 'basicSalary',
        'U' => 'incentiveAmount',
        'V' => 'remarks',
    ];

    private const DATE_FIELDS = ['birthDate', 'mitoJoinDate', 'contractStartDate', 'contractEndDate'];

    private const DIGIT_FIELDS = ['whatsappNumber', 'bankAccount'];

    public function __construct(
        protected OutsourceEmployeeRepositoryInterface $repository
    ) {}

    /**
     * Reads the workbook and upserts into the active outsource store. Shared by
     * `mito:outsource:import-xlsx` and the HR dashboard import so both behave identically:
     * match by Outsource ID (or WA/email when the row has no ID), blank cells never
     * overwrite existing values, and rows without an ID get the next sequence number.
     *
     * @return array{read: int, created: int, updated: int, warnings: list<string>, errors: list<string>}
     */
    public function import(string $path, string $sheetName, string $vendor, bool $dryRun = false, ?string $createdBy = null): array
    {
        $result = $this->read($path, $sheetName, $vendor);
        $warnings = $result['warnings'];
        $existing = $this->repository->getAll()->keyBy(fn (OutsourceEmployeeData $e) => strtoupper((string) $e->outsourceId));
        $created = 0;
        $updated = 0;
        $errors = [];
        $seenContacts = [];

        // Rows without an ID take the next sequence number, so they must come after every explicit ID.
        [$withId, $withoutId] = collect($result['rows'])->partition(fn (OutsourceEmployeeData $row) => $row->outsourceId !== null);

        foreach ($withId->concat($withoutId) as $row) {
            $label = $row->outsourceId ?? $row->fullName;

            foreach (['wa' => OutsourceEmployeeFilter::phoneKey($row->whatsappNumber), 'email' => (string) $row->email] as $kind => $key) {
                if ($key === '') {
                    continue;
                }
                if (isset($seenContacts[$kind][$key])) {
                    $warnings[] = "{$label}: {$kind} sama dengan {$seenContacts[$kind][$key]}.";
                }
                $seenContacts[$kind][$key] = $label;
            }

            $current = $row->outsourceId !== null
                ? $existing->get($row->outsourceId)
                : OutsourceEmployeeFilter::findByContact($existing->values(), $row->whatsappNumber, $row->email);

            if ($dryRun) {
                $current ? $updated++ : $created++;
                continue;
            }

            try {
                if ($current) {
                    $attributes = array_filter($row->toArray(), fn ($value) => $value !== null);
                    unset($attributes['createdBy']);
                    $this->repository->update((string) $current->outsourceId, $attributes);
                    $updated++;
                } else {
                    if ($createdBy !== null) {
                        $row->createdBy = $createdBy;
                    }
                    $saved = $this->repository->create($row);
                    $existing->put(strtoupper((string) $saved->outsourceId), $saved);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "{$label}: {$e->getMessage()}";
            }
        }

        return [
            'read' => count($result['rows']),
            'created' => $created,
            'updated' => $updated,
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{rows: list<OutsourceEmployeeData>, warnings: list<string>}
     */
    public function read(string $path, string $sheetName, string $vendor): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("File tidak ditemukan: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        if (!in_array($sheetName, $reader->listWorksheetNames($path), true)) {
            throw new RuntimeException("Sheet '{$sheetName}' tidak ditemukan di file.");
        }
        $reader->setLoadSheetsOnly([$sheetName]);
        $sheet = $reader->load($path)->getSheetByName($sheetName);
        if ($sheet === null) {
            throw new RuntimeException("Sheet '{$sheetName}' tidak ditemukan di file.");
        }

        $rows = [];
        $warnings = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($r = 2; $r <= $highestRow; $r++) {
            $values = [];
            foreach (self::COLUMNS as $column => $property) {
                $raw = $this->cellValue($sheet->getCell($column . $r));
                $values[$property] = $this->convert($property, $raw, "baris {$r} kolom {$column}", $warnings);
            }

            if ($values['outsourceId'] === null && $values['fullName'] === null) {
                continue;
            }
            if ($values['fullName'] === null) {
                $warnings[] = "baris {$r}: nama kosong, dilewati.";
                continue;
            }

            if ($values['outsourceId'] !== null) {
                $values['outsourceId'] = strtoupper($values['outsourceId']);
                if (!preg_match('/^[A-Z]+\d+$/', $values['outsourceId'])) {
                    $warnings[] = "baris {$r}: ID '{$values['outsourceId']}' tidak valid, akan dibuat otomatis.";
                    $values['outsourceId'] = null;
                }
            }
            $values['vendor'] = $vendor;
            $values['createdBy'] = 'Import XLSX';

            $rows[] = new OutsourceEmployeeData(...$values);
        }

        return ['rows' => $rows, 'warnings' => $warnings];
    }

    private function cellValue(Cell $cell): mixed
    {
        $value = $cell->getValue();
        if (is_string($value) && str_starts_with($value, '=')) {
            $value = $cell->getOldCalculatedValue();
        }
        if (is_string($value) && preg_match('/^#(N\/A|REF!|VALUE!|DIV\/0!|NAME\?|NUM!|NULL!)$/i', trim($value))) {
            return null;
        }

        return $value;
    }

    /**
     * @param  list<string>  $warnings
     */
    private function convert(string $property, mixed $value, string $where, array &$warnings): string|float|null
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if (in_array($property, self::DATE_FIELDS, true)) {
            $date = $this->toDate($value);
            if ($date === null) {
                $warnings[] = "{$where}: tanggal tidak valid, dikosongkan.";
            }

            return $date;
        }

        if (in_array($property, self::DIGIT_FIELDS, true)) {
            $digits = is_float($value) ? sprintf('%.0f', $value) : preg_replace('/\D+/', '', (string) $value);
            if ($digits === '' || $digits === null) {
                return null;
            }

            return $property === 'whatsappNumber' ? RecruitmentService::normalizePhone($digits) : $digits;
        }

        if ($property === 'email') {
            return strtolower(trim((string) $value));
        }

        if (is_float($value) && $property === 'outsourceId') {
            $value = sprintf('%.0f', $value);
        }

        return OutsourceEmployeeAttributeMap::normalize($property, $value);
    }

    private function toDate(mixed $value): ?string
    {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            $serial = (float) $value;
            if ($serial < 1) {
                return null;
            }

            return Carbon::instance(ExcelDate::excelToDateTimeObject($serial))->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value, 'Asia/Jakarta')->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
