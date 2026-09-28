<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'employee_id',
        'full_name',
        'branch_name',
        'division',
        'department',
        'job_position_location',
        'job_position',
        'area_kerja',
        'lokasi_kerja',
        'job_level',
        'grade',
        'join_date',
        'status_employee',
        'direct_superior',
        'indirect_superior',
        'personal_email',
        'working_email',
        'end_date_contract',
        'contract_start',
        'contract_duration',
        'contract_number',
        'birth_place',
        'birth_date',
        'citizen_id_address',
        'residential_address',
        'nik_npwp',
        'npwp',
        'ptkp_status',
        'bank_name',
        'bank_account',
        'bank_account_holder',
        'bpjs_ketenagakerjaan',
        'bpjs_kesehatan',
        'mobile_phone',
        'religion',
        'gender',
        'marital_status',
        'blood_type',
        'cost_center',
        'job_position_former',
        'type_of_rotation',
        'rotation_date',
        'nomor_sk',
        'resign_date',
        'hr_notes',
        'offboarding_type',
        'offboarding_reason',
        'offboarding_approved_by',
        'offboarding_docs_folder',
        'offboarding_doc_links',
        'outsource_vendor',
        'outsource_contract_seq',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'outsource_contract_seq' => 'integer',
        ];
    }
}
