<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Nomor HP Indonesia: 08xx / 628xx / +628xx, boleh memakai spasi, titik, tanda hubung, atau kurung. */
class IndonesianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/[\s().-]/', '', trim((string) $value));

        if (! preg_match('/^(\+62|62|0)8\d{7,11}$/', $digits)) {
            $fail('Nomor HP tidak valid.');
        }
    }
}
