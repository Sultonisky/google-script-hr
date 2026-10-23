<?php

namespace App\Http\Requests\HR;

use App\Rules\DivisionBelongsToDepartment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMprRequest extends FormRequest
{
    private const POSITION_REGEX = '/^(?=.*\p{L})[\p{L}0-9](?:[\p{L}0-9]|[ \-&\/().])*$/u';

    private const LANGUAGES_REGEX = '/^[\p{L} ,.\(\)\r\n]+$/u';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity'             => ['sometimes', 'required', 'string', 'max:255', Rule::in(array_keys(config('hris.mpr_form_options.target_entities', [])))],
            'position'           => ['sometimes', 'required', 'string', 'max:255', 'regex:' . self::POSITION_REGEX],
            'department'         => ['sometimes', 'required', 'string', 'max:255', Rule::in(array_keys(config('hris.mpr_department_divisions', [])))],
            'division'           => ['sometimes', 'required', 'string', 'max:255', new DivisionBelongsToDepartment((string) $this->input('department'))],
            'approval_division'  => ['nullable', 'string', 'max:255', Rule::in(config('hris.mpr.approval_divisions', []))],
            'job_level'          => ['sometimes', 'required', 'string', 'max:255'],
            'work_location'      => ['sometimes', 'required', 'string', 'max:255'],
            'employment_type'    => ['sometimes', 'required', 'string', 'max:255'],
            'quantity'           => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'expected_join_date' => ['sometimes', 'required', 'date'],
            'reason'             => ['sometimes', 'required', 'string', 'max:255', Rule::in(array_values(config('hris.mpr_form_options.reasons', [])))],
            'replacement_for'    => [
                Rule::requiredIf(fn () => $this->reasonRequiresReplacement()),
                'nullable',
                'string',
                'max:255',
            ],
            'job_description'    => ['nullable', 'string'],
            'requirements'       => ['nullable', 'string'],
            'requestor_position'    => ['nullable', 'string', 'max:255', 'regex:' . self::POSITION_REGEX],
            'working_days'          => ['nullable', 'array', 'max:1'],
            'working_days.*'        => ['nullable', 'string', Rule::in(array_keys(config('hris.mpr_form_options.working_days', [])))],
            'working_hours'         => ['nullable', 'array', 'max:1'],
            'working_hours.*'       => ['nullable', 'string', Rule::in(array_keys(config('hris.mpr_form_options.working_hours', [])))],
            'shift_detail'          => [
                'nullable', 'string', 'max:1000',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $days = array_map('strtolower', array_map('trim', (array) $this->input('working_days', [])));
                    if (in_array('shifting', $days, true) && trim((string) $value) === '') {
                        $fail('Detail Shift wajib diisi jika Hari Kerja "Shifting" dipilih.');
                    }
                },
            ],
            'benefits'              => ['nullable', 'array'],
            'benefits.*'            => ['nullable', 'string', Rule::in(array_keys(config('hris.mpr_form_options.benefits', [])))],
            'education_background'  => ['nullable', 'string', Rule::in(array_keys(config('hris.mpr_form_options.education_background', [])))],
            'work_experience'       => ['nullable', 'string', Rule::in(array_keys(config('hris.mpr_form_options.work_experience', [])))],
            'skills_competencies'   => ['nullable', 'string', 'max:2000'],
            'languages'             => ['nullable', 'string', 'max:1000', 'regex:' . self::LANGUAGES_REGEX],
            'industry_reference'    => ['nullable', 'string', 'max:1000'],
            'special_notes'         => ['nullable', 'string', 'max:2000'],
            'key_results_targets'   => ['nullable', 'string', 'max:4000'],
        ];
    }

    public function messages(): array
    {
        return [
            'replacement_for.required' => 'Nama karyawan yang digantikan wajib diisi untuk alasan Penggantian atau Restrukturisasi.',
            'position.regex' => 'Posisi / nama jabatan hanya boleh huruf, angka, spasi, dan tanda - / & ( ) .',
            'requestor_position.regex' => 'Jabatan pemohon hanya boleh huruf, angka, spasi, dan tanda - / & ( ) .',
        ];
    }

    private function reasonRequiresReplacement(): bool
    {
        $reason = trim((string) $this->input('reason', ''));
        $reasons = array_map('strval', config('hris.mpr_form_options.replacement_reasons', [
            'Penggantian Karyawan Resign / Mutasi / Demosi',
            'Restrukturisasi',
        ]));

        return in_array($reason, $reasons, true);
    }
}
