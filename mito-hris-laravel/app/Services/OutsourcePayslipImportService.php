<?php

namespace App\Services;

use App\DTOs\OutsourceEmployeeData;
use App\Models\OutsourcePayslip;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Support\OutsourceEmployeeAttributeMap;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use RuntimeException;

/**
 * Import payslip outsource dari Excel (kolom A–G, sheet pertama) per periode.
 * Data diterima apa adanya (THP tidak dihitung ulang); hanya baris dengan
 * Outsource ID yang terdaftar di master outsource yang disimpan.
 * Master outsource hanya dibaca: nominal di file (termasuk Gaji Pokok) tidak
 * boleh menimpa data master seperti basic_salary, UMK, atau insentif.
 */
class OutsourcePayslipImportService
{
    /** Column letter => [field, header] (sheet pertama, header di baris 1). */
    public const COLUMNS = [
        'A' => ['outsource_id', 'Outsource ID'],
        'B' => ['full_name', 'Nama'],
        'C' => ['hke', 'Total HKE'],
        'D' => ['basic_salary', 'Gaji Pokok'],
        'E' => ['bpjs_kesehatan_deduction', 'Potongan BPJS Kesehatan'],
        'F' => ['loan_deduction', 'Potongan Pinjaman'],
        'G' => ['take_home_pay', 'THP'],
    ];

    public const AMOUNT_FIELDS = ['hke', 'basic_salary', 'bpjs_kesehatan_deduction', 'loan_deduction', 'take_home_pay'];

    private const REQUIRED_FIELDS = ['hke' => true, 'basic_salary' => true, 'take_home_pay' => true];

    private const MAX_HKE = 31;

    public function __construct(
        protected OutsourceEmployeeRepositoryInterface $outsourceRepo
    ) {}

    /**
     * Baris yang error membatalkan seluruh import (tidak ada yang disimpan).
     * Outsource ID yang tidak ada di master dilewati dengan peringatan. Pada import
     * ulang periode yang sama, payslip dengan nominal identik tidak ditulis ulang
     * (status unchanged); hanya yang nominalnya berbeda yang diperbarui.
     *
     * @return array{read: int, created: int, updated: int, unchanged: int, skipped: int, rows: list<array<string, mixed>>, warnings: list<string>, errors: list<string>}
     */
    public function import(
        string $path,
        string $period,
        bool $dryRun = false,
        ?string $importedBy = null,
        ?string $sourceFile = null
    ): array {
        $result = $this->read($path);
        $warnings = $result['warnings'];
        $errors = $result['errors'];

        $master = $this->outsourceRepo->getAll()->keyBy(fn (OutsourceEmployeeData $e) => strtoupper((string) $e->outsourceId));
        $existing = OutsourcePayslip::query()
            ->where('period', $period)
            ->get()
            ->keyBy(fn (OutsourcePayslip $p) => strtoupper((string) $p->outsource_id));

        $rows = [];
        $skipped = 0;
        foreach ($result['rows'] as $row) {
            $employee = $master->get($row['outsource_id']);
            if (!$employee) {
                $warnings[] = "baris {$row['line']}: Outsource ID {$row['outsource_id']} tidak ditemukan di master data outsource, dilewati.";
                $skipped++;
                continue;
            }

            $rows[] = [
                'outsource_id' => (string) $employee->outsourceId,
                'full_name' => $employee->fullName ?? $row['full_name'],
                'vendor' => $employee->vendor,
                'hke' => $row['hke'],
                'basic_salary' => $row['basic_salary'],
                'bpjs_kesehatan_deduction' => $row['bpjs_kesehatan_deduction'],
                'loan_deduction' => $row['loan_deduction'],
                'take_home_pay' => $row['take_home_pay'],
                'status' => $this->statusFor($existing->get($row['outsource_id']), $row),
            ];
        }

        $count = fn (string $status) => count(array_filter($rows, fn (array $r) => $r['status'] === $status));
        $summary = [
            'read' => count($result['rows']),
            'created' => $count('created'),
            'updated' => $count('updated'),
            'unchanged' => $count('unchanged'),
            'skipped' => $skipped,
            'rows' => $rows,
            'warnings' => $warnings,
            'errors' => $errors,
        ];

        if ($dryRun || $errors !== []) {
            return $summary;
        }

        DB::transaction(function () use ($rows, $period, $importedBy, $sourceFile) {
            foreach ($rows as $row) {
                if ($row['status'] === 'unchanged') {
                    continue;
                }
                OutsourcePayslip::query()->updateOrCreate(
                    ['period' => $period, 'outsource_id' => $row['outsource_id']],
                    array_diff_key($row, ['status' => true, 'outsource_id' => true]) + [
                        'source_file' => $sourceFile,
                        'imported_by' => $importedBy,
                    ]
                );
            }
        });

        return $summary;
    }

    /**
     * @return array{rows: list<array<string, mixed>>, warnings: list<string>, errors: list<string>}
     */
    public function read(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("File tidak ditemukan: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getSheet(0);

        $header = strtolower(trim((string) $sheet->getCell('A1')->getValue()));
        if ($header !== strtolower(self::COLUMNS['A'][1])) {
            throw new RuntimeException("Format file tidak sesuai template: kolom A1 harus berisi '" . self::COLUMNS['A'][1] . "'.");
        }

        $rows = [];
        $warnings = [];
        $errors = [];
        $seen = [];
        $blank = 0;
        $highestRow = $sheet->getHighestDataRow();

        for ($r = 2; $r <= $highestRow; $r++) {
            $values = [];
            foreach (self::COLUMNS as $column => [$field]) {
                $values[$field] = $this->cellValue($sheet->getCell($column . $r));
            }

            $id = $this->normalizeId($values['outsource_id']);
            $hasAmount = false;
            foreach (self::AMOUNT_FIELDS as $field) {
                $hasAmount = $hasAmount || !$this->isBlank($values[$field]);
            }

            // Baris template tanpa nominal = outsource yang tidak digaji periode ini.
            if (!$hasAmount) {
                if ($id !== null) {
                    $blank++;
                }
                continue;
            }

            if ($id === null) {
                $errors[] = "baris {$r}: Outsource ID kosong.";
                continue;
            }
            if (isset($seen[$id])) {
                $errors[] = "baris {$r}: Outsource ID {$id} duplikat dengan baris {$seen[$id]}.";
                continue;
            }
            $seen[$id] = $r;

            $row = ['line' => $r, 'outsource_id' => $id, 'full_name' => $this->isBlank($values['full_name']) ? null : trim((string) $values['full_name'])];
            $rowErrors = [];
            foreach (self::AMOUNT_FIELDS as $field) {
                $label = self::COLUMNS[$this->columnOf($field)][1];
                $amount = $this->toAmount($values[$field]);

                if ($amount === null) {
                    if (!$this->isBlank($values[$field])) {
                        $rowErrors[] = "baris {$r} ({$id}): {$label} bukan angka.";
                    } elseif (isset(self::REQUIRED_FIELDS[$field])) {
                        $rowErrors[] = "baris {$r} ({$id}): {$label} wajib diisi.";
                    }
                    $row[$field] = 0.0;
                    continue;
                }
                if ($amount < 0) {
                    $rowErrors[] = "baris {$r} ({$id}): {$label} tidak boleh negatif.";
                } elseif ($field === 'hke' && $amount > self::MAX_HKE) {
                    $rowErrors[] = "baris {$r} ({$id}): Total HKE maksimal " . self::MAX_HKE . ' hari.';
                }
                $row[$field] = $amount;
            }

            if ($rowErrors !== []) {
                array_push($errors, ...$rowErrors);
                continue;
            }
            $rows[] = $row;
        }

        if ($blank > 0) {
            $warnings[] = "{$blank} baris tanpa nominal diabaikan (tidak masuk payslip periode ini).";
        }

        return ['rows' => $rows, 'warnings' => $warnings, 'errors' => $errors];
    }

    /**
     * Template berisi Outsource ID dan nama dari master outsource; kolom nominal dikosongkan.
     */
    public function template(?string $vendor = null): Spreadsheet
    {
        $outsources = $this->outsourceRepo->getAll($vendor ? ['vendor' => $vendor] : [])
            ->sortBy(fn (OutsourceEmployeeData $e) => strtolower(trim($e->fullName ?? '')))
            ->values();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payslip');

        foreach (self::COLUMNS as $column => [, $header]) {
            $sheet->getCell($column . '1')->setValue($header);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF005BAC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->freezePane('A2');

        $rowIndex = 2;
        foreach ($outsources as $os) {
            $sheet->getCell('A' . $rowIndex)->setValueExplicit((string) $os->outsourceId, DataType::TYPE_STRING);
            $sheet->getCell('B' . $rowIndex)->setValue($os->fullName ?? '');
            $rowIndex++;
        }
        $sheet->getStyle('D2:G' . max(2, $rowIndex - 1))->getNumberFormat()->setFormatCode('#,##0');

        $spreadsheet->getProperties()
            ->setCreator('MITO HRIS')
            ->setTitle('Template Payslip Outsource');

        return $spreadsheet;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function statusFor(?OutsourcePayslip $current, array $row): string
    {
        if (!$current) {
            return 'created';
        }
        foreach (self::AMOUNT_FIELDS as $field) {
            if (round((float) $current->{$field}, 2) !== round((float) $row[$field], 2)) {
                return 'updated';
            }
        }

        return 'unchanged';
    }

    private function columnOf(string $field): string
    {
        foreach (self::COLUMNS as $column => [$name]) {
            if ($name === $field) {
                return $column;
            }
        }

        return 'A';
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

    private function isBlank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function normalizeId(mixed $value): ?string
    {
        if ($this->isBlank($value)) {
            return null;
        }
        $id = is_float($value) ? sprintf('%.0f', $value) : (string) $value;

        return strtoupper(trim($id));
    }

    private function toAmount(mixed $value): ?float
    {
        if ($this->isBlank($value)) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $raw = trim(preg_replace('/^rp\.?\s*/i', '', trim((string) $value)) ?? '');
        if (!preg_match('/\d/', $raw) || preg_match('/[^\d.,\s\-]/', $raw)) {
            return null;
        }
        $negative = str_starts_with($raw, '-');
        $raw = ltrim($raw, '- ');
        if (preg_match('/^\d{1,3}(,\d{3})+$/', $raw)) {
            $raw = str_replace(',', '', $raw);
        }
        $amount = OutsourceEmployeeAttributeMap::parseAmount($raw);
        if ($amount === null) {
            return null;
        }

        return $negative ? -abs($amount) : $amount;
    }
}
