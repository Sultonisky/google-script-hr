<?php

namespace App\Http\Requests\HR;

use App\Enums\WarningLetterLevel;
use App\Services\WarningLetterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateWarningLetterRequest extends FormRequest
{
    private bool $hasStructuredRegulationInput = false;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $structuredKeys = ['regulation_type', 'article_number', 'paragraph_number', 'article_letter'];
        $this->hasStructuredRegulationInput = collect($structuredKeys)->contains(
            fn (string $key) => $this->exists($key)
        );

        if (! $this->hasStructuredRegulationInput) {
            return;
        }

        $referenceParts = [];
        $articleNumber = $this->normalizedPart('article_number');
        $paragraphNumber = $this->normalizedPart('paragraph_number');
        $articleLetter = $this->normalizedPart('article_letter');
        $regulationType = $this->normalizedPart('regulation_type');

        if ($articleNumber !== '') {
            $referenceParts[] = 'Pasal '.$articleNumber;
        }
        if ($paragraphNumber !== '') {
            $referenceParts[] = 'ayat ('.$paragraphNumber.')';
        }
        if ($articleLetter !== '') {
            $referenceParts[] = 'huruf '.strtolower($articleLetter);
        }
        if ($regulationType !== '') {
            $referenceParts[] = $regulationType;
        }

        $this->merge(['regulation_reference' => implode(' ', $referenceParts)]);
    }

    private function normalizedPart(string $key): string
    {
        $value = $this->input($key);

        return is_scalar($value) ? trim((string) $value) : '';
    }

    public function rules(): array
    {
        $usesFirstTemplate = in_array($this->input('level'), ['SP1', 'SP1T'], true);
        $hasStructuredRegulationValue = collect(['regulation_type', 'article_number', 'paragraph_number', 'article_letter'])
            ->contains(fn (string $key) => $this->normalizedPart($key) !== '');
        $requiresStructuredReference = $this->hasStructuredRegulationInput
            && ($usesFirstTemplate || $hasStructuredRegulationValue);

        return [
            'level' => ['required', Rule::enum(WarningLetterLevel::class)],
            'doc_date' => ['required', 'date'],
            'violation_category' => ['required_if:level,SP2,SP3', 'nullable', 'string', Rule::in(WarningLetterService::VIOLATION_CATEGORIES)],
            'violation_description' => ['required', 'string', 'min:10', 'max:2000'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:doc_date'],
            'regulation_reference' => [
                Rule::requiredIf(fn () => $usesFirstTemplate && ! $this->hasStructuredRegulationInput),
                'nullable',
                'string',
                'max:255',
            ],
            'regulation_type' => [
                Rule::requiredIf(fn () => $requiresStructuredReference),
                'nullable',
                'string',
                Rule::in(WarningLetterService::REGULATION_TYPES),
            ],
            'article_number' => [
                Rule::requiredIf(fn () => $requiresStructuredReference),
                'nullable',
                'integer',
                'min:1',
                'max:9999',
            ],
            'paragraph_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'article_letter' => ['nullable', 'string', 'regex:/^[a-zA-Z]$/'],
            'superior_position' => ['required_if:level,SP1,SP1T', 'nullable', 'string', 'max:150'],
            'corrective_actions' => ['nullable', 'string', 'max:1500'],
        ];
    }

    public function messages(): array
    {
        return [
            'level.required' => 'Tingkat Surat Peringatan wajib dipilih.',
            'level.enum' => 'Tingkat Surat Peringatan tidak valid.',
            'doc_date.required' => 'Tanggal surat wajib diisi.',
            'doc_date.date' => 'Tanggal surat tidak valid.',
            'violation_category.required_if' => 'Kategori pelanggaran wajib dipilih untuk SP-2 dan SP-3.',
            'violation_category.in' => 'Kategori pelanggaran tidak valid.',
            'violation_description.required' => 'Uraian pelanggaran wajib diisi.',
            'violation_description.min' => 'Uraian pelanggaran minimal 10 karakter.',
            'violation_description.max' => 'Uraian pelanggaran maksimal 2000 karakter.',
            'incident_date.date' => 'Tanggal kejadian tidak valid.',
            'incident_date.before_or_equal' => 'Tanggal kejadian tidak boleh setelah tanggal surat.',
            'regulation_reference.max' => 'Dasar ketentuan maksimal 255 karakter.',
            'regulation_reference.required_if' => 'Pasal atau dasar ketentuan wajib diisi untuk SP-1 dan SP-1 & Terakhir.',
            'regulation_type.required' => 'Jenis peraturan wajib dipilih untuk SP-1 dan SP-1 & Terakhir.',
            'regulation_type.in' => 'Jenis peraturan tidak valid.',
            'article_number.required' => 'Nomor pasal wajib diisi untuk SP-1 dan SP-1 & Terakhir.',
            'article_number.integer' => 'Nomor pasal harus berupa angka.',
            'article_number.min' => 'Nomor pasal minimal 1.',
            'article_number.max' => 'Nomor pasal maksimal 9999.',
            'paragraph_number.integer' => 'Nomor ayat harus berupa angka.',
            'paragraph_number.min' => 'Nomor ayat minimal 1.',
            'paragraph_number.max' => 'Nomor ayat maksimal 999.',
            'article_letter.regex' => 'Huruf pasal harus satu huruf, misalnya a atau e.',
            'regulation_type.prohibited' => 'Jangan kirim jenis peraturan bersama teks dasar ketentuan.',
            'article_number.prohibited' => 'Jangan kirim nomor pasal bersama teks dasar ketentuan.',
            'paragraph_number.prohibited' => 'Jangan kirim nomor ayat bersama teks dasar ketentuan.',
            'article_letter.prohibited' => 'Jangan kirim huruf pasal bersama teks dasar ketentuan.',
            'superior_position.required_if' => 'Jabatan atasan wajib diisi untuk SP-1 dan SP-1 & Terakhir.',
            'superior_position.max' => 'Jabatan atasan maksimal 150 karakter.',
            'corrective_actions.max' => 'Tindakan perbaikan maksimal 1500 karakter.',
        ];
    }
}
