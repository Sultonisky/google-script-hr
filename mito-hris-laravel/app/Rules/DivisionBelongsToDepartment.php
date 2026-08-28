<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DivisionBelongsToDepartment implements ValidationRule
{
    public function __construct(private readonly string $department) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $divisions = config("hris.mpr_department_divisions.{$this->department}", []);

        if (!in_array($value, $divisions, true)) {
            $fail('Divisi yang dipilih tidak sesuai dengan departemen.');
        }
    }
}
