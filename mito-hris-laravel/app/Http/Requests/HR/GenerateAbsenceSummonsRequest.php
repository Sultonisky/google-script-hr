<?php

namespace App\Http\Requests\HR;

use App\Enums\AbsenceSummonsLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateAbsenceSummonsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level' => ['required', Rule::enum(AbsenceSummonsLevel::class)],
            'working_days' => ['required_if:level,SPM2', 'nullable', 'integer', 'min:1', 'max:365'],
            'doc_date' => ['required', 'date_format:Y-m-d'],
            'absence_start_date' => ['required', 'date_format:Y-m-d'],
            'absence_end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:absence_start_date'],
            'absence_second_start_date' => ['nullable', 'required_with:absence_second_end_date', 'date_format:Y-m-d'],
            'absence_second_end_date' => ['nullable', 'required_with:absence_second_start_date', 'date_format:Y-m-d', 'after_or_equal:absence_second_start_date'],
            'meeting_date' => ['required', 'date_format:Y-m-d'],
            'meeting_time' => ['required', 'date_format:H:i'],
            'meeting_location' => ['required', 'string', 'max:1000'],
            'meeting_agenda' => ['required', 'string', 'max:255'],
            'company_entity' => ['required', 'string', Rule::in(array_keys(config('hris.mpr.companies', [])))],
        ];
    }

    public function messages(): array
    {
        return [
            'level.required' => 'Jenis panggilan wajib dipilih.',
            'level.enum' => 'Jenis panggilan tidak valid.',
            'working_days.required_if' => 'Jumlah hari kerja mangkir wajib diisi untuk Panggilan Kerja II.',
            'working_days.integer' => 'Jumlah hari kerja mangkir harus berupa angka bulat.',
            'working_days.min' => 'Jumlah hari kerja mangkir minimal 1 hari.',
            'working_days.max' => 'Jumlah hari kerja mangkir maksimal 365 hari.',
            'doc_date.required' => 'Tanggal surat wajib diisi.',
            'doc_date.date_format' => 'Tanggal surat tidak valid.',
            'absence_start_date.required' => 'Tanggal awal mangkir wajib diisi.',
            'absence_start_date.date_format' => 'Tanggal awal mangkir tidak valid.',
            'absence_end_date.required' => 'Tanggal akhir mangkir wajib diisi.',
            'absence_end_date.date_format' => 'Tanggal akhir mangkir tidak valid.',
            'absence_end_date.after_or_equal' => 'Tanggal akhir mangkir tidak boleh sebelum tanggal awal mangkir.',
            'absence_second_start_date.required_with' => 'Tanggal awal periode mangkir kedua wajib diisi.',
            'absence_second_start_date.date_format' => 'Tanggal awal periode mangkir kedua tidak valid.',
            'absence_second_end_date.required_with' => 'Tanggal akhir periode mangkir kedua wajib diisi.',
            'absence_second_end_date.date_format' => 'Tanggal akhir periode mangkir kedua tidak valid.',
            'absence_second_end_date.after_or_equal' => 'Tanggal akhir periode mangkir kedua tidak boleh sebelum tanggal awal.',
            'meeting_date.required' => 'Tanggal panggilan wajib diisi.',
            'meeting_date.date_format' => 'Tanggal panggilan tidak valid.',
            'meeting_time.required' => 'Waktu panggilan wajib diisi.',
            'meeting_time.date_format' => 'Format waktu panggilan tidak valid.',
            'meeting_location.required' => 'Tempat panggilan wajib diisi.',
            'meeting_location.max' => 'Tempat panggilan maksimal 1000 karakter.',
            'meeting_agenda.required' => 'Agenda panggilan wajib diisi.',
            'meeting_agenda.max' => 'Agenda panggilan maksimal 255 karakter.',
            'company_entity.required' => 'Entitas perusahaan wajib dipilih.',
            'company_entity.in' => 'Entitas perusahaan tidak valid.',
        ];
    }
}
