<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Rumus harga mobil bekas, dipindahkan dari legacy-react/src/utils/carUtils.js.
 * Browser menjalankan rumus yang sama untuk pratinjau (resources/js/pricing.js); nilai yang
 * disimpan selalu hasil hitung PHP ini.
 */
class CarPricing
{
    // Perkiraan harga baru (Rp) model populer: dasar estimasi harga jual
    private const MODEL_BASE_PRICE = [
        'agya' => 170_000_000, 'ayla' => 150_000_000, 'brio' => 200_000_000, 'calya' => 160_000_000, 'sigra' => 150_000_000,
        'avanza' => 260_000_000, 'xenia' => 250_000_000, 'veloz' => 300_000_000, 'ertiga' => 260_000_000, 'xpander' => 290_000_000,
        'mobilio' => 240_000_000, 'livina' => 280_000_000, 'rush' => 290_000_000, 'terios' => 270_000_000, 'raize' => 260_000_000,
        'rocky' => 250_000_000, 'jazz' => 270_000_000, 'yaris' => 290_000_000, 'br-v' => 320_000_000, 'hr-v' => 380_000_000,
        'city' => 350_000_000, 'vios' => 350_000_000, 'kijang innova' => 420_000_000, 'innova' => 420_000_000,
        'civic' => 520_000_000, 'cr-v' => 550_000_000, 'fortuner' => 560_000_000, 'pajero sport' => 560_000_000,
        'cx-5' => 600_000_000, 'creta' => 350_000_000, 'almaz' => 350_000_000, 'camry' => 700_000_000, 'alphard' => 1_500_000_000,
    ];

    // Perkiraan harga mobil baru per tipe bodi (dipakai bila model tidak dikenal)
    private const BODY_BASE_PRICE = [
        'Hatchback' => 230_000_000,
        'Sedan' => 480_000_000,
        'MPV' => 300_000_000,
        'SUV' => 520_000_000,
        'Pick-up' => 280_000_000,
    ];

    // Pengali merek: seberapa kuat nilai jual kembali di pasar mobil bekas
    private const BRAND_FACTOR = [
        'Toyota' => 1.05, 'Honda' => 1.02, 'Mitsubishi' => 1.0, 'Suzuki' => 0.95, 'Daihatsu' => 0.95,
        'Nissan' => 0.9, 'Hyundai' => 0.92, 'Mazda' => 0.95, 'Wuling' => 0.85, 'BMW' => 0.65,
    ];

    // Merek premium yang harga barunya jauh di atas rata-rata tipe bodinya
    private const PREMIUM_BRAND_MULTIPLIER = ['BMW' => 2.2];

    private const CONDITION_FACTOR = [
        'Sangat Baik' => 1.05,
        'Baik' => 1.0,
        'Cukup' => 0.88,
        'Perlu Perbaikan' => 0.72,
    ];

    /** Tabel untuk pratinjau di browser, supaya rumusnya tidak ditulis dua kali. */
    public static function tables(): array
    {
        return [
            'modelBase' => self::MODEL_BASE_PRICE,
            'bodyBase' => self::BODY_BASE_PRICE,
            'brandFactor' => self::BRAND_FACTOR,
            'premium' => self::PREMIUM_BRAND_MULTIPLIER,
            'condition' => self::CONDITION_FACTOR,
        ];
    }

    /**
     * Simulasi kredit dengan bunga flat (umum di leasing Indonesia).
     *
     * @return array{dpAmount:int, principal:int, totalInterest:int, monthly:int, totalPayment:int}
     */
    public static function installment(int|float|string|null $price, int|float|string|null $dpPercent, int|float|string|null $tenorMonths, int|float|string|null $annualRate): array
    {
        $safePrice = max(0.0, self::num($price));
        $dp = min(100.0, max(0.0, self::num($dpPercent)));
        $tenor = max(1.0, self::num($tenorMonths) ?: 1.0);
        $rate = max(0.0, self::num($annualRate));

        $dpAmount = (int) round($safePrice * ($dp / 100));
        $principal = (int) round($safePrice) - $dpAmount;
        $totalInterest = (int) round($principal * ($rate / 100) * ($tenor / 12));
        $monthly = (int) (ceil(($principal + $totalInterest) / $tenor / 1000) * 1000);
        $totalPayment = $dpAmount + $monthly * (int) $tenor;

        return [
            'dpAmount' => $dpAmount,
            'principal' => $principal,
            'totalInterest' => $totalInterest,
            'monthly' => $monthly,
            'totalPayment' => $totalPayment,
        ];
    }

    /**
     * Estimasi harga beli mobil dari pelanggan (fitur "Jual Mobil"), dibulatkan ke Rp 500.000.
     * Hanya perkiraan kasar; harga final ditentukan setelah inspeksi.
     *
     * @param  array{brand?:string, model?:string, body_type?:string, year?:mixed, mileage?:mixed, transmission?:string, condition?:string}  $car
     * @return array{low:int, high:int, mid:int}
     */
    public static function estimate(array $car): array
    {
        $brand = (string) ($car['brand'] ?? '');
        $bodyType = (string) ($car['body_type'] ?? '');
        $currentYear = Carbon::now()->year;
        $year = (int) self::num($car['year'] ?? 0) ?: $currentYear;
        $age = max(0, $currentYear - $year);

        $modelBase = self::MODEL_BASE_PRICE[strtolower(trim((string) ($car['model'] ?? '')))] ?? null;
        $newPrice = $modelBase
            ?: (self::BODY_BASE_PRICE[$bodyType] ?? 300_000_000) * (self::PREMIUM_BRAND_MULTIPLIER[$brand] ?? 1);
        $base = $newPrice * (self::BRAND_FACTOR[$brand] ?? 0.92);

        // Depresiasi: 12% di tahun pertama, lalu 6% per tahun, minimal sisa 25%
        $value = $age === 0 ? $base : $base * 0.88 * (0.94 ** ($age - 1));
        $value = max($value, $base * 0.25);

        // Koreksi kilometer terhadap rata-rata 15.000 km/tahun
        $km = self::num($car['mileage'] ?? 0);
        $expectedKm = max(1, $age) * 15_000;
        $kmDiff = ($km - $expectedKm) / 10_000;
        $value *= min(1.06, max(0.8, 1 - $kmDiff * 0.015));

        if (($car['transmission'] ?? '') === 'Otomatis') {
            $value *= 1.04;
        }
        $value *= self::CONDITION_FACTOR[$car['condition'] ?? ''] ?? 1;

        // Harga beli showroom ada di bawah harga pasar
        $mid = $value * 0.85;
        $step = 500_000;

        return [
            'low' => (int) (round(($mid * 0.94) / $step) * $step),
            'high' => (int) (round(($mid * 1.06) / $step) * $step),
            'mid' => (int) (round($mid / $step) * $step),
        ];
    }

    private static function num(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
