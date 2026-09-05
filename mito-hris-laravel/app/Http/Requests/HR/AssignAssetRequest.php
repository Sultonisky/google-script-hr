<?php

namespace App\Http\Requests\HR;

use App\Rules\ExistingEmployeeId;
use Illuminate\Foundation\Http\FormRequest;

class AssignAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'    => ['required', 'string', 'max:100', new ExistingEmployeeId],
            'assigned_date'  => ['required', 'date'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required'   => 'Karyawan wajib dipilih.',
            'assigned_date.required' => 'Tanggal penugasan wajib diisi.',
            'assigned_date.date'     => 'Format tanggal tidak valid.',
        ];
    }
}