<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class HoldCandidateRequest extends FormRequest
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
            'reason.required' => 'Alasan hold wajib diisi.',
        ];
    }
}
