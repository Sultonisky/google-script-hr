<?php

namespace App\Http\Requests\HR;

use App\Enums\CertType;
use App\Enums\CertStatus;
use App\Rules\ExistingEmployeeId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cert_code'            => ['nullable', 'string', 'max:50', 'unique:certifications,cert_code'],
            'cert_type'            => ['required', Rule::in(array_column(CertType::cases(), 'value'))],
            'name'                 => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:1000'],
            'issuing_organization' => ['required', 'string', 'max:255'],
            'certificate_number'   => ['nullable', 'string', 'max:255'],
            'issue_date'           => ['required', 'date'],
            'expiry_date'          => ['nullable', 'date', 'after_or_equal:issue_date'],
            'status'               => ['nullable', Rule::in(array_column(CertStatus::cases(), 'value'))],
            'employee_id'          => ['required', 'string', 'max:100', new ExistingEmployeeId],
            'employee_name'        => ['required', 'string', 'max:255'],
            'division'             => ['nullable', 'string', 'max:255'],
            'department'           => ['nullable', 'string', 'max:255'],
            'notes'                => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'cert_type.required'            => 'Jenis sertifikasi wajib dipilih.',
            'name.required'                 => 'Nama sertifikasi wajib diisi.',
            'issuing_organization.required' => 'Lembaga penerbit wajib diisi.',
            'issue_date.required'           => 'Tanggal terbit wajib diisi.',
            'employee_id.required'          => 'Karyawan wajib dipilih.',
            'employee_name.required'        => 'Nama karyawan wajib diisi.',
            'cert_code.unique'              => 'Kode sertifikasi sudah digunakan.',
            'expiry_date.after_or_equal'    => 'Tanggal kedaluwarsa harus setelah atau sama dengan tanggal terbit.',
        ];
    }
}
