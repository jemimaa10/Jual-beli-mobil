<?php

namespace Tests\Concerns;

use App\Models\Car;
use App\Models\SellRequest;
use App\Models\User;

trait MakesShowroomData
{
    protected function staff(): User
    {
        return User::factory()->create();
    }

    protected function makeCar(array $overrides = []): Car
    {
        static $n = 0;
        $n++;

        return Car::create(array_merge([
            'code' => 'T18-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'brand' => 'Toyota',
            'model' => 'Avanza',
            'variant' => '1.3 G MT',
            'year' => 2020,
            'body_type' => 'MPV',
            'transmission' => 'Manual',
            'fuel' => 'Bensin',
            'mileage' => 40000,
            'color' => 'Putih',
            'location' => 'Depok',
            'price' => 250_000_000,
            'buy_price' => 220_000_000,
            'status' => 'available',
            'inspection' => ['mesin' => 90, 'transmisi' => 90, 'kakiKaki' => 90, 'kelistrikan' => 90, 'interior' => 90, 'eksterior' => 90, 'dokumen' => 90],
            'inspection_score' => 90,
        ], $overrides));
    }

    protected function makeRequest(array $overrides = []): SellRequest
    {
        static $n = 0;
        $n++;

        return SellRequest::create(array_merge([
            'code' => 'JUAL-T' . str_pad((string) $n, 3, '0', STR_PAD_LEFT),
            'type' => 'trade-in',
            'customer_name' => 'Ibu Maria',
            'phone' => '0857-1818-3302',
            'brand' => 'Honda',
            'model' => 'Jazz',
            'variant' => 'RS CVT',
            'year' => 2017,
            'body_type' => 'Hatchback',
            'transmission' => 'Otomatis',
            'fuel' => 'Bensin',
            'mileage' => 76000,
            'color' => 'Merah',
            'location' => 'Tangerang Selatan',
            'condition' => 'Baik',
            'asking_price' => 175_000_000,
            'status' => 'inspected',
        ], $overrides));
    }
}
