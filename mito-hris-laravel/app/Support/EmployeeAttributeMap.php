<?php

namespace App\Support;

use App\DTOs\EmployeeData;
use App\Models\Employee;

/**
 * Maps Employee DTO ↔ Eloquent ↔ Sheets-style attribute keys used by services.
 */
final class EmployeeAttributeMap
{
    /** @var array<string, string> Sheets header → DB column */
    public const SHEET_TO_COLUMN = [
        'Employee ID' => 'employee_id',
        'Full Name' => 'full_name',
        'Branch Name' => 'branch_name',
        'Division' => 'division',
        'Department' => 'department',
        'Job Position (Location)' => 'job_position_location',
        'Job Position (Locaction)' => 'job_position_location',
        'Job Position' => 'job_position',
        'Area Kerja' => 'area_kerja',
        'Lokasi Kerja' => 'lokasi_kerja',
        'Job Level' => 'job_level',
        'Grade' => 'grade',
        'Join Date' => 'join_date',
        'Status Employee' => 'status_employee',
        'Direct Superior' => 'direct_superior',
        'Indirect Superior' => 'indirect_superior',
        'Personal Email' => 'personal_email',
        'Working Email' => 'working_email',
        'End Date (Contract)' => 'end_date_contract',
        'Start Date (Contract)' => 'contract_start',
        'Contract Start' => 'contract_start',
        'Contract Duration' => 'contract_duration',
        'Contract Number' => 'contract_number',
        'Birth Place' => 'birth_place',
        'Birth Date' => 'birth_date',
        'Citizen ID Address' => 'citizen_id_address',
        'Residential Address' => 'residential_address',
        'NIK - NPWP 16 digit' => 'nik_npwp',
        'NPWP' => 'npwp',
        'PTKP Status' => 'ptkp_status',
        'Bank Name' => 'bank_name',
        'Bank Account' => 'bank_account',
        'Bank Account Holder' => 'bank_account_holder',
        'BPJS Ketenagakerjaan' => 'bpjs_ketenagakerjaan',
        'BPJS Kesehatan' => 'bpjs_kesehatan',
        'Mobile Phone' => 'mobile_phone',
        'Religion' => 'religion',
        'Gender' => 'gender',
        'Marital Status' => 'marital_status',
        'Blood Type' => 'blood_type',
        'Cost Center' => 'cost_center',
        'Job Position (Former)' => 'job_position_former',
        'Type of Rotation' => 'type_of_rotation',
        'Tanggal Mutasi/Demosi/Promosi' => 'rotation_date',
        'Nomor SK' => 'nomor_sk',
        'Resign Date' => 'resign_date',
        'HR Notes' => 'hr_notes',
        'Offboarding Type' => 'offboarding_type',
        'Offboarding Reason' => 'offboarding_reason',
        'Offboarding Approved By' => 'offboarding_approved_by',
        'Offboarding Documents Folder' => 'offboarding_docs_folder',
        'Offboarding Document Links' => 'offboarding_doc_links',
        'Outsource Vendor' => 'outsource_vendor',
        'Outsource Contract Seq' => 'outsource_contract_seq',
        'Created By' => 'created_by',
        'Created At' => 'created_at',
        'Updated At' => 'updated_at',
    ];

    public static function toData(Employee $model): EmployeeData
    {
        return new EmployeeData(
            employeeId: $model->employee_id,
            fullName: $model->full_name,
            branchName: $model->branch_name,
            division: $model->division,
            department: $model->department,
            jobPositionLocation: $model->job_position_location,
            jobPosition: $model->job_position,
            areaKerja: $model->area_kerja,
            lokasiKerja: $model->lokasi_kerja,
            jobLevel: $model->job_level,
            grade: $model->grade,
            joinDate: $model->join_date,
            statusEmployee: $model->status_employee ?? 'Contract',
            directSuperior: $model->direct_superior,
            indirectSuperior: $model->indirect_superior,
            personalEmail: $model->personal_email,
            workingEmail: $model->working_email,
            endDateContract: $model->end_date_contract,
            contractStart: $model->contract_start,
            contractDuration: $model->contract_duration,
            contractNumber: $model->contract_number,
            birthPlace: $model->birth_place,
            birthDate: $model->birth_date,
            citizenIdAddress: $model->citizen_id_address,
            residentialAddress: $model->residential_address,
            nikNpwp: self::stripLeadingQuote($model->nik_npwp),
            npwp: self::stripLeadingQuote($model->npwp),
            ptkpStatus: $model->ptkp_status,
            bankName: $model->bank_name,
            bankAccount: self::stripLeadingQuote($model->bank_account),
            bankAccountHolder: $model->bank_account_holder,
            bpjsKetenagakerjaan: self::stripLeadingQuote($model->bpjs_ketenagakerjaan),
            bpjsKesehatan: self::stripLeadingQuote($model->bpjs_kesehatan),
            mobilePhone: self::stripLeadingQuote($model->mobile_phone),
            religion: $model->religion,
            gender: $model->gender,
            maritalStatus: $model->marital_status,
            bloodType: $model->blood_type,
            costCenter: $model->cost_center,
            jobPositionFormer: $model->job_position_former,
            typeOfRotation: $model->type_of_rotation,
            rotationDate: $model->rotation_date,
            nomorSk: $model->nomor_sk,
            resignDate: $model->resign_date,
            hrNotes: $model->hr_notes,
            offboardingType: $model->offboarding_type,
            offboardingReason: $model->offboarding_reason,
            offboardingApprovedBy: $model->offboarding_approved_by,
            offboardingDocsFolder: $model->offboarding_docs_folder,
            offboardingDocLinks: $model->offboarding_doc_links,
            outsourceVendor: $model->outsource_vendor,
            outsourceContractSeq: $model->outsource_contract_seq,
            createdBy: $model->created_by,
            createdAt: optional($model->created_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            updatedAt: optional($model->updated_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            rowNumber: null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toFillable(EmployeeData $data): array
    {
        return [
            'employee_id' => $data->employeeId,
            'full_name' => $data->fullName,
            'branch_name' => $data->branchName,
            'division' => $data->division,
            'department' => $data->department,
            'job_position_location' => $data->jobPositionLocation,
            'job_position' => $data->jobPosition,
            'area_kerja' => $data->areaKerja,
            'lokasi_kerja' => $data->lokasiKerja,
            'job_level' => $data->jobLevel,
            'grade' => $data->grade,
            'join_date' => $data->joinDate,
            'status_employee' => $data->statusEmployee ?? 'Contract',
            'direct_superior' => $data->directSuperior,
            'indirect_superior' => $data->indirectSuperior,
            'personal_email' => $data->personalEmail,
            'working_email' => $data->workingEmail,
            'end_date_contract' => $data->endDateContract,
            'contract_start' => $data->contractStart,
            'contract_duration' => $data->contractDuration,
            'contract_number' => $data->contractNumber,
            'birth_place' => $data->birthPlace,
            'birth_date' => $data->birthDate,
            'citizen_id_address' => $data->citizenIdAddress,
            'residential_address' => $data->residentialAddress,
            'nik_npwp' => self::stripLeadingQuote($data->nikNpwp),
            'npwp' => self::stripLeadingQuote($data->npwp),
            'ptkp_status' => $data->ptkpStatus,
            'bank_name' => $data->bankName,
            'bank_account' => self::stripLeadingQuote($data->bankAccount),
            'bank_account_holder' => $data->bankAccountHolder,
            'bpjs_ketenagakerjaan' => self::stripLeadingQuote($data->bpjsKetenagakerjaan),
            'bpjs_kesehatan' => self::stripLeadingQuote($data->bpjsKesehatan),
            'mobile_phone' => self::stripLeadingQuote($data->mobilePhone),
            'religion' => $data->religion,
            'gender' => $data->gender,
            'marital_status' => $data->maritalStatus,
            'blood_type' => $data->bloodType,
            'cost_center' => $data->costCenter,
            'job_position_former' => $data->jobPositionFormer,
            'type_of_rotation' => $data->typeOfRotation,
            'rotation_date' => $data->rotationDate,
            'nomor_sk' => $data->nomorSk,
            'resign_date' => $data->resignDate,
            'hr_notes' => $data->hrNotes,
            'offboarding_type' => $data->offboardingType,
            'offboarding_reason' => $data->offboardingReason,
            'offboarding_approved_by' => $data->offboardingApprovedBy,
            'offboarding_docs_folder' => $data->offboardingDocsFolder,
            'offboarding_doc_links' => $data->offboardingDocLinks,
            'outsource_vendor' => $data->outsourceVendor,
            'outsource_contract_seq' => $data->outsourceContractSeq,
            'created_by' => $data->createdBy ?? 'HR Administrator',
        ];
    }

    /**
     * Convert Sheets-style update attributes to DB columns.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function sheetAttributesToColumns(array $attributes): array
    {
        $updates = [];
        foreach ($attributes as $key => $value) {
            $column = self::SHEET_TO_COLUMN[$key] ?? null;
            if ($column === null) {
                continue;
            }
            if (in_array($column, ['nik_npwp', 'npwp', 'bank_account', 'bpjs_ketenagakerjaan', 'bpjs_kesehatan', 'mobile_phone'], true)) {
                $value = self::stripLeadingQuote(is_string($value) ? $value : (string) $value);
            }
            if ($column === 'outsource_contract_seq') {
                $value = $value === '' || $value === null ? null : (int) $value;
            }
            if ($column === 'updated_at' || $column === 'created_at') {
                // Let Eloquent timestamps handle updated_at unless an explicit string is useful;
                // still accept explicit Jakarta timestamps as strings by skipping cast here.
                $updates[$column] = $value;
                continue;
            }
            $updates[$column] = $value;
        }

        return $updates;
    }

    private static function stripLeadingQuote(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return ltrim(trim($value), "'");
    }
}
