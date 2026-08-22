<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class CandidateSelfUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'             => ['required', 'email', 'max:255'],
            'no_telp'           => ['required', 'string', 'max:30'],
            'alamat'            => ['nullable', 'string'],
            'pendidikan'        => ['nullable', 'string'],
            'pengalaman_kerja'  => ['nullable', 'string'],
            'perusahaan_terakhir' => ['nullable', 'string'],
            'cv_link'           => ['nullable', 'url', 'max:500'],
        ];
    }
}
