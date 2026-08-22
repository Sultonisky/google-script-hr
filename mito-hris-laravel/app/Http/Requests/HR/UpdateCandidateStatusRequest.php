<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'   => ['required', 'string', 'in:New,Screening,Interview HR,Interview User,Offering,Accepted,Hold,Blacklist,Rejected'],
            'hr_notes' => ['nullable', 'string'],
        ];
    }
}
