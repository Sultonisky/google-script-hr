<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $identifier = $this->input('identifier');

        if (is_string($identifier)) {
            $this->merge([
                'identifier' => strtolower(trim($identifier)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'email:filter', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'rememberMe' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'Email wajib diisi.',
            'identifier.email' => 'Format email tidak valid.',
            'identifier.max' => 'Email terlalu panjang.',
            'password.required' => 'Password wajib diisi.',
            'password.max' => 'Password terlalu panjang.',
        ];
    }
}
