<?php

namespace App\Http\Requests\HR;

use App\Enums\WarningLetterLevel;
use App\Services\WarningLetterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateWarningLetterRequest extends FormRequest
{
    private bool $hasStructuredRegulationInput = false;
    private bool $hasRepeatedRegulationInput = false;
    private array $structuredRegulationRows = [];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->hasRepeatedRegulationInput = $this->exists('regulation_references');
        $structuredKeys = ['regulation_type', 'article_number', 'paragraph_number', 'article_letter'];
        $hasLegacyStructuredInput = collect($structuredKeys)->contains(
            fn (string $key) => $this->exists($key)
        );
        $this->hasStructuredRegulationInput = $this->hasRepeatedRegulationInput || $hasLegacyStructuredInput;

        if ($this->hasRepeatedRegulationInput) {
            $inputRows = $this->input('regulation_references');
            $this->structuredRegulationRows = is_array($inputRows)
                ? collect($inputRows)
                    ->filter(fn ($row) => is_array($row) && collect($row)->contains(fn ($value) => $this->isFilledScalar($value)))
                    ->values()
                    ->all()
                : [];
        } elseif ($hasLegacyStructuredInput) {
            $this->structuredRegulationRows = [[
                'regulation_type' => $this->input('regulation_type'),
                'article_number' => $this->input('article_number'),
                'paragraph_number' => $this->input('paragraph_number'),
                'article_letter' => $this->input('article_letter'),
            ]];
        }

        if ($this->hasStructuredRegulationInput) {
            $this->merge([
                'regulation_references' => $this->structuredRegulationRows,
                'regulation_reference' => collect($this->structuredRegulationRows)
                    ->map(fn (array $row) => $this->formatRegulationRow($row))
                    ->filter()
                    ->implode('; '),
            ]);
        }
    }

    private function isFilledScalar(mixed $value): bool
    {
        return is_scalar($value) && trim((string) $value) !== '';
    }

    private function normalizedPart(array $row, string $key): string
    {
        $value = $row[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function formatRegulationRow(array $row): string
    {
        $parts = [];
        $articleNumber = $this->normalizedPart($row, 'article_number');
        $paragraphNumber = $this->normalizedPart($row, 'paragraph_number');
        $articleLetter = $this->normalizedPart($row, 'article_letter');
        $regulationType = $this->normalizedPart($row, 'regulation_type');

        if ($articleNumber !== '') {
            $parts[] = 'Pasal '.$articleNumber;
        }
        if ($paragraphNumber !== '') {
            $parts[] = 'ayat ('.$paragraphNumber.')';
        }
        if ($articleLetter !== '') {
            $parts[] = 'huruf '.strtolower($articleLetter);
        }
        if ($regulationType !== '') {
            $parts[] = $regulationType;
        }

        return implode(' ', $parts);
    }

    public function rules(): array
    {
        $usesFirstTemplate = in_array($this->input('level'), ['SP1', 'SP1T'], true);
        $rules = [
            'level' => ['required', Rule::enum(WarningLetterLevel::class)],
            'doc_date' => ['required', 'date'],
            'violation_category' => ['required_if:level,SP2,SP3', 'nullable', 'string', Rule::in(WarningLetterService::VIOLATION_CATEGORIES)],
            'violation_description' => ['required', 'string', 'min:10', 'max:2000'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:doc_date'],
            'regulation_reference' => [
                Rule::requiredIf(fn () => $usesFirstTemplate && ! $this->hasStructuredRegulationInput),
                'nullable',
                'string',
                'max:2000',
            ],
            'regulation_references' => [
                Rule::requiredIf(fn () => $usesFirstTemplate && $this->hasRepeatedRegulationInput),
                'sometimes',
                'array',
                ...($usesFirstTemplate && $this->hasRepeatedRegulationInput ? ['min:1'] : []),
                'max:10',
            ],
            'regulation_type' => [
                Rule::requiredIf(fn () => $usesFirstTemplate && $this->hasStructuredRegulationInput && ! $this->hasRepeatedRegulationInput),
                'nullable',
                'string',
                Rule::in(WarningLetterService::REGULATION_TYPES),
            ],
            'article_number' => [
                Rule::requiredIf(fn () => $usesFirstTemplate && $this->hasStructuredRegulationInput && ! $this->hasRepeatedRegulationInput),
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

        if ($this->hasRepeatedRegulationInput) {
            foreach ($this->structuredRegulationRows as $index => $row) {
                $rowStarted = collect(['regulation_type', 'article_number', 'paragraph_number', 'article_letter'])
                    ->contains(fn (string $key) => $this->normalizedPart($row, $key) !== '');
                $required = $usesFirstTemplate || $rowStarted;

                $rules["regulation_references.{$index}.regulation_type"] = [
                    Rule::requiredIf($required),
                    'nullable',
                    'string',
                    Rule::in(WarningLetterService::REGULATION_TYPES),
                ];
                $rules["regulation_references.{$index}.article_number"] = [
                    Rule::requiredIf($required),
                    'nullable',
                    'integer',
                    'min:1',
                    'max:9999',
                ];
                $rules["regulation_references.{$index}.paragraph_number"] = ['nullable', 'integer', 'min:1', 'max:999'];
                $rules["regulation_references.{$index}.article_letter"] = ['nullable', 'string', 'regex:/^[a-zA-Z]$/'];
            }
        }

        return $rules;
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
            'regulation_reference.max' => 'Dasar ketentuan maksimal 2000 karakter.',
            'regulation_references.array' => 'Format dasar ketentuan tidak valid.',
            'regulation_references.min' => 'Tambahkan minimal satu dasar ketentuan.',
            'regulation_references.max' => 'Maksimal 10 dasar ketentuan dapat ditambahkan.',
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
