<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkCandidateStatusRequest extends FormRequest
{
    public const STATUSES = ['Accepted', 'Hold', 'Blacklist'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recruitment_ids'   => ['required', 'array', 'min:1', 'max:100'],
            'recruitment_ids.*' => ['required', 'string', 'max:64', 'distinct'],
            'status'            => ['required', Rule::in(self::STATUSES)],
            'reason'            => ['nullable', 'string', 'max:500', 'required_if:status,Hold,Blacklist'],
        ];
    }

    public function messages(): array
    {
        return [
            'recruitment_ids.required' => 'Pilih minimal satu kandidat.',
            'recruitment_ids.min'      => 'Pilih minimal satu kandidat.',
            'recruitment_ids.max'      => 'Maksimal 100 kandidat per proses.',
            'recruitment_ids.*.distinct' => 'Terdapat kandidat yang dipilih lebih dari sekali.',
            'status.required'          => 'Status tujuan wajib diisi.',
            'status.in'                => 'Status tujuan tidak valid.',
            'reason.required_if'       => 'Alasan wajib diisi untuk status Hold atau Blacklist.',
            'reason.max'               => 'Alasan maksimal 500 karakter.',
        ];
    }
}
