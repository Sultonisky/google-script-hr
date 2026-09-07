<?php

namespace App\Http\Requests\HR;

use App\Enums\CertType;
use App\Enums\CertStatus;
use App\Rules\ExistingEmployeeId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $certId = $this->route('certification')?->id ?? $this->route('certification');

        return [
            'cert_code'            => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('certifications', 'cert_code')->ignore($certId),
            ],
            'cert_type'            => ['required', Rule::enum(CertType::class)],
            'name'                 => ['required', 'string', 'max:255'],
            'product_scope'        => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $this->productClassification()), Rule::prohibitedIf(fn () => $this->companyClassification())],
            'brand'                => ['nullable', 'string', 'max:100', Rule::requiredIf(fn () => $this->productClassification()), Rule::prohibitedIf(fn () => $this->companyClassification())],
            'company_scope'        => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $this->companyClassification()), Rule::prohibitedIf(fn () => $this->productClassification())],
            'description'          => ['nullable', 'string', 'max:1000'],
            'issuing_organization' => ['required', 'string', 'max:255'],
            'certificate_number'   => ['nullable', 'string', 'max:255'],
            'issue_date'           => ['required', 'date'],
            'expiry_date'          => ['nullable', 'date', 'after_or_equal:issue_date'],
            'status'               => ['nullable', Rule::in(array_column(CertStatus::cases(), 'value'))],
            'employee_id'          => ['nullable', 'string', 'max:100', new ExistingEmployeeId],
            'employee_name'        => ['nullable', 'string', 'max:255'],
            'division'             => ['nullable', 'string', 'max:255'],
            'department'           => ['nullable', 'string', 'max:255'],
            'notes'                => ['nullable', 'string', 'max:2000'],
            'attachment'           => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'remove_attachment'    => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'cert_type.required'            => 'Jenis sertifikasi wajib dipilih.',
            'name.required'                 => 'Nama sertifikasi wajib diisi.',
            'issuing_organization.required' => 'Lembaga penerbit wajib diisi.',
            'issue_date.required'           => 'Tanggal terbit wajib diisi.',
            'cert_code.unique'              => 'Kode sertifikasi sudah digunakan.',
            'expiry_date.after_or_equal'    => 'Tanggal kedaluwarsa harus setelah atau sama dengan tanggal terbit.',
            'product_scope.required'        => 'Produk / scope produk wajib diisi untuk klasifikasi ini.',
            'brand.required'                => 'Brand wajib diisi untuk klasifikasi produk.',
            'company_scope.required'        => 'Scope perusahaan wajib diisi untuk klasifikasi ISO/K3.',
        ];
    }

    private function productClassification(): bool
    {
        return in_array($this->input('cert_type'), [
            CertType::SNI->value,
            CertType::FOOD_SAFETY->value,
            CertType::PRODUCT_SAFETY->value,
        ], true);
    }

    private function companyClassification(): bool
    {
        return in_array($this->input('cert_type'), [CertType::ISO->value, CertType::K3->value], true);
    }
}
