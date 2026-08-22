<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class AcceptCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }
}
