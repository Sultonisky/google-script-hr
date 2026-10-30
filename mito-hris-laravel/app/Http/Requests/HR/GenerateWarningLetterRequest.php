<?php

namespace App\Http\Requests\HR;

use App\Enums\WarningLetterLevel;
use App\Services\WarningLetterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateWarningLetterRequest extends FormRequest
{
    private const REFERENCE_KEYS = [
        'regulation_type',
        'chapter_number',
        'chapter_title',
        'article_number',
        'article_title',
        'paragraph_number',
        'article_letter',
        'subparagraph_number',
        'article_text',
    ];

    private bool $hasStructuredRegulationInput = false;
    private bool $hasRepeatedRegulationInput = false;
    private bool $hasViolationInput = false;
    private array $structuredRegulationRows = [];
    private array $violationRows = [];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('violations')) {
            $this->prepareViolationInput();

            return;
        }

        $this->hasRepeatedRegulationInput = $this->exists('regulation_references');
        $structuredKeys = [
            'regulation_type',
            'chapter_number',
            'chapter_title',
            'article_number',
            'article_title',
            'paragraph_number',
            'article_letter',
            'subparagraph_number',
        ];
        $hasLegacyStructuredInput = collect($structuredKeys)->contains(
            fn (string $key) => $this->exists($key)
        );
        $this->hasStructuredRegulationInput = $this->hasRepeatedRegulationInput || $hasLegacyStructuredInput;
        // SP-1 hanya mencantumkan pasal/ayat/huruf; jenis peraturan diabaikan.
        $omitRegulationType = $this->omitsRegulationType();
        if ($omitRegulationType && $this->exists('regulation_type')) {
            $this->merge(['regulation_type' => null]);
        }

        if ($this->hasRepeatedRegulationInput) {
            $inputRows = $this->input('regulation_references');
            $this->structuredRegulationRows = is_array($inputRows)
                ? collect($inputRows)
                    ->filter(fn ($row) => is_array($row))
                    ->map(fn (array $row) => $omitRegulationType ? array_merge($row, ['regulation_type' => null]) : $row)
                    ->filter(fn (array $row) => collect($row)->contains(fn ($value) => $this->isFilledScalar($value)))
                    ->values()
                    ->all()
                : [];
        } elseif ($hasLegacyStructuredInput) {
            $this->structuredRegulationRows = [[
                'regulation_type' => $omitRegulationType ? null : $this->input('regulation_type'),
                'chapter_number' => $this->input('chapter_number'),
                'chapter_title' => $this->input('chapter_title'),
                'article_number' => $this->input('article_number'),
                'article_title' => $this->input('article_title'),
                'paragraph_number' => $this->input('paragraph_number'),
                'article_letter' => $this->input('article_letter'),
                'subparagraph_number' => $this->input('subparagraph_number'),
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

    /**
     * SP-1 & Terakhir: tiap pelanggaran memiliki uraian dan satu/lebih pasal beserta bunyinya.
     */
    private function prepareViolationInput(): void
    {
        $this->hasViolationInput = true;
        $this->hasStructuredRegulationInput = true;

        $inputRows = $this->input('violations');
        $this->violationRows = collect(is_array($inputRows) ? $inputRows : [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) {
                $references = collect(is_array($row['references'] ?? null) ? $row['references'] : [])
                    ->filter(fn ($ref) => is_array($ref) && collect(self::REFERENCE_KEYS)->contains(
                        fn (string $key) => $this->normalizedPart($ref, $key) !== ''
                    ))
                    ->map(fn (array $ref) => collect(self::REFERENCE_KEYS)
                        ->mapWithKeys(fn (string $key) => [$key => $this->normalizedPart($ref, $key)])
                        ->all())
                    ->values()
                    ->all();

                return [
                    'description' => $this->normalizedPart($row, 'description'),
                    'references' => $references,
                ];
            })
            ->filter(fn (array $row) => $row['description'] !== '' || $row['references'] !== [])
            ->values()
            ->all();

        $this->merge([
            'violations' => $this->violationRows,
            'violation_description' => collect($this->violationRows)->pluck('description')->filter()->implode("\n"),
            'regulation_reference' => collect($this->violationRows)
                ->flatMap(fn (array $row) => $row['references'])
                ->map(fn (array $ref) => $this->formatRegulationRow($ref))
                ->filter()
                ->unique()
                ->implode('; '),
        ]);
    }

    /**
     * SP-1, SP-2, dan SP-3 hanya mencantumkan pasal/ayat/huruf, tanpa jenis peraturan.
     */
    private function omitsRegulationType(): bool
    {
        $level = WarningLetterLevel::tryFrom((string) $this->input('level'));

        return $level !== null && ! $level->usesViolationDetails();
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
        $chapterNumber = $this->normalizedPart($row, 'chapter_number');
        $chapterTitle = $this->normalizedPart($row, 'chapter_title');
        $articleNumber = $this->normalizedPart($row, 'article_number');
        $articleTitle = $this->normalizedPart($row, 'article_title');
        $paragraphNumber = $this->normalizedPart($row, 'paragraph_number');
        $articleLetter = $this->normalizedPart($row, 'article_letter');
        $subparagraphNumber = $this->normalizedPart($row, 'subparagraph_number');
        $regulationType = $this->normalizedPart($row, 'regulation_type');

        if ($chapterNumber !== '') {
            $parts[] = 'Bab '.$chapterNumber.($chapterTitle !== '' ? ' ('.$chapterTitle.')' : '');
        }
        if ($articleNumber !== '') {
            $parts[] = 'Pasal '.$articleNumber.($articleTitle !== '' ? ' ('.$articleTitle.')' : '');
        }
        if ($paragraphNumber !== '') {
            $parts[] = ctype_alpha($paragraphNumber)
                ? 'huruf '.strtolower($paragraphNumber)
                : 'ayat ('.$paragraphNumber.')';
        }
        if ($articleLetter !== '') {
            $parts[] = (ctype_alpha($paragraphNumber) ? 'subhuruf ' : 'huruf ').strtolower($articleLetter);
        }
        if ($subparagraphNumber !== '') {
            $parts[] = 'nomor '.$subparagraphNumber;
        }
        if ($regulationType !== '') {
            $parts[] = $regulationType;
        }

        return implode(' ', $parts);
    }

    public function rules(): array
    {
        $usesFirstTemplate = WarningLetterLevel::tryFrom((string) $this->input('level')) !== null;
        $rules = [
            'level' => ['required', Rule::enum(WarningLetterLevel::class)],
            'doc_date' => ['required', 'date'],
            'violation_category' => ['nullable', 'string', Rule::in(WarningLetterService::VIOLATION_CATEGORIES)],
            'violation_description' => ['required', 'string', 'min:10', 'max:'.($this->hasViolationInput ? 11000 : 2000)],
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
                Rule::requiredIf(fn () => $usesFirstTemplate && ! $this->omitsRegulationType() && $this->hasStructuredRegulationInput && ! $this->hasRepeatedRegulationInput && ! $this->hasViolationInput),
                'nullable',
                'string',
                Rule::in(WarningLetterService::REGULATION_TYPES),
            ],
            'article_number' => [
                Rule::requiredIf(fn () => $usesFirstTemplate && $this->hasStructuredRegulationInput && ! $this->hasRepeatedRegulationInput && ! $this->hasViolationInput),
                'nullable',
                'integer',
                'min:1',
                'max:9999',
            ],
            'chapter_number' => ['nullable', 'string', 'max:20'],
            'chapter_title' => ['nullable', 'string', 'max:200'],
            'article_title' => ['nullable', 'string', 'max:200'],
            'paragraph_number' => ['nullable', 'regex:/^(?:[1-9]\d{0,2}|[a-zA-Z])$/'],
            'article_letter' => ['nullable', 'string', 'regex:/^[a-zA-Z]$/'],
            'subparagraph_number' => ['nullable', 'regex:/^[1-9]\d{0,2}$/'],
            'superior_name' => ['nullable', 'string', 'max:150'],
            'superior_position' => ['required_if:level,SP1,SP1T,SP2,SP3', 'nullable', 'string', 'max:150'],
            'corrective_actions' => ['nullable', 'string', 'max:1500'],
            'points' => ['required', 'integer', 'min:1', 'max:999'],
        ];

        if ($this->hasViolationInput) {
            $rules['violations'] = ['required', 'array', 'min:1', 'max:10'];
            $rules['violations.*.description'] = ['required', 'string', 'min:10', 'max:1000'];
            $rules['violations.*.references'] = ['required', 'array', 'min:1', 'max:5'];
            $rules['violations.*.references.*.regulation_type'] = ['required', 'string', Rule::in(WarningLetterService::REGULATION_TYPES)];
            $rules['violations.*.references.*.chapter_number'] = ['nullable', 'string', 'max:20'];
            $rules['violations.*.references.*.chapter_title'] = ['nullable', 'string', 'max:200'];
            $rules['violations.*.references.*.article_number'] = ['required', 'integer', 'min:1', 'max:9999'];
            $rules['violations.*.references.*.article_title'] = ['nullable', 'string', 'max:200'];
            $rules['violations.*.references.*.paragraph_number'] = ['nullable', 'regex:/^(?:[1-9]\d{0,2}|[a-zA-Z])$/'];
            $rules['violations.*.references.*.article_letter'] = ['nullable', 'string', 'regex:/^[a-zA-Z]$/'];
            $rules['violations.*.references.*.subparagraph_number'] = ['nullable', 'regex:/^[1-9]\d{0,2}$/'];
            $rules['violations.*.references.*.article_text'] = ['nullable', 'string', 'max:10000'];
        }

        if ($this->hasRepeatedRegulationInput) {
            foreach ($this->structuredRegulationRows as $index => $row) {
                $rowStarted = collect([
                    'regulation_type',
                    'chapter_number',
                    'chapter_title',
                    'article_number',
                    'article_title',
                    'paragraph_number',
                    'article_letter',
                    'subparagraph_number',
                ])
                    ->contains(fn (string $key) => $this->normalizedPart($row, $key) !== '');
                $required = $usesFirstTemplate || $rowStarted;

                $rules["regulation_references.{$index}.regulation_type"] = [
                    Rule::requiredIf($required && ! $this->omitsRegulationType()),
                    'nullable',
                    'string',
                    Rule::in(WarningLetterService::REGULATION_TYPES),
                ];
                $rules["regulation_references.{$index}.chapter_number"] = ['nullable', 'string', 'max:20'];
                $rules["regulation_references.{$index}.chapter_title"] = ['nullable', 'string', 'max:200'];
                $rules["regulation_references.{$index}.article_number"] = [
                    Rule::requiredIf($required),
                    'nullable',
                    'integer',
                    'min:1',
                    'max:9999',
                ];
                $rules["regulation_references.{$index}.article_title"] = ['nullable', 'string', 'max:200'];
                $rules["regulation_references.{$index}.paragraph_number"] = ['nullable', 'regex:/^(?:[1-9]\d{0,2}|[a-zA-Z])$/'];
                $rules["regulation_references.{$index}.article_letter"] = ['nullable', 'string', 'regex:/^[a-zA-Z]$/'];
                $rules["regulation_references.{$index}.subparagraph_number"] = ['nullable', 'regex:/^[1-9]\d{0,2}$/'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'points.required' => 'Poin wajib diisi.',
            'points.integer' => 'Poin harus berupa angka bulat.',
            'points.min' => 'Poin minimal 1.',
            'points.max' => 'Poin maksimal 999.',
            'level.required' => 'Tingkat Surat Peringatan wajib dipilih.',
            'level.enum' => 'Tingkat Surat Peringatan tidak valid.',
            'doc_date.required' => 'Tanggal surat wajib diisi.',
            'doc_date.date' => 'Tanggal surat tidak valid.',
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
            'regulation_reference.required_if' => 'Pasal yang dilanggar wajib diisi.',
            'regulation_type.required' => 'Jenis peraturan wajib dipilih untuk SP-1 & Terakhir.',
            'regulation_type.in' => 'Jenis peraturan tidak valid.',
            'article_number.required' => 'Nomor pasal wajib diisi.',
            'article_number.integer' => 'Nomor pasal harus berupa angka.',
            'article_number.min' => 'Nomor pasal minimal 1.',
            'article_number.max' => 'Nomor pasal maksimal 9999.',
            'paragraph_number.regex' => 'Nomor ayat harus berupa angka atau satu huruf, misalnya 1 atau a.',
            'article_letter.regex' => 'Huruf pasal harus satu huruf, misalnya a atau e.',
            'subparagraph_number.regex' => 'Nomor subbutir harus berupa angka positif.',
            'regulation_type.prohibited' => 'Jangan kirim jenis peraturan bersama teks dasar ketentuan.',
            'article_number.prohibited' => 'Jangan kirim nomor pasal bersama teks dasar ketentuan.',
            'paragraph_number.prohibited' => 'Jangan kirim nomor ayat bersama teks dasar ketentuan.',
            'article_letter.prohibited' => 'Jangan kirim huruf pasal bersama teks dasar ketentuan.',
            'superior_position.required_if' => 'Jabatan atasan wajib diisi.',
            'superior_position.max' => 'Jabatan atasan maksimal 150 karakter.',
            'superior_name.max' => 'Nama atasan maksimal 150 karakter.',
            'corrective_actions.max' => 'Tindakan perbaikan maksimal 1500 karakter.',
            'violations.required' => 'Tambahkan minimal satu pelanggaran.',
            'violations.array' => 'Format rincian pelanggaran tidak valid.',
            'violations.min' => 'Tambahkan minimal satu pelanggaran.',
            'violations.max' => 'Maksimal 10 pelanggaran dapat ditambahkan.',
            'violations.*.description.required' => 'Uraian pelanggaran ke-:position wajib diisi.',
            'violations.*.description.min' => 'Uraian pelanggaran ke-:position minimal 10 karakter.',
            'violations.*.description.max' => 'Uraian pelanggaran ke-:position maksimal 1000 karakter.',
            'violations.*.references.required' => 'Pelanggaran ke-:position wajib memiliki minimal satu pasal.',
            'violations.*.references.min' => 'Pelanggaran ke-:position wajib memiliki minimal satu pasal.',
            'violations.*.references.max' => 'Pelanggaran ke-:position maksimal memiliki 5 pasal.',
            'violations.*.references.*.regulation_type.required' => 'Pilih jenis peraturan pada pelanggaran ke-:position, pasal ke-:second-position.',
            'violations.*.references.*.regulation_type.in' => 'Jenis peraturan pada pelanggaran ke-:position, pasal ke-:second-position tidak valid.',
            'violations.*.references.*.article_number.required' => 'Isi nomor pasal pada pelanggaran ke-:position, pasal ke-:second-position.',
            'violations.*.references.*.article_number.integer' => 'Nomor pasal pada pelanggaran ke-:position harus berupa angka.',
            'violations.*.references.*.article_number.min' => 'Nomor pasal pada pelanggaran ke-:position minimal 1.',
            'violations.*.references.*.article_number.max' => 'Nomor pasal pada pelanggaran ke-:position maksimal 9999.',
            'violations.*.references.*.paragraph_number.integer' => 'Nomor ayat pada pelanggaran ke-:position harus berupa angka.',
            'violations.*.references.*.paragraph_number.min' => 'Nomor ayat pada pelanggaran ke-:position minimal 1.',
            'violations.*.references.*.paragraph_number.max' => 'Nomor ayat pada pelanggaran ke-:position maksimal 999.',
            'violations.*.references.*.article_letter.regex' => 'Huruf pasal pada pelanggaran ke-:position harus satu huruf, misalnya a atau e.',
            'violations.*.references.*.article_text.max' => 'Bunyi pasal pada pelanggaran ke-:position maksimal 10000 karakter.',
        ];
    }
}
