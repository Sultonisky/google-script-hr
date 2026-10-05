<?php

namespace App\Services;

use App\DTOs\OutsourceEmployeeData;
use App\Models\OutsourceIncentive;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Support\OutsourceEmployeeAttributeMap;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use RuntimeException;

class OutsourceIncentiveImportService
{
    private const HEADERS = ['Outsource ID', 'Nama', 'UMK', 'Incentive'];

    public function __construct(
        protected OutsourceEmployeeRepositoryInterface $outsourceRepo
    ) {}

    public function template(): Spreadsheet
    {
        $employees = $this->outsourceRepo->getAll()
            ->sortBy(fn (OutsourceEmployeeData $employee) => strtolower(trim($employee->fullName ?? '')))
            ->values();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Incentive');

        foreach (self::HEADERS as $index => $header) {
            $column = chr(ord('A') + $index);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF005BAC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->freezePane('A2');

        foreach ($employees as $index => $employee) {
            $row = $index + 2;
            $sheet->getCell('A' . $row)->setValueExplicit((string) $employee->outsourceId, DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $row, $employee->fullName ?? '');
            if ($employee->umkAmount !== null) {
                $sheet->setCellValue('C' . $row, $employee->umkAmount);
            }
        }
        $sheet->getStyle('C2:D' . max(2, $employees->count() + 1))->getNumberFormat()->setFormatCode('#,##0');
        $spreadsheet->getProperties()->setCreator('MITO HRIS')->setTitle('Template Outsource Incentive');

        return $spreadsheet;
    }

    /**
     * @return array{read: int, created: int, updated: int, unchanged: int, skipped: int, rows: list<array<string, mixed>>, warnings: list<string>, errors: list<string>}
     */
    public function import(string $path, string $period, bool $dryRun = false, ?string $importedBy = null, ?string $sourceFile = null): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getSheet(0);
        if (strtolower(trim((string) $sheet->getCell('A1')->getValue())) !== strtolower(self::HEADERS[0])) {
            throw new RuntimeException("Format file tidak sesuai template: kolom A1 harus berisi '" . self::HEADERS[0] . "'.");
        }

        $readRows = [];
        $errors = [];
        $warnings = [];
        $seen = [];
        $blank = 0;
        for ($line = 2; $line <= $sheet->getHighestDataRow(); $line++) {
            $rawId = $sheet->getCell('A' . $line)->getValue();
            $rawName = $sheet->getCell('B' . $line)->getValue();
            $rawAmount = $sheet->getCell('D' . $line)->getValue();
            if (is_string($rawAmount) && str_starts_with($rawAmount, '=')) {
                $rawAmount = $sheet->getCell('D' . $line)->getOldCalculatedValue();
            }
            if ($rawAmount === null || (is_string($rawAmount) && trim($rawAmount) === '')) {
                if ($rawId !== null && trim((string) $rawId) !== '') {
                    $blank++;
                }
                continue;
            }

            $id = strtoupper(trim(is_float($rawId) ? sprintf('%.0f', $rawId) : (string) $rawId));
            if ($id === '') {
                $errors[] = "baris {$line}: Outsource ID kosong.";
                continue;
            }
            if (isset($seen[$id])) {
                $errors[] = "baris {$line}: Outsource ID {$id} duplikat dengan baris {$seen[$id]}.";
                continue;
            }
            $seen[$id] = $line;

            $amount = OutsourceEmployeeAttributeMap::parseAmount($rawAmount);
            if ($amount === null) {
                $errors[] = "baris {$line} ({$id}): Incentive bukan angka.";
            } elseif ($amount < 0) {
                $errors[] = "baris {$line} ({$id}): Incentive tidak boleh negatif.";
            } else {
                $readRows[] = [
                    'line' => $line,
                    'outsource_id' => $id,
                    'full_name' => $rawName === null ? null : trim((string) $rawName),
                    'incentive_amount' => $amount,
                ];
            }
        }
        if ($blank > 0) {
            $warnings[] = "{$blank} baris tanpa nominal incentive diabaikan.";
        }

        $master = $this->outsourceRepo->getAll()->keyBy(fn (OutsourceEmployeeData $employee) => strtoupper((string) $employee->outsourceId));
        $existing = OutsourceIncentive::query()->where('period', $period)->get()->keyBy(fn (OutsourceIncentive $incentive) => strtoupper($incentive->outsource_id));
        $rows = [];
        $skipped = 0;
        foreach ($readRows as $row) {
            $employee = $master->get($row['outsource_id']);
            if (!$employee) {
                $warnings[] = "baris {$row['line']}: Outsource ID {$row['outsource_id']} tidak ditemukan di master data outsource, dilewati.";
                $skipped++;
                continue;
            }
            $current = $existing->get($row['outsource_id']);
            $rows[] = [
                'outsource_id' => (string) $employee->outsourceId,
                'full_name' => $employee->fullName ?? $row['full_name'],
                'vendor' => $employee->vendor,
                'umk_amount' => $employee->umkAmount,
                'incentive_amount' => $row['incentive_amount'],
                'status' => !$current ? 'created' : (
                    round((float) $current->incentive_amount, 2) === round($row['incentive_amount'], 2)
                    && (is_null($current->umk_amount) && is_null($employee->umkAmount)
                        || round((float) $current->umk_amount, 2) === round((float) $employee->umkAmount, 2))
                        ? 'unchanged'
                        : 'updated'
                ),
            ];
        }

        $count = fn (string $status) => count(array_filter($rows, fn (array $row) => $row['status'] === $status));
        $result = [
            'read' => count($readRows),
            'created' => $count('created'),
            'updated' => $count('updated'),
            'unchanged' => $count('unchanged'),
            'skipped' => $skipped,
            'rows' => $rows,
            'warnings' => $warnings,
            'errors' => $errors,
        ];
        if ($dryRun || $errors !== []) {
            return $result;
        }

        DB::transaction(function () use ($rows, $period, $importedBy, $sourceFile): void {
            foreach ($rows as $row) {
                if ($row['status'] === 'unchanged') {
                    continue;
                }
                OutsourceIncentive::query()->updateOrCreate(
                    ['period' => $period, 'outsource_id' => $row['outsource_id']],
                    array_diff_key($row, ['status' => true, 'outsource_id' => true]) + [
                        'source_file' => $sourceFile,
                        'imported_by' => $importedBy,
                    ]
                );
            }
        });

        return $result;
    }
}
