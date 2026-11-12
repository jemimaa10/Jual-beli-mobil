<?php

if (! function_exists('rupiah')) {
    /** "Rp 1.250.000" */
    function rupiah(int|float|string|null $n): string
    {
        if ($n === null || $n === '' || ! is_numeric($n)) {
            return 'Rp 0';
        }

        $n = (float) $n;
        $sign = $n < 0 ? '-' : '';

        return $sign . 'Rp ' . number_format(abs($n), 0, ',', '.');
    }
}

if (! function_exists('rupiah_short')) {
    /** "Rp 185 jt", "Rp 1,25 M", "Rp 30 rb" */
    function rupiah_short(int|float|string|null $n): string
    {
        $n = is_numeric($n) ? (float) $n : 0.0;
        $trim = fn (float $v, int $dec): string => rtrim(rtrim(number_format($v, $dec, ',', '.'), '0'), ',');

        if ($n >= 1_000_000_000) {
            return 'Rp ' . $trim($n / 1_000_000_000, 2) . ' M';
        }
        if ($n >= 1_000_000) {
            return 'Rp ' . $trim($n / 1_000_000, 1) . ' jt';
        }
        if ($n >= 1_000) {
            return 'Rp ' . number_format(round($n / 1_000), 0, ',', '.') . ' rb';
        }

        return 'Rp ' . number_format($n, 0, ',', '.');
    }
}

if (! function_exists('km')) {
    /** "45.000 km" */
    function km(int|float|string|null $n): string
    {
        return number_format(is_numeric($n) ? (float) $n : 0, 0, ',', '.') . ' km';
    }
}

if (! function_exists('car_name')) {
    /** "Toyota Fortuner 2.4 VRZ AT" (+ " 2020" bila $withYear) */
    function car_name(mixed $car, bool $withYear = false): string
    {
        if (! $car) {
            return '';
        }

        $base = implode(' ', array_filter([
            data_get($car, 'brand'),
            data_get($car, 'model'),
            data_get($car, 'variant'),
        ]));
        $year = data_get($car, 'year');

        return $withYear && $year ? "$base $year" : $base;
    }
}
