<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\SellRequest;
use App\Services\CarPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellCarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'sell',
            'brand' => 'Toyota',
            'model' => 'Avanza',
            'variant' => '1.3 G MT',
            'year' => now()->year - 5,
            'body_type' => 'MPV',
            'transmission' => 'Manual',
            'fuel' => 'Bensin',
            'mileage' => 60000,
            'color' => 'Putih',
            'location' => 'Depok',
            'condition' => 'Baik',
            'asking_price' => 150_000_000,
            'inspection_date' => now()->addDay()->toDateString(),
            'inspection_time' => '10:00',
            'customer_name' => 'Siti Aminah',
            'phone' => '0857-1818-1234',
            'email' => 'siti@example.com',
            'notes' => 'Pajak hidup.',
        ], $overrides);
    }

    public function test_page_loads_with_the_form(): void
    {
        $this->get(route('sell.create'))
            ->assertOk()
            ->assertSee('Jual Langsung')
            ->assertSee('Tukar Tambah')
            ->assertSee('Estimasi Harga');
    }

    public function test_valid_sell_request_is_saved_with_a_server_side_estimate(): void
    {
        $this->post(route('sell.store'), $this->payload([
            // nilai dari browser tidak boleh dipercaya
            'estimate_low' => 1,
            'estimate_high' => 2,
        ]))->assertRedirect(route('sell.create'))->assertSessionHas('sell_request_id');

        $request = SellRequest::where('code', 'JUAL-0004')->firstOrFail();
        $expected = CarPricing::estimate([
            'brand' => 'Toyota', 'model' => 'Avanza', 'body_type' => 'MPV', 'year' => now()->year - 5,
            'mileage' => 60000, 'transmission' => 'Manual', 'condition' => 'Baik',
        ]);

        $this->assertSame('new', $request->status);
        $this->assertSame('sell', $request->type);
        $this->assertSame($expected['low'], $request->estimate_low);
        $this->assertSame($expected['high'], $request->estimate_high);
        $this->assertSame('Siti Aminah', $request->customer_name);
        $this->assertSame(150_000_000, $request->asking_price);
        $this->assertNull($request->trade_in_car_id);
    }

    public function test_success_screen_shows_the_request_code(): void
    {
        $this->followingRedirects()
            ->post(route('sell.store'), $this->payload())
            ->assertOk()
            ->assertSee('Permintaan Terkirim')
            ->assertSee('JUAL-0004');
    }

    public function test_trade_in_needs_an_available_target_car(): void
    {
        $this->post(route('sell.store'), $this->payload(['type' => 'trade-in']))
            ->assertSessionHasErrors('trade_in_car_id');

        $sold = Car::where('status', 'sold')->firstOrFail();
        $this->post(route('sell.store'), $this->payload(['type' => 'trade-in', 'trade_in_car_id' => $sold->id]))
            ->assertSessionHasErrors('trade_in_car_id');

        $this->assertSame(3, SellRequest::count());
    }

    public function test_trade_in_with_an_available_car_is_saved(): void
    {
        $car = Car::where('status', 'available')->firstOrFail();

        $this->post(route('sell.store'), $this->payload(['type' => 'trade-in', 'trade_in_car_id' => $car->id]))
            ->assertSessionHasNoErrors();

        $request = SellRequest::latest('id')->first();
        $this->assertSame('trade-in', $request->type);
        $this->assertSame($car->id, $request->trade_in_car_id);
    }

    public function test_required_contact_fields_are_validated(): void
    {
        $this->post(route('sell.store'), $this->payload(['customer_name' => '', 'phone' => '12345', 'email' => 'bukan-email']))
            ->assertSessionHasErrors(['customer_name', 'phone', 'email']);

        $this->assertSame(3, SellRequest::count());
    }

    public function test_car_data_is_validated(): void
    {
        $this->post(route('sell.store'), $this->payload(['year' => 1800]))->assertSessionHasErrors('year');
        $this->post(route('sell.store'), $this->payload(['year' => now()->year + 2]))->assertSessionHasErrors('year');
        $this->post(route('sell.store'), $this->payload(['model' => '']))->assertSessionHasErrors('model');
        $this->post(route('sell.store'), $this->payload(['mileage' => '']))->assertSessionHasErrors('mileage');
        $this->post(route('sell.store'), $this->payload(['mileage' => 5_000_000]))->assertSessionHasErrors('mileage');
        $this->post(route('sell.store'), $this->payload(['brand' => 'Tesla']))->assertSessionHasErrors('brand');
        $this->post(route('sell.store'), $this->payload(['inspection_date' => now()->subDay()->toDateString()]))->assertSessionHasErrors('inspection_date');
        $this->post(route('sell.store'), $this->payload(['inspection_time' => '03:00']))->assertSessionHasErrors('inspection_time');

        $this->assertSame(3, SellRequest::count());
    }

    public function test_bad_money_input_returns_validation_errors_not_a_500(): void
    {
        foreach (['abc', '-5', '99999999999999999999', '1,5'] as $bad) {
            $this->post(route('sell.store'), $this->payload(['asking_price' => $bad]))
                ->assertSessionHasErrors('asking_price');
        }

        $this->post(route('sell.store'), $this->payload(['asking_price' => '']))->assertSessionHasNoErrors();
        $this->assertSame(0, SellRequest::latest('id')->first()->asking_price);
    }
}
