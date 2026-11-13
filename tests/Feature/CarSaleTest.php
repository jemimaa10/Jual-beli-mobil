<?php

namespace Tests\Feature;

use App\Exceptions\SaleException;
use App\Models\Car;
use App\Models\Transaction;
use App\Services\CarSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesShowroomData;
use Tests\TestCase;

class CarSaleTest extends TestCase
{
    use MakesShowroomData, RefreshDatabase;

    private function sell(Car $car, array $data): Transaction
    {
        return app(CarSaleService::class)->sell($car, array_merge(['customer_name' => 'Budi', 'payment_method' => 'cash'], $data));
    }

    private function assertRefused(string $message, callable $action): void
    {
        try {
            $action();
            $this->fail('SaleException tidak dilempar');
        } catch (SaleException $e) {
            $this->assertSame($message, $e->getMessage());
        }
    }

    public function test_cash_sale_with_discount(): void
    {
        $car = $this->makeCar();

        $trx = $this->sell($car, ['discount' => 5_000_000, 'paid_amount' => 250_000_000]);

        $this->assertSame('car', $trx->sale_type);
        $this->assertSame(250_000_000, $trx->subtotal);
        $this->assertSame(245_000_000, $trx->total);
        $this->assertSame(245_000_000, $trx->amount_due);
        $this->assertSame(5_000_000, $trx->change_amount);
        $this->assertSame(25_000_000, $trx->total_profit);
        $this->assertSame('completed', $trx->status);
        $this->assertSame($car->id, $trx->car_id);
        $this->assertCount(1, $trx->items);
        $this->assertSame(1, $trx->items->first()->qty);
        $this->assertNull($trx->items->first()->product_id);

        $car->refresh();
        $this->assertSame('sold', $car->status);
        $this->assertSame(245_000_000, $car->sold_price);
        $this->assertSame($trx->id, $car->sold_transaction_id);
        $this->assertNotNull($car->sold_at);
    }

    public function test_empty_paid_amount_means_exact_payment(): void
    {
        $trx = $this->sell($this->makeCar(), ['discount' => 5_000_000, 'paid_amount' => null]);

        $this->assertSame(245_000_000, $trx->paid_amount);
        $this->assertSame(0, $trx->change_amount);
    }

    public function test_underpaid_cash_is_refused_and_nothing_changes(): void
    {
        $car = $this->makeCar();

        $this->assertRefused('Uang diterima kurang dari sisa yang harus dibayar.', fn () => $this->sell($car, ['paid_amount' => 100_000_000]));

        $this->assertSame('available', $car->fresh()->status);
        $this->assertSame(0, Transaction::count());
    }

    public function test_credit_sale_uses_the_flat_rate_formula(): void
    {
        $car = $this->makeCar(['price' => 200_000_000, 'buy_price' => 180_000_000]);

        $trx = $this->sell($car, [
            'payment_method' => 'credit',
            'credit' => ['leasing' => 'BCA Finance', 'dp_percent' => 20, 'tenor_months' => 36, 'annual_rate' => 7],
        ]);

        $this->assertSame(40_000_000, $trx->paid_amount);
        $this->assertSame('BCA Finance', $trx->credit['leasing']);
        $this->assertSame(160_000_000, $trx->credit['principal']);
        $this->assertSame(40_000_000, $trx->credit['dp_amount']);
        $this->assertSame(33_600_000, $trx->credit['total_interest']);
        $this->assertSame(5_378_000, $trx->credit['monthly']);
    }

    public function test_credit_defaults_come_from_settings(): void
    {
        $car = $this->makeCar(['price' => 200_000_000]);

        $trx = $this->sell($car, ['payment_method' => 'credit']);

        $this->assertEquals(20, $trx->credit['dp_percent']);
        $this->assertEquals(7, $trx->credit['annual_rate']);
        $this->assertSame(36, $trx->credit['tenor_months']);
    }

    public function test_credit_dp_below_the_minimum_is_refused(): void
    {
        $car = $this->makeCar();

        $this->assertRefused('DP minimal 20%.', fn () => $this->sell($car, [
            'payment_method' => 'credit',
            'credit' => ['dp_percent' => 10, 'tenor_months' => 36, 'annual_rate' => 7],
        ]));
    }

    public function test_transfer_is_recorded_as_fully_paid(): void
    {
        $trx = $this->sell($this->makeCar(), ['payment_method' => 'transfer']);

        $this->assertSame(250_000_000, $trx->paid_amount);
        $this->assertSame(0, $trx->change_amount);
    }

    public function test_invalid_discounts_are_refused(): void
    {
        $car = $this->makeCar();

        $this->assertRefused('Diskon tidak valid atau melebihi harga mobil.', fn () => $this->sell($car, ['discount' => 250_000_000]));
        $this->assertRefused('Diskon tidak valid atau melebihi harga mobil.', fn () => $this->sell($car, ['discount' => -1]));

        $this->assertSame('available', $car->fresh()->status);
    }

    public function test_manual_trade_in_only_reduces_the_amount_due(): void
    {
        $car = $this->makeCar();

        $trx = $this->sell($car, ['trade_in' => ['car_name' => 'Honda Jazz RS 2017', 'value' => 100_000_000]]);

        $this->assertSame(150_000_000, $trx->amount_due);
        $this->assertSame(250_000_000, $trx->total);
        $this->assertSame('Honda Jazz RS 2017', $trx->trade_in['car_name']);
        $this->assertSame(100_000_000, $trx->trade_in['value']);
        $this->assertSame(1, Car::count());
    }

    public function test_trade_in_from_a_request_enters_stock(): void
    {
        $car = $this->makeCar();
        $request = $this->makeRequest();

        $this->sell($car, ['trade_in' => ['request_id' => $request->id, 'car_name' => 'Honda Jazz RS 2017', 'value' => 100_000_000]]);

        $this->assertSame(2, Car::count());
        $stocked = Car::where('brand', 'Honda')->firstOrFail();
        $this->assertSame('available', $stocked->status);
        $this->assertSame(100_000_000, $stocked->buy_price);

        $request->refresh();
        $this->assertSame('purchased', $request->status);
        $this->assertSame($stocked->id, $request->purchased_car_id);
    }

    public function test_trade_in_above_the_total_is_refused(): void
    {
        $car = $this->makeCar();

        $this->assertRefused('Nilai tukar tambah melebihi total harga mobil.', fn () => $this->sell($car, ['trade_in' => ['car_name' => 'X', 'value' => 251_000_000]]));
    }

    public function test_a_sold_car_cannot_be_sold_twice(): void
    {
        $car = $this->makeCar();
        $this->sell($car, []);

        $this->assertRefused('Mobil ini sudah terjual.', fn () => $this->sell($car->fresh(), []));

        $this->assertSame(1, Transaction::count());
    }

    public function test_the_same_stale_car_model_cannot_be_sold_twice_either(): void
    {
        $car = $this->makeCar();
        $stale = Car::find($car->id); // dimuat sebelum penjualan pertama, seperti tab kedua
        $this->sell($car, []);

        $this->assertRefused('Mobil ini sudah terjual.', fn () => $this->sell($stale, []));

        $this->assertSame(1, Transaction::count());
    }

    public function test_voiding_a_sale_frees_the_car_but_keeps_the_trade_in(): void
    {
        $car = $this->makeCar();
        $request = $this->makeRequest();
        $trx = $this->sell($car, ['trade_in' => ['request_id' => $request->id, 'car_name' => 'Honda Jazz', 'value' => 100_000_000]]);

        $voided = app(CarSaleService::class)->void($car->fresh());

        $this->assertSame($trx->id, $voided->id);
        $this->assertSame('void', $voided->fresh()->status);
        $this->assertNotNull($voided->fresh()->voided_at);

        $car->refresh();
        $this->assertSame('available', $car->status);
        $this->assertNull($car->sold_at);
        $this->assertNull($car->sold_price);
        $this->assertNull($car->sold_transaction_id);
        $this->assertSame(2, Car::count(), 'mobil tukar tambah tetap di stok');
        $this->assertSame(0, Transaction::completed()->count());
    }

    public function test_voiding_a_car_that_is_not_sold_is_refused(): void
    {
        $this->assertRefused('Mobil ini belum terjual.', fn () => app(CarSaleService::class)->void($this->makeCar()));
    }

    public function test_guests_cannot_sell(): void
    {
        $car = $this->makeCar();

        $this->post(route('showroom.cars.sale.store', $car), ['customer_name' => 'X'])->assertRedirect('/login');
        $this->assertSame('available', $car->fresh()->status);
    }

    public function test_bad_money_input_returns_validation_errors_not_a_500(): void
    {
        $car = $this->makeCar();
        $this->actingAs($this->staff());

        foreach (['abc', '99999999999999999999', '-5', '1,5'] as $bad) {
            $this->post(route('showroom.cars.sale.store', $car), [
                'customer_name' => 'Budi', 'payment_method' => 'cash', 'discount' => $bad,
            ])->assertSessionHasErrors('discount');
        }
        $this->post(route('showroom.cars.sale.store', $car), [
            'customer_name' => 'Budi', 'payment_method' => 'cash', 'paid_amount' => 'banyak',
        ])->assertSessionHasErrors('paid_amount');

        $this->assertSame('available', $car->fresh()->status);
        $this->assertSame(0, Transaction::count());
    }

    public function test_http_sale_redirects_to_the_receipt(): void
    {
        $car = $this->makeCar();
        $this->actingAs($this->staff());

        $response = $this->post(route('showroom.cars.sale.store', $car), [
            'customer_name' => 'Budi', 'customer_phone' => '0812-0000-1111', 'payment_method' => 'cash', 'discount' => '5000000', 'paid_amount' => '250000000',
        ]);

        $trx = Transaction::firstOrFail();
        $response->assertRedirect(route('showroom.receipt', $trx));
        $this->get(route('showroom.receipt', $trx))->assertOk()->assertSee('NOTA PENJUALAN KENDARAAN', false)->assertSee('TERJUAL');
    }

    public function test_http_double_submit_shows_an_error_and_no_second_transaction(): void
    {
        $car = $this->makeCar();
        $this->actingAs($this->staff());
        $payload = ['customer_name' => 'Budi', 'payment_method' => 'transfer'];

        $this->post(route('showroom.cars.sale.store', $car), $payload);
        $this->from(route('showroom.cars.sale.create', $car))
            ->post(route('showroom.cars.sale.store', $car), $payload)
            ->assertSessionHas('error', 'Mobil ini sudah terjual.');

        $this->assertSame(1, Transaction::count());
    }

    public function test_http_void_marks_the_receipt_as_cancelled(): void
    {
        $car = $this->makeCar();
        $this->actingAs($this->staff());
        $trx = $this->sell($car, []);

        $this->post(route('showroom.cars.sale.void', $car))->assertSessionHas('success');

        $this->get(route('showroom.receipt', $trx))->assertOk()->assertSee('DIBATALKAN');
        $this->assertSame('available', $car->fresh()->status);
    }
}
