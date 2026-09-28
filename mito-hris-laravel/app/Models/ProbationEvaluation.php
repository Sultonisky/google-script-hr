<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProbationEvaluation extends Model
{
    protected $table = 'probation_evaluations';

    protected $fillable = [
        'probation_id',
        'employee_id',
        'recruitment_id',
        'contract_duration',
        'contract_start',
        'contract_end',
        'join_date',
        'status',
        'eval_id',
        'eval_date',
        'decision',
        'extension_duration',
        'new_contract_start',
        'new_contract_end',
        'evaluator_notes',
        'evaluator',
        'sk_status',
        'integrity_total',
        'ci_total',
        'ee_total',
        'teamwork_total',
        'overall_total',
        'category',
        'indicators',
    ];

    protected function casts(): array
    {
        return [
            'indicators' => 'array',
            'integrity_total' => 'integer',
            'ci_total' => 'integer',
            'ee_total' => 'integer',
            'teamwork_total' => 'integer',
            'overall_total' => 'integer',
        ];
    }
}
