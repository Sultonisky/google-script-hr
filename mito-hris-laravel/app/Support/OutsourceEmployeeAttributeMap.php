<?php

namespace App\Support;

use App\DTOs\OutsourceEmployeeData;
use App\Models\OutsourceEmployee;

/**
 * Single mapping for the outsource store: DTO property ↔ DB column ↔ Sheets header.
 * Order matches config('hris.schemas.Outsource_Employees').
 */
final class OutsourceEmployeeAttributeMap
{
    /** @var array<string, array{0:string, 1:string}> property => [column, header] */
    public const FIELDS = [
        'outsourceId' => ['outsource_id', 'Outsource ID'],
        'fullName' => ['full_name', 'Full Name'],
        'citizenIdAddress' => ['citizen_id_address', 'Citizen ID Address'],
        'birthDate' => ['birth_date', 'Birth Date'],
        'birthPlace' => ['birth_place', 'Birth Place'],
        'lastEducation' => ['last_education', 'Last Education'],
        'whatsappNumber' => ['whatsapp_number', 'WhatsApp Number'],
        'email' => ['email', 'Email'],
        'jobTitle' => ['job_title', 'Job Title'],
        'workLocation' => ['work_location', 'Work Location'],
        'workCity' => ['work_city', 'Work City'],
        'bankAccount' => ['bank_account', 'BCA Account Number'],
        'mitoJoinDate' => ['mito_join_date', 'Mito Join Date'],
        'contractStartDate' => ['contract_start_date', 'Contract Start Date'],
        'contractEndDate' => ['contract_end_date', 'Contract End Date'],
        'costCenter' => ['cost_center', 'Branch (Cost Center)'],
        'entity' => ['entity', 'Entity'],
        'payrollScheme' => ['payroll_scheme', 'Payroll Scheme'],
        'umkAmount' => ['umk_amount', 'UMK Amount'],
        'basicSalary' => ['basic_salary', 'Basic Salary'],
        'incentiveAmount' => ['incentive_amount', 'Incentive Amount (30%)'],
        'remarks' => ['remarks', 'Remarks'],
        'vendor' => ['vendor', 'Vendor'],
        'createdBy' => ['created_by', 'Created By'],
        'createdAt' => ['created_at', 'Created At'],
        'updatedAt' => ['updated_at', 'Updated At'],
    ];

    public const AMOUNT_FIELDS = ['umkAmount', 'basicSalary', 'incentiveAmount'];

    public const IDENTIFIER_FIELDS = ['whatsappNumber', 'bankAccount'];

    private const READ_ONLY_FIELDS = ['outsourceId', 'createdBy', 'createdAt', 'updatedAt'];

    /** @return list<string> */
    public static function headers(): array
    {
        return array_values(array_map(fn (array $field) => $field[1], self::FIELDS));
    }

    public static function toData(OutsourceEmployee $model): OutsourceEmployeeData
    {
        $values = [];
        foreach (self::FIELDS as $property => [$column]) {
            if ($column === 'created_at' || $column === 'updated_at') {
                $values[$property] = optional($model->{$column})?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                continue;
            }
            $values[$property] = self::normalize($property, $model->{$column});
        }

        return new OutsourceEmployeeData(...$values);
    }

    /** @param  array<string, mixed>  $row  Sheets row keyed by header */
    public static function fromSheetRow(array $row): OutsourceEmployeeData
    {
        $values = [];
        foreach (self::FIELDS as $property => [, $header]) {
            $values[$property] = self::normalize($property, $row[$header] ?? null);
        }
        $values['rowNumber'] = isset($row['_row_number']) ? (int) $row['_row_number'] : null;

        return new OutsourceEmployeeData(...$values);
    }

    /** @return array<string, mixed> */
    public static function toFillable(OutsourceEmployeeData $data): array
    {
        $payload = [];
        foreach (self::FIELDS as $property => [$column]) {
            if ($column === 'created_at' || $column === 'updated_at') {
                continue;
            }
            $payload[$column] = $data->{$property};
        }
        $payload['created_by'] = $data->createdBy ?? 'HR Administrator';

        return $payload;
    }

    /** @return list<string> */
    public static function toSheetRow(OutsourceEmployeeData $data): array
    {
        $row = [];
        foreach (self::FIELDS as $property => $field) {
            $value = $data->{$property};
            $row[] = $value === null ? '' : self::formatForSheet($property, $value);
        }

        return $row;
    }

    /**
     * Keep only writable DTO properties, normalized.
     *
     * @param  array<string, mixed>  $attributes  keyed by DTO property
     * @return array<string, mixed>
     */
    public static function writableAttributes(array $attributes): array
    {
        $clean = [];
        foreach ($attributes as $property => $value) {
            if (!isset(self::FIELDS[$property]) || in_array($property, self::READ_ONLY_FIELDS, true)) {
                continue;
            }
            $clean[$property] = self::normalize($property, $value);
        }

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $attributes  keyed by DTO property
     * @return array<string, mixed>
     */
    public static function attributesToColumns(array $attributes): array
    {
        $columns = [];
        foreach (self::writableAttributes($attributes) as $property => $value) {
            $columns[self::FIELDS[$property][0]] = $value;
        }

        return $columns;
    }

    public static function normalize(string $property, mixed $value): string|float|null
    {
        if (in_array($property, self::AMOUNT_FIELDS, true)) {
            return self::parseAmount($value);
        }
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        if (in_array($property, self::IDENTIFIER_FIELDS, true)) {
            $value = ltrim($value, "'");
        }

        return $value === '' ? null : $value;
    }

    /**
     * Accepts 3984000, "3984000.00", "3.984.000", "5.396" (= 5396), "Rp 3.984.000,50".
     */
    public static function parseAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $raw = trim((string) $value);
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $raw)) {
            return (float) str_replace('.', '', $raw);
        }
        if (preg_match('/^-?\d+(\.\d+)?$/', $raw)) {
            return (float) $raw;
        }
        $clean = preg_replace('/[^\d,]/', '', $raw) ?? '';
        if ($clean === '') {
            return null;
        }

        return (float) str_replace(',', '.', $clean);
    }

    private static function formatForSheet(string $property, string|float $value): string
    {
        if (in_array($property, self::AMOUNT_FIELDS, true)) {
            $number = (float) $value;

            return floor($number) === $number ? (string) (int) $number : (string) $number;
        }

        return (string) $value;
    }
}
