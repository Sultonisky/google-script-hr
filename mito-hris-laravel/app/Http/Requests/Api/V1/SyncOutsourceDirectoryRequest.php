<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SyncOutsourceDirectoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $people = $this->input('people');
        if (! is_array($people)) {
            return;
        }

        foreach ($people as $index => $person) {
            if (! is_array($person)) {
                continue;
            }

            if (isset($person['outsource_id']) && is_string($person['outsource_id'])) {
                $people[$index]['outsource_id'] = strtoupper(trim($person['outsource_id']));
            }
            if (isset($person['full_name']) && is_string($person['full_name'])) {
                $people[$index]['full_name'] = trim($person['full_name']);
            }
        }

        $this->merge(['people' => $people]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'people' => ['required', 'array', 'min:1', 'max:100'],
            'people.*' => ['required', 'array:outsource_id,full_name'],
            'people.*.outsource_id' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Z0-9][A-Z0-9._-]*$/',
                'distinct',
            ],
            'people.*.full_name' => ['required', 'string', 'max:255'],
            'dry_run' => ['sometimes', 'boolean'],
        ];
    }
}
