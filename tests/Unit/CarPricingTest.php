<?php

namespace Tests\Unit;

use App\Services\CarPricing;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class CarPricingTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
    }

    public function test_installment_uses_flat_rate_and_rounds_up_to_thousand(): void
    {
        $r = CarPricing::installment(200_000_000, 20, 36, 7);

        $this->assertSame(40_000_000, $r['dpAmount']);
        $this->assertSame(160_000_000, $r['principal']);
        $this->assertSame(33_600_000, $r['totalInterest']);
        $this->assertSame(5_378_000, $r['monthly']);
        $this->assertSame(233_608_000, $r['totalPayment']);
    }

    public function test_installment_with_decimal_rate(): void
    {
        $r = CarPricing::installment(245_000_000, 25, 48, 6.5);

        $this->assertSame(61_250_000, $r['dpAmount']);
        $this->assertSame(47_775_000, $r['totalInterest']);
        $this->assertSame(4_824_000, $r['monthly']);
        $this->assertSame(292_802_000, $r['totalPayment']);
    }

    public function test_installment_clamps_bad_input(): void
    {
        $r = CarPricing::installment(-5, 150, 0, -3);

        $this->assertSame(0, $r['dpAmount']);
        $this->assertSame(0, $r['monthly']);
    }

    /** Nilai acuan dihitung dari legacy-react/src/utils/carUtils.js dengan tanggal dibekukan 2026-11-12. */
    public function test_estimate_matches_the_original_javascript(): void
    {
        Carbon::setTestNow('2026-11-12 12:00:00');

        $cases = [
            [['brand' => 'Toyota', 'model' => 'Avanza', 'body_type' => 'MPV', 'year' => 2019, 'mileage' => 60000, 'transmission' => 'Manual', 'condition' => 'Baik'],
                ['low' => 140_500_000, 'high' => 158_500_000, 'mid' => 149_500_000]],
            [['brand' => 'BMW', 'model' => '320i Sport', 'body_type' => 'Sedan', 'year' => 2016, 'mileage' => 90000, 'transmission' => 'Otomatis', 'condition' => 'Cukup'],
                ['low' => 268_500_000, 'high' => 302_500_000, 'mid' => 285_500_000]],
            [['brand' => 'Honda', 'model' => 'Brio', 'body_type' => 'Hatchback', 'year' => 2026, 'mileage' => 5000, 'transmission' => 'Otomatis', 'condition' => 'Sangat Baik'],
                ['low' => 180_500_000, 'high' => 203_500_000, 'mid' => 192_000_000]],
            [['brand' => 'Suzuki', 'model' => 'Karimun', 'body_type' => 'Hatchback', 'year' => 2010, 'mileage' => 250000, 'transmission' => 'Manual', 'condition' => 'Perlu Perbaikan'],
                ['low' => 43_000_000, 'high' => 48_500_000, 'mid' => 46_000_000]],
        ];

        foreach ($cases as [$car, $expected]) {
            $this->assertSame($expected, CarPricing::estimate($car), $car['model']);
        }
    }

    public function test_estimate_tolerates_missing_fields(): void
    {
        Carbon::setTestNow('2026-11-12 12:00:00');

        $r = CarPricing::estimate([]);

        $this->assertGreaterThan(0, $r['mid']);
        $this->assertLessThan($r['high'], $r['low']);
    }
}
