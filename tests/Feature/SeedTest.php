<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Category;
use App\Models\Product;
use App\Models\SellRequest;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\TestDrive;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedTest extends TestCase
{
    use RefreshDatabase;

    private function demo(): array
    {
        return json_decode(file_get_contents(database_path('seeders/data/demo.json')), true);
    }

    public function test_seeding_loads_the_demo_showroom(): void
    {
        $this->seed();
        $demo = $this->demo();

        $this->assertSame(12, Car::count());
        $this->assertSame(3, SellRequest::count());
        $this->assertSame(3, TestDrive::count());
        $this->assertSame(count($demo['products']), Product::count());
        $this->assertSame(count($demo['categories']), Category::count());
        $this->assertSame(count($demo['suppliers']), Supplier::count());
        $this->assertSame(count($demo['transactions']), Transaction::count());
        $this->assertTrue(User::where('email', 'admin@auto18.test')->exists());
    }

    public function test_settings_come_from_the_store_profile(): void
    {
        $this->seed();

        $this->assertSame('Auto18', Setting::current()->name);
        $this->assertEquals(7, Setting::current()->credit_rate);
        $this->assertFalse(Setting::current()->tax_enabled);
    }

    public function test_every_transaction_has_items_and_sold_car_points_back(): void
    {
        $this->seed();

        foreach (Transaction::with('items')->get() as $trx) {
            $this->assertGreaterThan(0, $trx->items->count(), $trx->code);
        }

        $sold = Car::where('status', 'sold')->firstOrFail();
        $trx = Transaction::find($sold->sold_transaction_id);
        $this->assertSame('car', $trx->sale_type);
        $this->assertSame($sold->id, $trx->car_id);
    }

    public function test_inspection_score_is_the_rounded_average(): void
    {
        $this->seed();

        $car = Car::where('code', 'A18-0001')->firstOrFail();
        $this->assertCount(7, $car->inspection);
        $this->assertSame((int) round(array_sum($car->inspection) / 7), $car->inspection_score);
    }

    public function test_car_photo_resolves_to_a_bundled_file(): void
    {
        $this->seed();

        $car = Car::where('code', 'A18-0001')->firstOrFail();
        $this->assertStringEndsWith('/images/cars/xpander.jpg', $car->image_src);
        $this->assertFileExists(public_path('images/cars/xpander.jpg'));
    }

    public function test_demo_dates_are_relative_to_today(): void
    {
        $this->seed();

        $this->assertTrue(Transaction::where('sale_type', 'retail')->whereDate('date', today())->exists());
    }
}
