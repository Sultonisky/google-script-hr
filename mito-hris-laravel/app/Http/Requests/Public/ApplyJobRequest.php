<?php

namespace App\Http\Requests\Public;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ApplyJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nik = $this->input('nik');
        $email = $this->input('email');
        $phone = $this->input('nomor_telepon');
        $name = $this->input('nama_lengkap');

        $this->merge([
            'nik' => is_string($nik) ? preg_replace('/\D+/', '', $nik) : $nik,
            'email' => is_string($email) ? strtolower(trim($email)) : $email,
            'nomor_telepon' => is_string($phone) ? preg_replace('/\D+/', '', $phone) : $phone,
            'nama_lengkap' => is_string($name) ? trim(preg_replace('/\s+/', ' ', $name)) : $name,
        ]);
    }

    public function rules(): array
    {
        return [
            'posisi_dilamar'        => ['required', 'string', 'max:255'],
            'nama_lengkap'          => ['required', 'string', 'min:3', 'max:255', 'regex:/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u'],
            'nik'                   => ['required', 'digits:16'],
            'email'                 => ['required', 'email:filter', 'max:255'],
            'nomor_telepon'         => ['required', 'string', 'min:9', 'max:13', 'regex:/^[0-9]+$/'],
            'jenis_kelamin'         => ['required', 'string', 'in:Laki-laki,Perempuan'],
            'birth_date'            => ['required', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'usia'                  => ['required', 'integer', 'min:17', 'max:100'],
            'golongan_darah'        => ['required', 'in:A,B,AB,O'],
            'marital_status'        => ['required', 'string', 'in:Belum Menikah,Menikah,Cerai'],
            'alamat_domisili'       => ['required', 'string', 'min:5', 'max:500'],
            'provinsi'              => ['required', 'string', 'max:80'],
            'kota'                  => ['required', 'string', 'max:80'],
            'kota_nama'             => ['nullable', 'string', 'max:120'],
            'kecamatan'             => ['required_without:kecamatan_manual', 'nullable', 'string', 'max:120'],
            'kecamatan_manual'      => ['required_without:kecamatan', 'nullable', 'string', 'max:120'],
            'pendidikan_terakhir'   => ['required', 'string', 'max:80'],
            'pengalaman_kerja'      => ['required', 'string', 'max:80'],
            'perusahaan_terakhir'   => ['nullable', 'string', 'max:255'],
            'status_bekerja'        => ['required', 'string', 'max:80'],
            'kesediaan_bergabung'   => ['required', 'string', 'max:80'],
            'ekspektasi_gaji'       => ['required', 'string', 'max:40'],
            'sumber_informasi'      => ['required', 'string', 'max:120'],
            'agreement'             => ['required', 'in:1'],
            'cv_link'               => ['nullable', 'url', 'max:500'],
            'consent_timestamp'     => ['nullable', 'string', 'max:80'],
            'consent_device'        => ['nullable', 'string', 'max:120'],
            'consent_latitude'      => ['nullable', 'string', 'max:40'],
            'consent_longitude'     => ['nullable', 'string', 'max:40'],
            'consent_location'      => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('birth_date')) {
                return;
            }

            $birth = $this->input('birth_date');
            if (!is_string($birth) || $birth === '') {
                return;
            }

            try {
                $date = Carbon::createFromFormat('Y-m-d', $birth, 'Asia/Jakarta')->startOfDay();
            } catch (\Throwable $e) {
                return;
            }

            if ($date->age < 17) {
                $validator->errors()->add('birth_date', 'Usia minimal untuk mendaftar adalah 17 tahun.');
            }
        });
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
            'nomor_telepon.regex'     => 'Nomor HP hanya boleh berisi angka.',
            'nomor_telepon.min'       => 'Nomor HP terlalu pendek.',
            'jenis_kelamin.required'  => 'Jenis kelamin wajib dipilih.',
            'birth_date.required'     => 'Tanggal lahir wajib diisi.',
            'birth_date.date_format'  => 'Tanggal lahir harus berformat YYYY-MM-DD.',
            'birth_date.after_or_equal' => 'Tahun lahir tidak valid (minimal 1900).',
            'birth_date.before_or_equal' => 'Tanggal lahir tidak boleh tanggal yang akan datang.',
            'usia.required'           => 'Usia wajib diisi.',
            'usia.min'                => 'Usia minimal untuk mendaftar adalah 17 tahun.',
            'golongan_darah.required' => 'Golongan darah wajib dipilih.',
            'golongan_darah.in'       => 'Golongan darah harus A, B, AB, atau O.',
            'marital_status.required' => 'Status pernikahan wajib dipilih.',
            'alamat_domisili.required'=> 'Alamat domisili wajib diisi.',
            'provinsi.required'       => 'Provinsi wajib dipilih.',
            'kota.required'           => 'Kabupaten/Kota wajib dipilih.',
            'kecamatan.required_without' => 'Kecamatan wajib diisi.',
            'kecamatan_manual.required_without' => 'Kecamatan wajib diisi.',
            'pendidikan_terakhir.required' => 'Pendidikan terakhir wajib dipilih.',
            'pengalaman_kerja.required' => 'Pengalaman kerja wajib dipilih.',
            'status_bekerja.required' => 'Status bekerja saat ini wajib dipilih.',
            'kesediaan_bergabung.required' => 'Kesediaan bergabung wajib dipilih.',
            'ekspektasi_gaji.required' => 'Ekspektasi gaji wajib diisi.',
            'sumber_informasi.required' => 'Sumber informasi wajib dipilih.',
            'agreement.required'      => 'Anda harus menyetujui pernyataan keabsahan data untuk melanjutkan.',
            'agreement.in'            => 'Anda harus menyetujui pernyataan keabsahan data untuk melanjutkan.',
            'cv_link.url'             => 'Tautan CV harus berupa URL yang valid (dimulai dengan http:// atau https://).',
        ];
    }
}
