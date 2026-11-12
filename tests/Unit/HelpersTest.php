<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_rupiah_formats_with_dot_separators(): void
    {
        $this->assertSame('Rp 1.250.000', rupiah(1250000));
        $this->assertSame('Rp 0', rupiah(null));
    }

    public function test_rupiah_short_uses_jt_m_and_rb(): void
    {
        $this->assertSame('Rp 185 jt', rupiah_short(185000000));
        $this->assertSame('Rp 1,25 M', rupiah_short(1250000000));
        $this->assertSame('Rp 30 rb', rupiah_short(30000));
        $this->assertSame('Rp 500', rupiah_short(500));
    }

    public function test_km_formats_mileage(): void
    {
        $this->assertSame('45.000 km', km(45000));
    }

    public function test_car_name_builds_from_array_with_optional_year(): void
    {
        $car = ['brand' => 'Toyota', 'model' => 'Fortuner', 'variant' => '2.4 VRZ AT', 'year' => 2020];

        $this->assertSame('Toyota Fortuner 2.4 VRZ AT', car_name($car));
        $this->assertSame('Toyota Fortuner 2.4 VRZ AT 2020', car_name($car, true));
    }
}
