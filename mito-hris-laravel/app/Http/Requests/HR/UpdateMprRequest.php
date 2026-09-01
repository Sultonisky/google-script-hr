<?php

namespace App\Http\Requests\HR;

use App\Rules\DivisionBelongsToDepartment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMprRequest extends FormRequest
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
            // 'Notes' deprecated — diganti 'Special Notes'
        ];
    }
}
