<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MprRequest extends Model
{
    protected $table = 'mpr_requests';

    protected $fillable = [
        'mpr_number',
        'request_date',
        'requestor_name',
        'requestor_email',
        'entity',
        'department',
        'division',
        'approval_division',
        'position',
        'job_level',
        'work_location',
        'employment_type',
        'quantity',
        'expected_join_date',
        'reason',
        'replacement_for',
        'job_description',
        'requirements',
        'requestor_position',
        'working_days',
        'working_hours',
        'shift_detail',
        'benefits',
        'education_background',
        'work_experience',
        'skills',
        'languages',
        'industry_reference',
        'special_notes',
        'key_results',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}
