<?php

namespace App\Support;

use App\Models\ProbationEvaluation;

/**
 * Maps kandidat_probation sheet rows ↔ probation_evaluations table.
 * Indicators + approval fields live in the indicators JSON column.
 */
final class ProbationAttributeMap
{
    /** @var list<string> */
    public const INDICATOR_HEADERS = [
        'ind_integrity_1',
        'ind_integrity_2',
        'ind_integrity_3',
        'ind_integrity_4',
        'ind_ci_1',
        'ind_ci_2',
        'ind_ci_3',
        'ind_ci_4',
        'ind_ee_1',
        'ind_ee_2',
        'ind_tw_1',
        'ind_tw_2',
        'ind_tw_3',
        'Reviewer Name',
        'Approval Dept',
        'Approval Dept Name',
        'Approval Dept Date',
        'Approval HRBP',
        'Approval HRBP Name',
        'Approval HRBP Date',
    ];

    /**
     * @param  array<string, mixed>  $data  Sheet-header keyed row
     * @return array<string, mixed>
     */
    public static function toFillable(array $data): array
    {
        $indicators = [];
        foreach (self::INDICATOR_HEADERS as $header) {
            if (array_key_exists($header, $data)) {
                $indicators[$header] = (string) ($data[$header] ?? '');
            }
        }

        return [
            'probation_id' => (string) ($data['Probation ID'] ?? ''),
            'employee_id' => (string) ($data['Employee ID'] ?? ''),
            'recruitment_id' => (string) ($data['Recruitment ID'] ?? ''),
            'contract_duration' => (string) ($data['Contract Duration'] ?? ''),
            'contract_start' => (string) ($data['Contract Start'] ?? ''),
            'contract_end' => (string) ($data['Contract End'] ?? ''),
            'join_date' => (string) ($data['Join Date'] ?? ''),
            'status' => (string) ($data['Status'] ?? ''),
            'eval_id' => (string) ($data['Eval ID'] ?? ''),
            'eval_date' => (string) ($data['Eval Date'] ?? ''),
            'decision' => (string) ($data['Decision'] ?? ''),
            'extension_duration' => (string) ($data['Extension Duration'] ?? ''),
            'new_contract_start' => (string) ($data['New Contract Start'] ?? ''),
            'new_contract_end' => (string) ($data['New Contract End'] ?? ''),
            'evaluator_notes' => (string) ($data['Evaluator Notes'] ?? ''),
            'evaluator' => (string) ($data['Evaluator'] ?? ''),
            'sk_status' => (string) ($data['SK Status'] ?? ''),
            'integrity_total' => self::nullableInt($data['Integrity Total'] ?? null),
            'ci_total' => self::nullableInt($data['CI Total'] ?? null),
            'ee_total' => self::nullableInt($data['EE Total'] ?? null),
            'teamwork_total' => self::nullableInt($data['Teamwork Total'] ?? null),
            'overall_total' => self::nullableInt($data['Overall Total'] ?? null),
            'category' => (string) ($data['Category'] ?? ''),
            'indicators' => $indicators,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function toSheetRow(ProbationEvaluation $model): array
    {
        $headers = config('hris.schemas.kandidat_probation', []);
        if (!is_array($headers)) {
            $headers = [];
        }

        $indicators = is_array($model->indicators) ? $model->indicators : [];

        $assoc = [
            'Probation ID' => (string) ($model->probation_id ?? ''),
            'Employee ID' => (string) ($model->employee_id ?? ''),
            'Recruitment ID' => (string) ($model->recruitment_id ?? ''),
            'Contract Duration' => (string) ($model->contract_duration ?? ''),
            'Contract Start' => (string) ($model->contract_start ?? ''),
            'Contract End' => (string) ($model->contract_end ?? ''),
            'Join Date' => (string) ($model->join_date ?? ''),
            'Status' => (string) ($model->status ?? ''),
            'Eval ID' => (string) ($model->eval_id ?? ''),
            'Eval Date' => (string) ($model->eval_date ?? ''),
            'Decision' => (string) ($model->decision ?? ''),
            'Extension Duration' => (string) ($model->extension_duration ?? ''),
            'New Contract Start' => (string) ($model->new_contract_start ?? ''),
            'New Contract End' => (string) ($model->new_contract_end ?? ''),
            'Evaluator Notes' => (string) ($model->evaluator_notes ?? ''),
            'Evaluator' => (string) ($model->evaluator ?? ''),
            'SK Status' => (string) ($model->sk_status ?? ''),
            'Created At' => optional($model->created_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
            'Updated At' => optional($model->updated_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
            'Integrity Total' => $model->integrity_total !== null ? (string) $model->integrity_total : '',
            'CI Total' => $model->ci_total !== null ? (string) $model->ci_total : '',
            'EE Total' => $model->ee_total !== null ? (string) $model->ee_total : '',
            'Teamwork Total' => $model->teamwork_total !== null ? (string) $model->teamwork_total : '',
            'Overall Total' => $model->overall_total !== null ? (string) $model->overall_total : '',
            'Category' => (string) ($model->category ?? ''),
        ];

        foreach (self::INDICATOR_HEADERS as $header) {
            $assoc[$header] = (string) ($indicators[$header] ?? '');
        }

        $row = [];
        foreach ($headers as $header) {
            $row[$header] = (string) ($assoc[$header] ?? '');
        }

        return $row;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
