<?php

namespace App\Http\Requests\HR;

use App\Rules\DivisionBelongsToDepartment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMprRequest extends FormRequest
{
    private const NAME_REGEX = '/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u';

    private const POSITION_REGEX = '/^(?=.*\p{L})[\p{L}0-9](?:[\p{L}0-9]|[ \-&\/().])*$/u';

    private const LANGUAGES_REGEX = '/^[\p{L} ,.\(\)\r\n]+$/u';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'position'           => ['required', 'string', 'max:255', 'regex:' . self::POSITION_REGEX],
            'department'         => ['required', 'string', 'max:255', Rule::in(array_keys(config('hris.mpr_department_divisions', [])))],
            'division'           => ['required', 'string', 'max:255', new DivisionBelongsToDepartment((string) $this->input('department'))],
            'approval_division'  => ['nullable', 'string', 'max:255', Rule::in(config('hris.mpr.approval_divisions', []))],
            'job_level'          => ['required', 'string', 'max:255'],
            'work_location'      => ['required', 'string', 'max:255'],
            'employment_type'    => ['required', 'string', 'max:255'],
            'quantity'           => ['required', 'integer', 'min:1', 'max:100'],
            'expected_join_date' => ['required', 'date'],
            'reason'             => ['required', 'string', 'max:255', Rule::in(array_values(config('hris.mpr_form_options.reasons', [])))],
            'replacement_for'    => [
                Rule::requiredIf(fn () => $this->reasonRequiresReplacement()),
                'nullable',
                'string',
                'max:255',
            ],
            'job_description'    => ['nullable', 'string'],
            'requirements'       => ['nullable', 'string'],
            // 'Notes' deprecated — diganti 'Special Notes'
            // Requestor identity (for HR/Super Admin creating on behalf of manager)
            'manager_name'       => ['nullable', 'string', 'max:255', 'regex:' . self::NAME_REGEX],
            'manager_email'      => ['nullable', 'email', 'max:255'],
            // entity: target entity yang dituju untuk kebutuhan posisi
            'entity'             => ['required', 'string', 'max:255', Rule::in(array_keys(config('hris.mpr_form_options.target_entities', [])))],
            // -- Field baru (Refactor Create MPR) --
            'requestor_position'    => ['nullable', 'string', 'max:255', 'regex:' . self::POSITION_REGEX],
            // Hari Kerja: single radio
            'working_days'          => ['required', 'array', 'min:1', 'max:1'],
            'working_days.*'        => ['string', Rule::in(array_keys(config('hris.mpr_form_options.working_days', [])))],
            // Jam Kerja: single radio (tidak wajib jika Hari Kerja = Shifting)
            'working_hours'         => [
                Rule::requiredIf(fn () => !$this->isShiftingSelected()),
                'array',
                'max:1',
            ],
            'working_hours.*'       => ['string', Rule::in(array_keys(config('hris.mpr_form_options.working_hours', [])))],
            // Shift Detail: free text, wajib hanya jika Hari Kerja "Shifting" dipilih
            'shift_detail'          => [
                'nullable', 'string', 'max:1000',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $days = array_map('strtolower', array_map('trim', (array) $this->input('working_days', [])));
                    if (in_array('shifting', $days, true) && trim((string) $value) === '') {
                        $fail('Detail Shift wajib diisi jika Hari Kerja "Shifting" dipilih.');
                    }
                },
            ],
            // Benefits: multiple checkbox
            'benefits'              => ['required', 'array', 'min:1'],
            'benefits.*'            => ['string', Rule::in(array_keys(config('hris.mpr_form_options.benefits', [])))],
            // Pendidikan & Pengalaman: single selection
            'education_background'  => ['required', 'string', Rule::in(array_keys(config('hris.mpr_form_options.education_background', [])))],
            'work_experience'       => ['required', 'string', Rule::in(array_keys(config('hris.mpr_form_options.work_experience', [])))],
            // Free text kualifikasi
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
            'position.required'           => 'Posisi / jabatan yang diminta wajib diisi.',
            'department.required'         => 'Departemen wajib dipilih.',
            'division.required'           => 'Divisi wajib dipilih.',
            'approval_division.required'  => 'Disetujui oleh (Divisi) wajib dipilih.',
            'approval_division.in'        => 'Divisi penandatangan tidak valid.',
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
            'position.regex'              => 'Posisi / nama jabatan hanya boleh huruf, angka, spasi, dan tanda - / & ( ) .',
            'manager_name.regex'          => 'Nama pemohon hanya boleh huruf dan spasi.',
            'requestor_position.regex'    => 'Jabatan pemohon hanya boleh huruf, angka, spasi, dan tanda - / & ( ) .',
            'replacement_for.required'    => 'Nama karyawan yang digantikan wajib diisi untuk alasan Penggantian atau Restrukturisasi.',
            'languages.regex'             => 'Bahasa yang dikuasai hanya boleh huruf, spasi, koma, titik, dan kurung.',
            'working_days.required'       => 'Hari Kerja wajib dipilih.',
            'working_days.max'            => 'Hari Kerja hanya boleh dipilih satu.',
            'working_days.*.in'           => 'Pilihan Hari Kerja tidak valid.',
            'working_hours.required'      => 'Jam Kerja wajib dipilih.',
            'working_hours.max'           => 'Jam Kerja hanya boleh dipilih satu.',
            'working_hours.*.in'          => 'Pilihan Jam Kerja tidak valid.',
            'benefits.required'           => 'Benefits wajib dipilih.',
            'benefits.min'                => 'Benefits wajib dipilih minimal satu.',
            'benefits.*.in'               => 'Pilihan Benefits tidak valid.',
            'education_background.required' => 'Latar Belakang Pendidikan wajib dipilih.',
            'education_background.in'     => 'Pilihan Latar Belakang Pendidikan tidak valid.',
            'work_experience.required'    => 'Pengalaman Kerja wajib dipilih.',
            'work_experience.in'          => 'Pilihan Pengalaman Kerja tidak valid.',
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

    private function isShiftingSelected(): bool
    {
        $days = array_map('strtolower', array_map('trim', (array) $this->input('working_days', [])));

        return in_array('shifting', $days, true);
    }
}
