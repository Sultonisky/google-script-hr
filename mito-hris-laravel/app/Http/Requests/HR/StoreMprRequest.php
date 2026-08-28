<?php

namespace App\Http\Requests\HR;

use App\Rules\DivisionBelongsToDepartment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMprRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'position'           => ['required', 'string', 'max:255'],
            'department'         => ['required', 'string', 'max:255', Rule::in(array_keys(config('hris.mpr_department_divisions', [])))],
            'division'           => ['required', 'string', 'max:255', new DivisionBelongsToDepartment((string) $this->input('department'))],
            'job_level'          => ['required', 'string', 'max:255'],
            'work_location'      => ['required', 'string', 'max:255'],
            'employment_type'    => ['required', 'string', 'max:255'],
            'quantity'           => ['required', 'integer', 'min:1', 'max:100'],
            'expected_join_date' => ['required', 'date'],
            'reason'             => ['required', 'string', 'max:255'],
            'replacement_for'    => ['nullable', 'string', 'max:255'],
            'job_description'    => ['nullable', 'string'],
            'requirements'       => ['nullable', 'string'],
            'notes'              => ['nullable', 'string'],
            // Requestor identity (for HR/Super Admin creating on behalf of manager)
            'manager_name'       => ['nullable', 'string', 'max:255'],
            'manager_email'      => ['nullable', 'email', 'max:255'],
            // entity: entity yang dipilih requestor — WAJIB diisi
            'entity'             => ['required', 'string', 'max:255'],
            // company: alias lama, opsional (digunakan form HR lama, akan dipetakan ke entity di controller)
            'company'            => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'position.required'           => 'Posisi / jabatan yang diminta wajib diisi.',
            'department.required'         => 'Departemen wajib dipilih.',
            'division.required'           => 'Divisi wajib dipilih.',
            'department.in'               => 'Departemen yang dipilih tidak tersedia.',
            'division.*'                  => 'Divisi yang dipilih tidak sesuai dengan departemen.',
            'job_level.required'          => 'Level jabatan wajib dipilih.',
            'work_location.required'      => 'Lokasi kerja penempatan wajib dipilih.',
            'employment_type.required'    => 'Status kepegawaian wajib dipilih.',
            'quantity.required'           => 'Jumlah kebutuhan manpower wajib diisi.',
            'quantity.integer'            => 'Jumlah kebutuhan manpower harus berupa angka.',
            'quantity.min'                => 'Jumlah kebutuhan manpower minimal 1 orang.',
            'expected_join_date.required' => 'Tanggal target bergabung (expected join date) wajib diisi.',
            'expected_join_date.date'     => 'Format tanggal target bergabung tidak valid.',
            'reason.required'             => 'Alasan permintaan manpower wajib dipilih.',
            'entity.required'             => 'Pilih entitas / perusahaan untuk pengajuan MPR ini.',
        ];
    }
}
