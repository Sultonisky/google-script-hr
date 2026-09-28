<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutsourceEmployee extends Model
{
    protected $table = 'outsource_employees';

    protected $fillable = [
        'outsource_id',
        'full_name',
        'citizen_id_address',
        'birth_date',
        'birth_place',
        'last_education',
        'whatsapp_number',
        'email',
        'job_title',
        'work_location',
        'work_city',
        'bank_account',
        'mito_join_date',
        'contract_start_date',
        'contract_end_date',
        'cost_center',
        'entity',
        'payroll_scheme',
        'umk_amount',
        'basic_salary',
        'incentive_amount',
        'remarks',
        'vendor',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'umk_amount' => 'decimal:2',
            'basic_salary' => 'decimal:2',
            'incentive_amount' => 'decimal:2',
        ];
    }
}
