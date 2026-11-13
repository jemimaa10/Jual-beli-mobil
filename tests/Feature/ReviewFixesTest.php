<?php

namespace Tests\Feature;

use App\Exceptions\SaleException;
use App\Models\Car;
use App\Models\SellRequest;
use App\Models\TestDrive;
use App\Services\CarSaleService;
use App\Services\SellRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesShowroomData;
use Tests\TestCase;

/** Temuan review akhir: pesan validasi, urutan margin, tukar tambah, nomor HP, pembelian Rp 0. */
class ReviewFixesTest extends TestCase
{
    use MakesShowroomData, RefreshDatabase;

    public function test_validation_messages_are_readable_indonesian_not_raw_keys(): void
    {
        $this->actingAs($this->staff());

        $this->post(route('showroom.products.store'), ['sku' => '', 'name' => 'X', 'buy_price' => '1', 'sell_price' => '1', 'stock' => '-1']);

        $messages = session('errors')->all();
        $this->assertNotEmpty($messages);
        foreach ($messages as $message) {
            $this->assertStringStartsNotWith('validation.', $message);
        }
        $this->assertStringContainsString('wajib diisi', session('errors')->first('sku'));
    }

    public function test_login_failure_and_generic_rules_are_translated(): void
    {
        $this->assertStringStartsNotWith('validation.', __('validation.required', ['attribute' => 'nama']));
        $this->assertStringStartsNotWith('validation.', __('validation.integer', ['attribute' => 'stok']));
        $this->assertStringStartsNotWith('validation.', __('validation.max.string', ['attribute' => 'nama', 'max' => 5]));
        $this->assertStringStartsNotWith('auth.', __('auth.failed'));
    }

    public function test_stock_page_shows_validation_errors_from_its_inline_forms(): void
    {
        $this->actingAs($this->staff());
        $request = $this->makeRequest(['status' => 'scheduled']);

        $this->followingRedirects()
            ->from(route('showroom.cars.index', ['tab' => 'requests']))
            ->patch(route('showroom.requests.update', $request), ['action' => 'inspect', 'offer_price' => ''])
            ->assertOk()
            ->assertSee('Belum tersimpan');
    }

    public function test_margin_sort_handles_cars_priced_below_cost(): void
    {
        $this->actingAs($this->staff());
        $this->makeCar(['model' => 'Untung', 'price' => 200_000_000, 'buy_price' => 150_000_000]);
        $this->makeCar(['model' => 'Rugi', 'price' => 200_000_000, 'buy_price' => 210_000_000]);

        $this->get(route('showroom.cars.index', ['sort' => 'margin']))->assertOk()->assertSeeInOrder(['Untung', 'Rugi']);
    }

    public function test_trade_in_must_point_at_an_open_trade_in_request(): void
    {
        $sales = app(CarSaleService::class);
        $data = fn (SellRequest $r) => ['customer_name' => 'Budi', 'payment_method' => 'transfer', 'trade_in' => ['request_id' => $r->id, 'car_name' => 'X', 'value' => 50_000_000]];

        foreach ([['status' => 'rejected'], ['type' => 'sell', 'status' => 'inspected'], ['status' => 'purchased']] as $attributes) {
            $car = $this->makeCar();
            $request = $this->makeRequest($attributes);
            try {
                $sales->sell($car, $data($request));
                $this->fail('SaleException tidak dilempar untuk ' . json_encode($attributes));
            } catch (SaleException $e) {
                $this->assertSame('Permintaan tukar tambah ini tidak bisa dipakai.', $e->getMessage());
            }
            $this->assertSame('available', $car->fresh()->status, 'penjualan ikut dibatalkan');
            $this->assertSame($attributes['status'], $request->fresh()->status);
        }
        $this->assertSame(3, Car::count());
    }

    public function test_a_rejected_request_cannot_be_purchased(): void
    {
        $request = $this->makeRequest(['status' => 'rejected']);

        try {
            app(SellRequestService::class)->purchase($request, 90_000_000);
            $this->fail('SaleException tidak dilempar');
        } catch (SaleException $e) {
            $this->assertSame('Permintaan yang ditolak tidak bisa dibeli.', $e->getMessage());
        }
        $this->assertSame(0, Car::count());
    }

    public function test_a_request_without_any_price_cannot_be_purchased_for_zero(): void
    {
        $request = $this->makeRequest(['status' => 'inspected', 'offer_price' => 0, 'asking_price' => 0]);

        try {
            app(SellRequestService::class)->purchase($request);
            $this->fail('SaleException tidak dilempar');
        } catch (SaleException $e) {
            $this->assertSame('Isi harga beli terlebih dahulu.', $e->getMessage());
        }
        $this->assertSame(0, Car::count());
        $this->assertSame('inspected', $request->fresh()->status);
    }

    public function test_overlong_phone_numbers_are_rejected_on_public_forms(): void
    {
        $car = $this->makeCar();
        $phone = '0812' . str_repeat(' ', 60) . '3456789';

        $this->post(route('cars.test-drive', $car), [
            'customer_name' => 'A', 'phone' => $phone, 'date' => now()->addDay()->toDateString(), 'time' => '10:00', 'location' => 'showroom',
        ])->assertSessionHasErrors('phone');
        $this->assertSame(0, TestDrive::count());

        $this->post(route('sell.store'), [
            'type' => 'sell', 'brand' => 'Toyota', 'model' => 'Avanza', 'year' => now()->year - 3, 'body_type' => 'MPV', 'transmission' => 'Manual',
            'fuel' => 'Bensin', 'mileage' => 1000, 'color' => 'Putih', 'location' => 'Depok', 'condition' => 'Baik',
            'inspection_date' => now()->addDay()->toDateString(), 'inspection_time' => '10:00', 'customer_name' => 'A', 'phone' => $phone,
        ])->assertSessionHasErrors('phone');
        $this->assertSame(0, SellRequest::count());
    }
}
