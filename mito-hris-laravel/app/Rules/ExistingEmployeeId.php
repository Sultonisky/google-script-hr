<?php

namespace App\Rules;

use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Backend source-of-truth check: the submitted Employee ID must resolve to a
 * real employee through the active employee repository provider (local dummy
 * in dev, Google Sheets in production). Never trust browser-supplied
 * employee_name/division/department without this check.
 */
class ExistingEmployeeId implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || trim($value) === '') {
            $fail('Karyawan wajib dipilih.');

            return;
        }

        try {
            $repo = app(EmployeeRepositoryInterface::class);
            $found = $repo->findById(trim($value));
        } catch (\Throwable) {
            $fail('Gagal memverifikasi karyawan. Coba lagi.');

            return;
        }

        if (!$found) {
            $fail('Employee ID tidak dikenal.');
        }
    }
}
