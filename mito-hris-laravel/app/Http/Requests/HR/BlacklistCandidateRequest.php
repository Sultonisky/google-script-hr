<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class BlacklistCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan blacklist wajib dicantumkan secara jelas.',
        ];
    }
}
