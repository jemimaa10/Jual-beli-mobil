<?php

namespace App\Services;

use App\Models\Car;
use App\Models\SellRequest;
use App\Models\TestDrive;
use App\Models\Transaction;

/** Kode berurutan: angka terbesar yang sudah ada + 1. */
class CodeGenerator
{
    public static function car(): string
    {
        return 'A18-' . self::next(Car::query()->pluck('code'), 1);
    }

    public static function sellRequest(): string
    {
        return 'JUAL-' . self::next(SellRequest::query()->pluck('code'), 1);
    }

    public static function testDrive(): string
    {
        return 'TD-' . self::next(TestDrive::query()->pluck('code'), 1);
    }

    public static function transaction(): string
    {
        return 'TRX-A18-' . self::next(Transaction::query()->pluck('code'), 1001);
    }

    private static function next($codes, int $start): string
    {
        $max = $codes
            ->map(fn ($code) => preg_match('/(\d+)$/', (string) $code, $m) ? (int) $m[1] : 0)
            ->max();

        return str_pad((string) ($max ? $max + 1 : $start), 4, '0', STR_PAD_LEFT);
    }
}
