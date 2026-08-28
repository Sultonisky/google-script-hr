<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class ApplyJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Field names match apply.blade.php form inputs
            'posisi_dilamar'        => ['required', 'string', 'max:255'],
            'nama_lengkap'          => ['required', 'string', 'min:3', 'max:255', 'regex:/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u'],
            'nik'                   => ['required', 'digits:16'],
            'email'                 => ['required', 'email', 'max:255'],
            'nomor_telepon'         => ['required', 'string', 'max:30'],
            'jenis_kelamin'         => ['nullable', 'string', 'in:Laki-laki,Perempuan'],
            'birth_date'            => ['nullable', 'string', 'max:10'], // DD/MM/YYYY format from JS auto-slash
            'usia'                  => ['nullable', 'numeric'],
            'marital_status'        => ['nullable', 'string'],
            'alamat_domisili'       => ['nullable', 'string'],
            'provinsi'              => ['nullable', 'string'],
            'kota'                  => ['nullable', 'string'],
            'kecamatan'             => ['nullable', 'string'],
            'kecamatan_manual'      => ['nullable', 'string'],
            'pendidikan_terakhir'   => ['nullable', 'string'],
            'pengalaman_kerja'      => ['nullable', 'string'],
            'perusahaan_terakhir'   => ['nullable', 'string'],
            'status_bekerja'        => ['nullable', 'string'],
            'kesediaan_bergabung'   => ['nullable', 'string'],
            'ekspektasi_gaji'       => ['nullable', 'string'],
            'sumber_informasi'      => ['nullable', 'string'],
            'agreement'             => ['required', 'in:1'],
            'cv_link'               => ['nullable', 'url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'posisi_dilamar.required' => 'Posisi yang dilamar wajib dipilih.',
            'nama_lengkap.required'   => 'Nama lengkap wajib diisi sesuai KTP.',
            'nama_lengkap.regex'      => 'Nama lengkap hanya boleh berisi huruf dan spasi.',
            'nik.required'            => 'Nomor Induk Kependudukan (NIK) wajib diisi.',
            'nik.digits'              => 'NIK harus berupa 16 digit angka.',
            'email.required'          => 'Alamat email wajib diisi.',
            'email.email'             => 'Format alamat email tidak valid.',
            'nomor_telepon.required'  => 'Nomor HP / WhatsApp wajib diisi.',
            'agreement.required'      => 'Anda harus menyetujui pernyataan keabsahan data untuk melanjutkan.',
            'agreement.in'            => 'Anda harus menyetujui pernyataan keabsahan data untuk melanjutkan.',
            'cv_link.url'             => 'Tautan CV harus berupa URL yang valid (dimulai dengan http:// atau https://).',
        ];
    }
}
