<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $table = 'candidates';

    protected $fillable = [
        'recruitment_id',
        'lifecycle_status',
        'full_name',
        'nik',
        'birth_date',
        'age',
        'gender',
        'blood_type',
        'marital_status',
        'email',
        'phone',
        'address',
        'city',
        'position_applied',
        'education',
        'work_experience',
        'last_company',
        'current_employment_status',
        'available_to_join',
        'expected_salary',
        'recruitment_source',
        'status',
        'hr_notes',
        'created_by',
        'hold_reason',
        'hold_follow_up_date',
        'blacklist_reason',
        'blacklist_date',
        'blacklist_updated_by',
        'employee_id',
        'processed_date',
        'processed_by',
        'offering_payload',
        'extra_payload',
        'created_date',
    ];

    protected function casts(): array
    {
        return [
            'offering_payload' => 'array',
            'extra_payload' => 'array',
        ];
    }
}
