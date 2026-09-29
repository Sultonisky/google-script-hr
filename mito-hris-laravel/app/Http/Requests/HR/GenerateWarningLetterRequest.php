<?php

namespace App\Http\Requests\HR;

use App\Enums\WarningLetterLevel;
use App\Services\WarningLetterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateWarningLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level' => ['required', Rule::enum(WarningLetterLevel::class)],
            'doc_date' => ['required', 'date'],
            'violation_category' => ['required', 'string', Rule::in(WarningLetterService::VIOLATION_CATEGORIES)],
            'violation_description' => ['required', 'string', 'min:10', 'max:2000'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:doc_date'],
            'regulation_reference' => ['nullable', 'string', 'max:255'],
            'corrective_actions' => ['nullable', 'string', 'max:1500'],
            'validity_months' => ['required', 'integer', 'min:1', 'max:' . WarningLetterService::MAX_VALIDITY_MONTHS],
        ];
    }

    public function messages(): array
    {
        return [
            'level.required' => 'Tingkat Surat Peringatan wajib dipilih.',
            'level.enum' => 'Tingkat Surat Peringatan tidak valid.',
            'doc_date.required' => 'Tanggal surat wajib diisi.',
            'doc_date.date' => 'Tanggal surat tidak valid.',
            'violation_category.required' => 'Kategori pelanggaran wajib dipilih.',
            'violation_category.in' => 'Kategori pelanggaran tidak valid.',
            'violation_description.required' => 'Uraian pelanggaran wajib diisi.',
            'violation_description.min' => 'Uraian pelanggaran minimal 10 karakter.',
            'violation_description.max' => 'Uraian pelanggaran maksimal 2000 karakter.',
            'incident_date.date' => 'Tanggal kejadian tidak valid.',
            'incident_date.before_or_equal' => 'Tanggal kejadian tidak boleh setelah tanggal surat.',
            'regulation_reference.max' => 'Dasar ketentuan maksimal 255 karakter.',
            'corrective_actions.max' => 'Tindakan perbaikan maksimal 1500 karakter.',
            'validity_months.required' => 'Masa berlaku wajib dipilih.',
            'validity_months.integer' => 'Masa berlaku tidak valid.',
            'validity_months.min' => 'Masa berlaku minimal 1 bulan.',
            'validity_months.max' => 'Masa berlaku maksimal ' . WarningLetterService::MAX_VALIDITY_MONTHS . ' bulan.',
        ];
    }
}
