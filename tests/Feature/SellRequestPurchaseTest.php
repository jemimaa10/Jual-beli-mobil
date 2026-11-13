<?php

namespace Tests\Feature;

use App\Exceptions\SaleException;
use App\Models\Car;
use App\Services\SellRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesShowroomData;
use Tests\TestCase;

class SellRequestPurchaseTest extends TestCase
{
    use MakesShowroomData, RefreshDatabase;

    public function test_buying_a_request_creates_an_available_car_with_a_default_price(): void
    {
        $request = $this->makeRequest(['status' => 'inspected', 'offer_price' => 0]);

        $car = app(SellRequestService::class)->purchase($request, 96_000_000);

        $this->assertSame('available', $car->status);
        $this->assertSame(96_000_000, $car->buy_price);
        $this->assertSame(107_500_000, $car->price, 'modal x 1,12 dibulatkan ke Rp 500.000');
        $this->assertStringContainsString($request->code, $car->description);
        $this->assertMatchesRegularExpression('/^A18-\d{4}$/', $car->code);
        $this->assertSame('Honda', $car->brand);
        $this->assertSame(90, $car->inspection_score);

        $request->refresh();
        $this->assertSame('purchased', $request->status);
        $this->assertSame(96_000_000, $request->offer_price);
        $this->assertSame($car->id, $request->purchased_car_id);
    }

    public function test_an_explicit_sell_price_wins(): void
    {
        $request = $this->makeRequest();

        $car = app(SellRequestService::class)->purchase($request, 96_000_000, 120_000_000);

        $this->assertSame(120_000_000, $car->price);
    }

    public function test_buy_price_falls_back_to_the_saved_offer_then_the_asking_price(): void
    {
        $withOffer = $this->makeRequest(['offer_price' => 90_000_000]);
        $this->assertSame(90_000_000, app(SellRequestService::class)->purchase($withOffer)->buy_price);

        $asking = $this->makeRequest(['offer_price' => 0, 'asking_price' => 175_000_000]);
        $this->assertSame(175_000_000, app(SellRequestService::class)->purchase($asking)->buy_price);
    }

    public function test_a_request_cannot_be_bought_twice(): void
    {
        $request = $this->makeRequest();
        app(SellRequestService::class)->purchase($request, 96_000_000);

        try {
            app(SellRequestService::class)->purchase($request->fresh(), 96_000_000);
            $this->fail('SaleException tidak dilempar');
        } catch (SaleException $e) {
            $this->assertSame('Permintaan ini sudah dibeli.', $e->getMessage());
        }

        $this->assertSame(1, Car::count());
    }

    public function test_status_flow_new_scheduled_inspected(): void
    {
        $this->actingAs($this->staff());
        $request = $this->makeRequest(['status' => 'new', 'offer_price' => 0]);
        $date = now()->addDays(2)->toDateString();

        $this->patch(route('showroom.requests.update', $request), ['action' => 'schedule', 'inspection_date' => $date, 'inspection_time' => '13:00'])
            ->assertSessionHasNoErrors();
        $request->refresh();
        $this->assertSame('scheduled', $request->status);
        $this->assertSame($date, $request->inspection_date->toDateString());
        $this->assertSame('13:00', $request->inspection_time);

        $this->patch(route('showroom.requests.update', $request), ['action' => 'inspect', 'offer_price' => '96000000'])
            ->assertSessionHasNoErrors();
        $request->refresh();
        $this->assertSame('inspected', $request->status);
        $this->assertSame(96_000_000, $request->offer_price);
    }

    public function test_http_purchase_puts_the_car_in_stock(): void
    {
        $this->actingAs($this->staff());
        $request = $this->makeRequest(['status' => 'inspected']);

        $this->post(route('showroom.requests.purchase', $request), ['offer_price' => '96000000', 'sell_price' => ''])
            ->assertSessionHas('success');

        $this->assertSame('purchased', $request->fresh()->status);
        $this->assertSame(107_500_000, Car::firstOrFail()->price);
    }

    public function test_a_request_can_be_rejected_but_a_purchased_one_cannot_be_changed(): void
    {
        $this->actingAs($this->staff());
        $rejected = $this->makeRequest(['status' => 'new']);
        $this->patch(route('showroom.requests.update', $rejected), ['action' => 'reject']);
        $this->assertSame('rejected', $rejected->fresh()->status);

        $purchased = $this->makeRequest(['status' => 'purchased']);
        $this->from('/')->patch(route('showroom.requests.update', $purchased), ['action' => 'reject'])->assertSessionHas('error');
        $this->assertSame('purchased', $purchased->fresh()->status);
    }

    public function test_requests_can_be_deleted(): void
    {
        $this->actingAs($this->staff());
        $request = $this->makeRequest();

        $this->delete(route('showroom.requests.destroy', $request))->assertSessionHas('success');

        $this->assertNull($request->fresh());
    }

    public function test_bad_offer_price_is_a_validation_error(): void
    {
        $this->actingAs($this->staff());
        $request = $this->makeRequest(['status' => 'scheduled']);

        foreach (['abc', '-1', '99999999999999999999'] as $bad) {
            $this->patch(route('showroom.requests.update', $request), ['action' => 'inspect', 'offer_price' => $bad])
                ->assertSessionHasErrors('offer_price');
        }
        $this->assertSame('scheduled', $request->fresh()->status);
    }
}
