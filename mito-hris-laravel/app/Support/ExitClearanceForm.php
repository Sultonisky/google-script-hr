<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Exit clearance is print-only. Company names follow the HRIS entity list,
 * including PT Suka Dessert Indonesia.
 */
class ExitClearanceForm
{
    public const COMPANIES = [
        'msi' => 'PT Mahakarya Sukses Indonesia',
        'spi' => 'PT Stein Perkasa Internasional',
        'mep' => 'PT Mitra Elektro Perkasa',
        'pii' => 'PT Perkasa Injeksi Indonesia',
        'sdi' => 'PT Suka Dessert Indonesia',
    ];

    public const MONEY_STATUSES = [
        'ada' => 'Ada',
        'tidak_ada' => 'Tidak ada',
    ];

    public const GA_ASSETS = [
        'id_card' => 'Kartu Tanda Pengenal',
        'parking' => 'Kartu Parkir',
        'insurance' => 'Kartu Asuransi',
        'uniform' => 'Pakaian Seragam',
        'vehicle' => 'Kendaraan',
        'vehicle_docs' => 'Surat-surat Kendaraan',
        'other' => 'Lainnya',
    ];

    public const IT_FIELDS = [
        'email' => 'Email',
        'password' => 'Password',
        'laptop' => 'Laptop',
        'phone' => 'Handphone',
        'other' => 'Lainnya',
    ];

    public const DIVISION_FIELDS = [
        'devices' => 'Perangkat lainnya',
        'accounts' => 'Akun-akun lainnya',
    ];

    public const APPROVERS = [
        'employee' => ['title' => 'Dibuat oleh', 'hint' => 'Karyawan yang mengundurkan diri'],
        'supervisor' => ['title' => 'Diperiksa oleh', 'hint' => 'Direct Supervisor'],
        'delegate' => ['title' => 'Diperiksa dan diterima oleh', 'hint' => 'Penerima Delegasi'],
        'hrga' => ['title' => 'Diperiksa dan diterima oleh', 'hint' => 'HRGA'],
        'it' => ['title' => 'Diperiksa dan diterima oleh', 'hint' => 'IT'],
        'accounting' => ['title' => 'Diperiksa dan diterima oleh', 'hint' => 'Accounting'],
    ];

    private const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    private const MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public static function matchCompany(string $branchName): string
    {
        $branch = strtolower(trim($branchName));
        if ($branch === '') {
            return '';
        }
        if (str_contains($branch, 'stein') || str_contains($branch, 'steen')) {
            return 'spi';
        }
        if (str_contains($branch, 'injeksi') || str_contains($branch, 'intens')) {
            return 'pii';
        }
        if (str_contains($branch, 'suka') || str_contains($branch, 'dessert') || strtoupper(trim($branchName)) === 'SDI') {
            return 'sdi';
        }
        if (str_contains($branch, 'mitra') || str_contains($branch, 'elektro') || str_contains($branch, 'elektronik')) {
            return 'mep';
        }
        if (str_contains($branch, 'mahakarya') || preg_match('/\bmsi\b/', $branch)) {
            return 'msi';
        }

        return '';
    }

    public static function formatDate(?string $date, bool $withDay = true): string
    {
        if ($date === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return '';
        }

        $parsed = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Jakarta');
        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            return '';
        }

        $formatted = $parsed->day.' '.self::MONTHS[$parsed->month].' '.$parsed->year;
        if (!$withDay) {
            return $formatted;
        }

        return self::DAYS[$parsed->dayOfWeek].', '.$formatted;
    }

    public static function formatRupiah(?string $amount): string
    {
        $digits = preg_replace('/\D/', '', (string) $amount);
        if ($digits === '') {
            return '';
        }

        return 'Rp '.number_format((int) $digits, 0, ',', '.');
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(Request $request): array
    {
        $amounts = [];
        foreach (['petty_amount', 'loan_amount'] as $key) {
            $digits = preg_replace('/\D/', '', (string) $request->input($key, ''));
            $amounts[$key] = $digits === '' ? null : $digits;
        }
        $request->merge($amounts);
        foreach (['petty', 'loan'] as $prefix) {
            if ($request->input($prefix.'_status') !== 'ada') {
                $request->merge([
                    $prefix.'_amount' => null,
                    $prefix.'_to' => null,
                ]);
            }
        }

        $validated = validator($request->all(), self::rules(), self::messages())->validate();

        $company = (string) $validated['company'];
        $gaRows = [];
        foreach (self::GA_ASSETS as $key => $label) {
            if ($key === 'other') {
                $gaRows[] = [
                    'label' => $label,
                    'checked' => false,
                    'other_text' => trim((string) ($validated['ga']['other'] ?? '')),
                ];
            } else {
                $gaRows[] = [
                    'label' => $label,
                    'checked' => filter_var($validated['ga'][$key] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'other_text' => null,
                ];
            }
        }
        $itRows = [];
        foreach (self::IT_FIELDS as $key => $label) {
            $itRows[] = [
                'key' => $key,
                'label' => $label,
                'value' => trim((string) ($validated['it'][$key] ?? '')),
            ];
        }
        $divisionRows = [];
        foreach (self::DIVISION_FIELDS as $key => $label) {
            $divisionRows[] = [
                'label' => $label,
                'value' => trim((string) ($validated['division'][$key] ?? '')),
            ];
        }

        $approvers = [];
        foreach (self::APPROVERS as $key => $meta) {
            $approvers[] = [
                'title' => $meta['title'],
                'hint' => $meta['hint'],
                'name' => trim((string) ($validated['approvers'][$key]['name'] ?? '')),
                'date' => self::formatDate($validated['approvers'][$key]['date'] ?? null, false),
            ];
        }

        return [
            'company' => $company,
            'company_name' => self::COMPANIES[$company],
            'last_working_date' => self::formatDate((string) $validated['last_working_date']),
            'hardcopy' => filter_var($validated['hardcopy'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'softcopy' => filter_var($validated['softcopy'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'delegated_to' => trim((string) ($validated['delegated_to'] ?? '')),
            'handover_date' => self::formatDate($validated['handover_date'] ?? null),
            'handover_other' => trim((string) ($validated['handover_other'] ?? '')),
            'ga' => $gaRows,
            'it' => $itRows,
            'division' => $divisionRows,
            'petty' => self::moneyRow($validated, 'petty'),
            'loan' => self::moneyRow($validated, 'loan'),
            'accounting_other' => trim((string) ($validated['accounting_other'] ?? '')),
            'approvers' => $approvers,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function rules(): array
    {
        $rules = [
            'company' => ['required', 'string', Rule::in(array_keys(self::COMPANIES))],
            'last_working_date' => ['required', 'date_format:Y-m-d'],
            'hardcopy' => ['nullable', 'boolean'],
            'softcopy' => ['nullable', 'boolean'],
            'delegated_to' => ['nullable', 'string', 'max:160'],
            'handover_date' => ['nullable', 'date_format:Y-m-d'],
            'handover_other' => ['nullable', 'string', 'max:180'],
            'petty_status' => ['nullable', 'string', Rule::in(array_keys(self::MONEY_STATUSES))],
            'petty_amount' => ['nullable', 'required_if:petty_status,ada', 'regex:/^\d{1,12}$/'],
            'petty_to' => ['nullable', 'required_if:petty_status,ada', 'string', 'max:160'],
            'loan_status' => ['nullable', 'string', Rule::in(array_keys(self::MONEY_STATUSES))],
            'loan_amount' => ['nullable', 'required_if:loan_status,ada', 'regex:/^\d{1,12}$/'],
            'loan_to' => ['nullable', 'required_if:loan_status,ada', 'string', 'max:160'],
            'accounting_other' => ['nullable', 'string', 'max:300'],
        ];

        foreach (array_keys(self::GA_ASSETS) as $key) {
            $rules["ga.$key"] = $key === 'other'
                ? ['nullable', 'string', 'max:180']
                : ['nullable', 'boolean'];
        }
        $rules['it.email'] = ['nullable', 'string', 'email', 'max:160'];
        $rules['it.password'] = ['nullable', 'string', 'max:120'];
        $rules['it.laptop'] = ['nullable', 'string', 'max:180'];
        $rules['it.phone'] = ['nullable', 'regex:/^\+?[0-9]{8,15}$/'];
        $rules['it.other'] = ['nullable', 'string', 'max:180'];
        foreach (array_keys(self::DIVISION_FIELDS) as $key) {
            $rules["division.$key"] = ['nullable', 'string', 'max:180'];
        }

        foreach (array_keys(self::APPROVERS) as $key) {
            $rules["approvers.$key.name"] = ['nullable', 'string', 'max:120'];
            $rules["approvers.$key.date"] = ['nullable', 'date_format:Y-m-d'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private static function messages(): array
    {
        $messages = [
            'company.required' => 'Perusahaan wajib dipilih.',
            'company.in' => 'Perusahaan tidak dikenal.',
            'last_working_date.required' => 'Tanggal berakhir bekerja wajib diisi.',
            'last_working_date.date_format' => 'Tanggal berakhir bekerja tidak valid.',
            'handover_date.date_format' => 'Tanggal penyerahan tidak valid.',
            'hardcopy.boolean' => 'Pilihan hardcopy tidak valid.',
            'softcopy.boolean' => 'Pilihan softcopy tidak valid.',
            'delegated_to.max' => 'Nama penerima delegasi terlalu panjang.',
            'handover_other.max' => 'Keterangan serah terima lainnya terlalu panjang.',
            'petty_status.in' => 'Status petty cash tidak valid.',
            'petty_amount.regex' => 'Nominal petty cash tidak valid.',
            'petty_amount.required_if' => 'Nominal petty cash wajib diisi jika status Ada.',
            'petty_to.max' => 'Penerima petty cash terlalu panjang.',
            'petty_to.required_if' => 'Penerima petty cash wajib diisi jika status Ada.',
            'loan_status.in' => 'Status pinjaman tidak valid.',
            'loan_amount.regex' => 'Nominal pinjaman tidak valid.',
            'loan_amount.required_if' => 'Nominal pinjaman wajib diisi jika status Ada.',
            'loan_to.max' => 'Penerima pelunasan pinjaman terlalu panjang.',
            'loan_to.required_if' => 'Penerima pelunasan pinjaman wajib diisi jika status Ada.',
            'accounting_other.max' => 'Keterangan accounting terlalu panjang.',
        ];

        foreach (self::GA_ASSETS as $key => $label) {
            if ($key === 'other') {
                $messages["ga.$key.max"] = 'Keterangan aset lainnya terlalu panjang.';
            } else {
                $messages["ga.$key.boolean"] = 'Pilihan '.$label.' tidak valid.';
            }
        }
        $messages['it.email.email'] = 'Email tidak valid.';
        $messages['it.email.max'] = 'Email terlalu panjang.';
        $messages['it.password.max'] = 'Password terlalu panjang.';
        $messages['it.laptop.max'] = 'Keterangan laptop terlalu panjang.';
        $messages['it.phone.regex'] = 'Nomor handphone harus 8–15 digit.';
        $messages['it.other.max'] = 'Keterangan IT lainnya terlalu panjang.';
        foreach (self::DIVISION_FIELDS as $key => $label) {
            $messages["division.$key.max"] = $label.' terlalu panjang.';
        }

        foreach (self::APPROVERS as $key => $meta) {
            $messages["approvers.$key.name.max"] = 'Nama '.$meta['hint'].' terlalu panjang.';
            $messages["approvers.$key.date.date_format"] = 'Tanggal '.$meta['hint'].' tidak valid.';
        }

        return $messages;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{status: string, amount: string, to: string}
     */
    private static function moneyRow(array $validated, string $prefix): array
    {
        $status = (string) ($validated[$prefix.'_status'] ?? '');
        if (!isset(self::MONEY_STATUSES[$status])) {
            $status = '';
        }
        $showAmount = $status === 'ada';

        return [
            'status' => $status,
            'amount' => $showAmount ? self::formatRupiah($validated[$prefix.'_amount'] ?? '') : '',
            'to' => $showAmount ? trim((string) ($validated[$prefix.'_to'] ?? '')) : '',
        ];
    }
}
