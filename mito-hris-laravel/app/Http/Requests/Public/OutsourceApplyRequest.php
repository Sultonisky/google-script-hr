<?php

namespace App\Http\Requests\Public;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class OutsourceApplyRequest extends FormRequest
{
    private const NAME_REGEX = '/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u';

    private const MODERATE_REGEX = '/^[\p{L}0-9 .,&\-\/]+$/u';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $digitKeys = [
            'nik',
            'nomor_telepon',
            'nomor_rekening',
            'npwp',
            'bpjs_ketenagakerjaan',
            'bpjs_kesehatan',
        ];
        $nameKeys = [
            'nama_lengkap',
            'tempat_lahir',
            'atasan_langsung',
            'atasan_tidak_langsung',
            'nama_pemilik_rekening',
        ];
        $textKeys = [
            'vendor_outsource',
            'divisi',
            'departemen',
            'cost_center',
            'lokasi_kerja',
            'kecamatan',
            'kecamatan_manual',
            'alamat_ktp',
            'alamat_domisili',
            'kota_nama',
        ];
        $emailKeys = ['email_pribadi', 'email_kantor'];

        $merged = [];

        foreach ($digitKeys as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                $merged[$key] = preg_replace('/\D+/', '', $value);
            }
        }

        foreach (array_merge($nameKeys, $textKeys) as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                $merged[$key] = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
            }
        }

        foreach ($emailKeys as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                $merged[$key] = strtolower(trim($value));
            }
        }

        $merged['status_karyawan'] = 'Outsource';

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'min:3', 'max:255', 'regex:' . self::NAME_REGEX],
            'nik' => ['required', 'digits:16'],
            'birth_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:1900-01-01',
                'before_or_equal:today',
            ],
            'tempat_lahir' => ['required', 'string', 'min:3', 'max:120', 'regex:' . self::NAME_REGEX],
            'usia' => ['nullable', 'integer', 'min:17', 'max:100'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'agama' => ['required', 'in:Islam,Kristen,Katolik,Hindu,Buddha,Khonghucu'],
            'golongan_darah' => ['required', 'in:A,B,AB,O'],
            'status_pernikahan' => ['required', 'in:Belum Menikah,Menikah,Cerai'],
            'email_pribadi' => ['required', 'email:filter', 'max:255'],
            'email_kantor' => ['required', 'email:filter', 'max:255'],
            'nomor_telepon' => ['required', 'digits_between:9,13', 'regex:/^8[0-9]{6,12}$/'],
            'provinsi' => ['required', 'string', 'max:80'],
            'kota' => ['required', 'string', 'max:80'],
            'kota_nama' => ['nullable', 'string', 'max:120'],
            'kecamatan' => ['required_without:kecamatan_manual', 'nullable', 'string', 'max:120'],
            'kecamatan_manual' => ['required_without:kecamatan', 'nullable', 'string', 'min:3', 'max:120', 'regex:' . self::MODERATE_REGEX],
            'alamat_ktp' => ['required', 'string', 'min:5', 'max:500'],
            'alamat_domisili' => ['required', 'string', 'min:5', 'max:500'],
            'cabang_penempatan' => ['required', 'in:PT Mahakarya Sukses Indonesia,PT Stein Perkasa Internasional,PT Perkasa Injeksi Indonesia,PT Mitra Elektro Perkasa'],
            'vendor_outsource' => ['required', 'string', 'min:3', 'max:120', 'regex:' . self::MODERATE_REGEX],
            'divisi' => ['required', 'string', 'min:2', 'max:80', 'regex:' . self::MODERATE_REGEX],
            'departemen' => ['required', 'string', 'min:2', 'max:80', 'regex:' . self::MODERATE_REGEX],
            'area_kerja' => ['required', 'in:Head Office,Cabang,Pabrik'],
            'cost_center' => ['required', 'string', 'min:2', 'max:80', 'regex:' . self::MODERATE_REGEX],
            'lokasi_kerja' => ['required', 'string', 'min:3', 'max:120', 'regex:' . self::MODERATE_REGEX],
            'posisi_jabatan' => ['required', 'string', 'max:255'],
            'job_level' => ['required', 'in:Associate,Supervisor,Manager'],
            'status_karyawan' => ['required', 'in:Outsource'],
            'tanggal_masuk' => ['required', 'date', 'after_or_equal:1900-01-01'],
            'tanggal_berakhir_kontrak' => ['required', 'date', 'after:tanggal_masuk'],
            'atasan_langsung' => ['required', 'string', 'min:3', 'max:255', 'regex:' . self::NAME_REGEX],
            'atasan_tidak_langsung' => ['required', 'string', 'min:3', 'max:255', 'regex:' . self::NAME_REGEX],
            'nama_bank' => ['nullable', 'string', 'max:40'],
            'nomor_rekening' => ['required', 'digits_between:8,20'],
            'nama_pemilik_rekening' => ['required', 'string', 'min:3', 'max:255', 'regex:' . self::NAME_REGEX],
            'npwp' => ['required', 'digits_between:15,16'],
            'status_ptkp' => ['required', 'in:TK/0,TK/1,TK/2,TK/3,K/0,K/1,K/2,K/3'],
            'bpjs_ketenagakerjaan' => ['required', 'digits_between:11,16'],
            'bpjs_kesehatan' => ['required', 'digits_between:13,16'],
            'agreement' => ['required', 'in:1'],
            'consent_timestamp' => ['nullable', 'string', 'max:80'],
            'consent_device' => ['nullable', 'string', 'max:120'],
            'consent_latitude' => ['nullable', 'string', 'max:40'],
            'consent_longitude' => ['nullable', 'string', 'max:40'],
            'consent_location' => ['nullable', 'string', 'max:255'],
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
        $nameOnly = 'Hanya boleh berisi huruf dan spasi.';
        $moderate = 'Hanya boleh berisi huruf, angka, spasi, dan tanda . , & - /';

        return [
            'nama_lengkap.regex' => 'Nama lengkap ' . lcfirst($nameOnly),
            'tempat_lahir.regex' => 'Tempat lahir ' . lcfirst($nameOnly),
            'atasan_langsung.regex' => 'Nama atasan langsung ' . lcfirst($nameOnly),
            'atasan_tidak_langsung.regex' => 'Nama atasan tidak langsung ' . lcfirst($nameOnly),
            'nama_pemilik_rekening.regex' => 'Nama pemilik rekening ' . lcfirst($nameOnly),
            'nik.digits' => 'NIK harus berupa 16 digit angka.',
            'nomor_telepon.regex' => 'Nomor HP tidak valid. Gunakan format 8xxxxxxxxxx.',
            'nomor_telepon.digits_between' => 'Nomor HP hanya boleh berisi angka.',
            'nomor_rekening.digits_between' => 'Nomor rekening hanya boleh berisi angka.',
            'npwp.digits_between' => 'NPWP harus 15 atau 16 digit angka.',
            'bpjs_ketenagakerjaan.digits_between' => 'Nomor BPJS Ketenagakerjaan hanya boleh berisi angka.',
            'bpjs_kesehatan.digits_between' => 'Nomor BPJS Kesehatan hanya boleh berisi angka.',
            'vendor_outsource.regex' => 'Nama vendor ' . lcfirst($moderate),
            'divisi.regex' => 'Divisi ' . lcfirst($moderate),
            'departemen.regex' => 'Departemen ' . lcfirst($moderate),
            'cost_center.regex' => 'Cost center ' . lcfirst($moderate),
            'lokasi_kerja.regex' => 'Lokasi kerja ' . lcfirst($moderate),
            'kecamatan_manual.regex' => 'Kecamatan ' . lcfirst($moderate),
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
            'agama.in' => 'Agama tidak valid.',
            'golongan_darah.in' => 'Golongan darah harus A, B, AB, atau O.',
            'status_pernikahan.in' => 'Status pernikahan tidak valid.',
            'cabang_penempatan.in' => 'Entitas perusahaan tidak valid.',
            'area_kerja.in' => 'Area kerja tidak valid.',
            'job_level.in' => 'Job level tidak valid.',
            'status_karyawan.in' => 'Status karyawan harus Outsource.',
            'status_ptkp.in' => 'Status PTKP tidak valid.',
            'tanggal_berakhir_kontrak.after' => 'Tanggal berakhir kontrak harus setelah tanggal masuk.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'birth_date.date_format' => 'Tanggal lahir harus berformat YYYY-MM-DD.',
            'birth_date.after_or_equal' => 'Tahun lahir tidak valid (minimal 1900).',
            'birth_date.before_or_equal' => 'Tanggal lahir tidak boleh tanggal yang akan datang.',
            'email_pribadi.email' => 'Format alamat email pribadi tidak valid.',
            'email_kantor.email' => 'Format alamat email kantor tidak valid.',
            'kecamatan.required_without' => 'Kecamatan wajib diisi.',
            'kecamatan_manual.required_without' => 'Kecamatan wajib diisi.',
            'agreement.required' => 'Anda harus menyetujui pernyataan keabsahan data untuk melanjutkan.',
            'agreement.in' => 'Anda harus menyetujui pernyataan keabsahan data untuk melanjutkan.',
        ];
    }
}
