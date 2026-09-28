<?php

namespace App\Http\Requests\Public;

use App\Support\OutsourceEmployeeAttributeMap;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OutsourceApplyRequest extends FormRequest
{
    private const NAME_REGEX = '/^[\p{L}\'.]+(?:[ ]+[\p{L}\'.]+)*$/u';

    private const MODERATE_REGEX = '/^[\p{L}0-9 .,&()\-\/]+$/u';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach (['whatsapp_number', 'bank_account'] as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                $merged[$key] = preg_replace('/\D+/', '', $value);
            }
        }

        if (isset($merged['whatsapp_number'])) {
            $digits = $merged['whatsapp_number'];
            if (str_starts_with($digits, '62')) {
                $digits = substr($digits, 2);
            } elseif (str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }
            $merged['whatsapp_number'] = $digits;
        }

        foreach ([
            'full_name', 'birth_place', 'last_education', 'vendor', 'job_title', 'work_location',
            'work_city', 'cost_center', 'entity', 'payroll_scheme',
        ] as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                $merged[$key] = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
            }
        }

        $address = $this->input('citizen_id_address');
        if (is_string($address)) {
            $merged['citizen_id_address'] = trim($address);
        }

        $email = $this->input('email');
        if (is_string($email)) {
            $merged['email'] = strtolower(trim($email));
        }

        $umk = $this->input('umk_amount');
        if (is_string($umk) || is_numeric($umk)) {
            $parsed = OutsourceEmployeeAttributeMap::parseAmount($umk);
            $merged['umk_amount'] = $parsed ?? $umk;
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        $config = config('hris.outsource', []);

        return [
            'full_name' => ['required', 'string', 'min:3', 'max:255', 'regex:' . self::NAME_REGEX],
            'citizen_id_address' => ['required', 'string', 'min:5', 'max:500'],
            'birth_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:1900-01-01',
                'before_or_equal:today',
            ],
            'birth_place' => ['required', 'string', 'min:3', 'max:120', 'regex:' . self::NAME_REGEX],
            'last_education' => ['required', Rule::in($config['education_levels'] ?? [])],
            'whatsapp_number' => ['required', 'regex:/^8[0-9]{7,12}$/'],
            'email' => ['required', 'email:filter', 'max:255'],
            'vendor' => ['required', Rule::in($config['vendors'] ?? [])],
            'job_title' => ['required', 'string', 'min:2', 'max:120', 'regex:' . self::MODERATE_REGEX],
            'work_location' => ['required', 'string', 'min:2', 'max:255', 'regex:' . self::MODERATE_REGEX],
            'work_city' => ['required', 'string', 'min:2', 'max:120', 'regex:' . self::MODERATE_REGEX],
            'cost_center' => ['required', 'string', 'min:2', 'max:120', 'regex:' . self::MODERATE_REGEX],
            'entity' => ['required', Rule::in($config['entities'] ?? [])],
            'mito_join_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1900-01-01'],
            'contract_start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1900-01-01'],
            'contract_end_date' => ['required', 'date_format:Y-m-d', 'after:contract_start_date'],
            'payroll_scheme' => ['required', Rule::in($config['payroll_schemes'] ?? [])],
            'umk_amount' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'bank_account' => ['required', 'digits_between:8,20'],
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
        $nameOnly = 'hanya boleh berisi huruf, spasi, titik, dan apostrof.';
        $moderate = 'hanya boleh berisi huruf, angka, spasi, dan tanda . , & ( ) - /';

        return [
            'full_name.required' => 'Nama sesuai KTP wajib diisi.',
            'full_name.regex' => 'Nama ' . $nameOnly,
            'citizen_id_address.required' => 'Alamat sesuai KTP wajib diisi.',
            'citizen_id_address.min' => 'Alamat sesuai KTP minimal 5 karakter.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'birth_date.date_format' => 'Tanggal lahir harus berformat YYYY-MM-DD.',
            'birth_date.after_or_equal' => 'Tahun lahir tidak valid (minimal 1900).',
            'birth_date.before_or_equal' => 'Tanggal lahir tidak boleh tanggal yang akan datang.',
            'birth_place.required' => 'Kota kelahiran wajib diisi.',
            'birth_place.regex' => 'Kota kelahiran ' . $nameOnly,
            'last_education.required' => 'Pendidikan terakhir wajib dipilih.',
            'last_education.in' => 'Pendidikan terakhir tidak valid.',
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp_number.regex' => 'Nomor WhatsApp tidak valid. Gunakan format 8xxxxxxxxxx.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'vendor.required' => 'Vendor wajib dipilih.',
            'vendor.in' => 'Vendor tidak valid.',
            'job_title.required' => 'Nama jabatan wajib diisi.',
            'job_title.regex' => 'Nama jabatan ' . $moderate,
            'work_location.required' => 'Lokasi kerja wajib diisi.',
            'work_location.regex' => 'Lokasi kerja ' . $moderate,
            'work_city.required' => 'Kota lokasi kerja wajib diisi.',
            'work_city.regex' => 'Kota lokasi kerja ' . $moderate,
            'cost_center.required' => 'Cabang (cost center) wajib diisi.',
            'cost_center.regex' => 'Cabang (cost center) ' . $moderate,
            'entity.required' => 'Entity wajib dipilih.',
            'entity.in' => 'Entity tidak valid.',
            'mito_join_date.required' => 'Tanggal join di Mito wajib diisi.',
            'mito_join_date.date_format' => 'Tanggal join di Mito harus berformat YYYY-MM-DD.',
            'contract_start_date.required' => 'Tanggal awal kontrak wajib diisi.',
            'contract_start_date.date_format' => 'Tanggal awal kontrak harus berformat YYYY-MM-DD.',
            'contract_end_date.required' => 'Tanggal akhir kontrak wajib diisi.',
            'contract_end_date.date_format' => 'Tanggal akhir kontrak harus berformat YYYY-MM-DD.',
            'contract_end_date.after' => 'Tanggal akhir kontrak harus setelah tanggal awal kontrak.',
            'payroll_scheme.required' => 'Skema penggajian wajib dipilih.',
            'payroll_scheme.in' => 'Skema penggajian tidak valid.',
            'umk_amount.required' => 'Nominal UMK wajib diisi.',
            'umk_amount.numeric' => 'Nominal UMK harus berupa angka.',
            'umk_amount.min' => 'Nominal UMK tidak boleh negatif.',
            'bank_account.required' => 'Nomor rekening BCA wajib diisi.',
            'bank_account.digits_between' => 'Nomor rekening BCA harus 8–20 digit angka.',
            'agreement.required' => 'Anda harus menyetujui pernyataan keabsahan data untuk melanjutkan.',
            'agreement.in' => 'Anda harus menyetujui pernyataan keabsahan data untuk melanjutkan.',
        ];
    }
}
