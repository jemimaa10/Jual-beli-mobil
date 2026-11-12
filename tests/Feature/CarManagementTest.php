<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\TestDrive;
use App\Models\Transaction;
use App\Services\CarSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesShowroomData;
use Tests\TestCase;

class CarManagementTest extends TestCase
{
    use MakesShowroomData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->staff());
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'brand' => 'Toyota', 'model' => 'Rush', 'variant' => '1.5 S AT', 'year' => 2020,
            'body_type' => 'SUV', 'transmission' => 'Otomatis', 'fuel' => 'Bensin',
            'engine_cc' => 1496, 'seats' => 7, 'mileage' => 48000,
            'color' => 'Putih', 'plate' => 'B 1234 XYZ', 'location' => 'Jakarta Timur',
            'price' => '215000000', 'buy_price' => '190000000', 'status' => 'available',
            'owner_count' => 1, 'tax_valid_until' => '2027-05', 'service_record' => '1',
            'features' => 'Sunroof, Kamera 360 , ', 'description' => 'Terawat.',
            'inspection' => ['mesin' => 95, 'transmisi' => 120, 'kakiKaki' => -10, 'kelistrikan' => 90, 'interior' => 90, 'eksterior' => 90, 'dokumen' => 100],
        ], $overrides);
    }

    public function test_index_page_lists_cars_with_tabs(): void
    {
        $this->makeCar(['model' => 'Avanza']);

        $this->get(route('showroom.cars.index'))->assertOk()->assertSee('Avanza')->assertSee('Stok Mobil');
        $this->get(route('showroom.cars.index', ['tab' => 'requests']))->assertOk();
        $this->get(route('showroom.cars.index', ['tab' => 'testdrive']))->assertOk();
        $this->get(route('showroom.cars.index', ['tab' => 'bogus', 'status' => ['x'], 'sort' => 'zzz']))->assertOk();
    }

    public function test_stock_filters_search_status_and_brand(): void
    {
        $this->makeCar(['model' => 'Avanza']);
        $this->makeCar(['model' => 'Brio', 'brand' => 'Honda', 'status' => 'booked']);

        $this->get(route('showroom.cars.index', ['q' => 'brio']))->assertSee('Brio')->assertDontSee('Avanza');
        $this->get(route('showroom.cars.index', ['status' => 'booked']))->assertSee('Brio')->assertDontSee('Avanza');
        $this->get(route('showroom.cars.index', ['brand' => 'Toyota']))->assertSee('Avanza')->assertDontSee('Brio');
    }

    public function test_creating_a_car_normalizes_features_and_inspection(): void
    {
        $this->post(route('showroom.cars.store'), $this->payload())->assertSessionHasNoErrors();

        $car = Car::where('model', 'Rush')->firstOrFail();
        $this->assertSame(['Sunroof', 'Kamera 360'], $car->features);
        $this->assertSame(100, $car->inspection['transmisi']);
        $this->assertSame(0, $car->inspection['kakiKaki']);
        $this->assertSame((int) round((95 + 100 + 0 + 90 + 90 + 90 + 100) / 7), $car->inspection_score);
        $this->assertSame(215_000_000, $car->price);
        $this->assertMatchesRegularExpression('/^A18-\d{4}$/', $car->code);
        $this->assertSame('available', $car->status);
    }

    public function test_car_form_validation(): void
    {
        $this->post(route('showroom.cars.store'), $this->payload(['model' => '']))->assertSessionHasErrors('model');
        $this->post(route('showroom.cars.store'), $this->payload(['price' => '0']))->assertSessionHasErrors('price');
        $this->post(route('showroom.cars.store'), $this->payload(['price' => 'abc']))->assertSessionHasErrors('price');
        $this->post(route('showroom.cars.store'), $this->payload(['buy_price' => '-1']))->assertSessionHasErrors('buy_price');
        $this->post(route('showroom.cars.store'), $this->payload(['year' => 1900]))->assertSessionHasErrors('year');
        $this->post(route('showroom.cars.store'), $this->payload(['status' => 'sold']))->assertSessionHasErrors('status');
        $this->post(route('showroom.cars.store'), $this->payload(['tax_valid_until' => '2027/05']))->assertSessionHasErrors('tax_valid_until');
        $this->post(route('showroom.cars.store'), $this->payload(['image_url' => 'bukan url']))->assertSessionHasErrors('image_url');

        $this->assertSame(0, Car::count());
    }

    public function test_updating_a_car(): void
    {
        $car = $this->makeCar();

        $this->put(route('showroom.cars.update', $car), $this->payload(['model' => 'Rush', 'price' => '230000000']))->assertSessionHasNoErrors();

        $this->assertSame('Rush', $car->fresh()->model);
        $this->assertSame(230_000_000, $car->fresh()->price);
        $this->assertSame($car->code, $car->fresh()->code, 'kode stok tidak berubah');
    }

    public function test_editing_a_sold_car_keeps_it_sold(): void
    {
        $car = $this->makeCar();
        app(CarSaleService::class)->sell($car, ['customer_name' => 'Budi', 'payment_method' => 'transfer']);

        $this->put(route('showroom.cars.update', $car->fresh()), $this->payload(['status' => 'available']))->assertSessionHasNoErrors();

        $this->assertSame('sold', $car->fresh()->status);
    }

    public function test_status_can_toggle_between_available_and_booked_only(): void
    {
        $car = $this->makeCar();

        $this->patch(route('showroom.cars.status', $car), ['status' => 'booked']);
        $this->assertSame('booked', $car->fresh()->status);
        $this->patch(route('showroom.cars.status', $car), ['status' => 'available']);
        $this->assertSame('available', $car->fresh()->status);

        $this->patch(route('showroom.cars.status', $car), ['status' => 'sold'])->assertSessionHasErrors('status');
        $this->assertSame('available', $car->fresh()->status);
    }

    public function test_a_sold_car_status_cannot_be_changed_through_the_status_route(): void
    {
        $car = $this->makeCar();
        app(CarSaleService::class)->sell($car, ['customer_name' => 'Budi', 'payment_method' => 'transfer']);

        $this->patch(route('showroom.cars.status', $car->fresh()), ['status' => 'available'])->assertSessionHas('error');

        $this->assertSame('sold', $car->fresh()->status);
    }

    public function test_featured_flag_can_be_toggled(): void
    {
        $car = $this->makeCar(['is_featured' => false]);

        $this->patch(route('showroom.cars.feature', $car));
        $this->assertTrue($car->fresh()->is_featured);
        $this->patch(route('showroom.cars.feature', $car));
        $this->assertFalse($car->fresh()->is_featured);
    }

    public function test_deleting_a_car_keeps_old_records_readable(): void
    {
        $car = $this->makeCar();
        $drive = TestDrive::create(['code' => 'TD-T1', 'car_id' => $car->id, 'car_name' => 'Toyota Avanza 2020', 'customer_name' => 'A', 'phone' => '0812', 'date' => today(), 'time' => '10:00', 'status' => 'pending']);
        $trx = app(CarSaleService::class)->sell($car, ['customer_name' => 'Budi', 'payment_method' => 'transfer']);

        $this->delete(route('showroom.cars.destroy', $car->fresh()))->assertSessionHas('success');

        $this->assertNull(Car::find($car->id));
        $this->assertNull($drive->fresh()->car_id);
        $this->assertSame('Toyota Avanza 2020', $drive->fresh()->car_name);
        $this->assertNull($trx->fresh()->car_id);
        $this->get(route('showroom.receipt', $trx))->assertOk()->assertSee('Toyota Avanza 1.3 G MT 2020');
        $this->get(route('showroom.cars.index', ['tab' => 'testdrive']))->assertOk()->assertSee('Toyota Avanza 2020');
        $this->assertSame(1, Transaction::count());
    }

    public function test_test_drive_status_changes_and_delete(): void
    {
        $drive = TestDrive::create(['code' => 'TD-T2', 'car_name' => 'Toyota Avanza 2020', 'customer_name' => 'A', 'phone' => '0812', 'date' => today(), 'time' => '10:00', 'status' => 'pending']);

        $this->patch(route('showroom.test-drives.update', $drive), ['status' => 'confirmed']);
        $this->assertSame('confirmed', $drive->fresh()->status);
        $this->patch(route('showroom.test-drives.update', $drive), ['status' => 'done']);
        $this->assertSame('done', $drive->fresh()->status);

        $this->patch(route('showroom.test-drives.update', $drive), ['status' => 'cancelled'])->assertSessionHas('error');
        $this->assertSame('done', $drive->fresh()->status, 'booking yang selesai tidak bisa dibatalkan');

        $this->patch(route('showroom.test-drives.update', $drive), ['status' => 'bogus'])->assertSessionHasErrors('status');

        $this->delete(route('showroom.test-drives.destroy', $drive))->assertSessionHas('success');
        $this->assertNull($drive->fresh());
    }

    public function test_stock_pages_require_login(): void
    {
        auth()->logout();
        $car = $this->makeCar();

        $this->get(route('showroom.cars.index'))->assertRedirect('/login');
        $this->post(route('showroom.cars.store'), $this->payload())->assertRedirect('/login');
        $this->delete(route('showroom.cars.destroy', $car))->assertRedirect('/login');
        $this->assertNotNull($car->fresh());
    }
}
