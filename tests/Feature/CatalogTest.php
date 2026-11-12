<?php

namespace Tests\Feature;

use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function listed(string $query = ''): array
    {
        $response = $this->get('/' . $query)->assertOk();

        return $response->viewData('cars')->pluck('model')->all();
    }

    public function test_home_lists_unsold_cars_with_a_photo_and_price(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Xpander')
            ->assertSee('Daftar Mobil')
            ->assertSee('images/cars/xpander.jpg', false);

        $this->assertCount(11, $this->listed());
        $this->assertNotContains('Yaris', $this->listed());
    }

    public function test_brand_filter_accepts_several_brands(): void
    {
        $models = $this->listed('?brand[]=Toyota&brand[]=Suzuki');

        $this->assertContains('Fortuner', $models);
        $this->assertContains('Ertiga', $models);
        $this->assertNotContains('Xpander', $models);
    }

    public function test_keyword_search_matches_model_code_and_year(): void
    {
        $this->assertSame(['Fortuner'], $this->listed('?q=fortuner'));
        $this->assertSame(['Xpander'], $this->listed('?q=A18-0001'));
    }

    public function test_sold_cars_are_hidden_unless_requested(): void
    {
        $this->assertNotContains('Yaris', $this->listed());
        $this->assertContains('Yaris', $this->listed('?sold=1'));
    }

    public function test_attribute_filters_narrow_the_list(): void
    {
        $suvs = $this->get('/?body=SUV')->viewData('cars');
        $this->assertTrue($suvs->isNotEmpty());
        $this->assertTrue($suvs->every(fn ($c) => $c->body_type === 'SUV'));

        $manual = $this->get('/?transmission=Manual')->viewData('cars');
        $this->assertTrue($manual->every(fn ($c) => $c->transmission === 'Manual'));

        $lowKm = $this->get('/?km_max=50000')->viewData('cars');
        $this->assertTrue($lowKm->every(fn ($c) => $c->mileage <= 50000));

        $recent = $this->get('/?year_min=2021')->viewData('cars');
        $this->assertTrue($recent->every(fn ($c) => $c->year >= 2021));

        $range = $this->get('/?price_min=150000000&price_max=250000000')->viewData('cars');
        $this->assertTrue($range->every(fn ($c) => $c->price >= 150_000_000 && $c->price <= 250_000_000));
    }

    public function test_sorting_by_price_and_mileage(): void
    {
        $asc = $this->get('/?sort=price_asc')->viewData('cars')->pluck('price')->all();
        $sorted = $asc;
        sort($sorted);
        $this->assertSame($sorted, $asc);

        $desc = $this->get('/?sort=price_desc')->viewData('cars')->pluck('price')->all();
        rsort($sorted);
        $this->assertSame($sorted, $desc);

        $km = $this->get('/?sort=km_asc')->viewData('cars')->pluck('mileage')->all();
        $kmSorted = $km;
        sort($kmSorted);
        $this->assertSame($kmSorted, $km);
    }

    public function test_recommended_order_puts_available_featured_cars_first(): void
    {
        $cars = $this->get('/')->viewData('cars');

        $this->assertSame('available', $cars->first()->status);
        $this->assertTrue($cars->first()->is_featured);
        $booked = $cars->search(fn ($c) => $c->status === 'booked');
        $lastAvailable = $cars->keys()->filter(fn ($i) => $cars[$i]->status === 'available')->max();
        $this->assertGreaterThan($lastAvailable, $booked);
    }

    public function test_garbage_query_values_do_not_break_the_page(): void
    {
        $this->get('/?price_min=abc&km_max=-5&year_min=x&sort=nonsense&brand=notanarray&body[]=x')->assertOk();
        $this->get('/?price_max=99999999999999999999999')->assertOk();
    }

    public function test_car_detail_shows_specs_and_inspection_report(): void
    {
        $car = Car::where('code', 'A18-0001')->firstOrFail();

        $this->get(route('cars.show', $car))
            ->assertOk()
            ->assertSee('Mitsubishi Xpander')
            ->assertSee('Mesin & Performa')
            ->assertSee('Dokumen (BPKB, STNK, Faktur)')
            ->assertSee('Jadwal Test Drive');
    }

    public function test_sold_car_detail_is_marked_and_cannot_be_booked(): void
    {
        $sold = Car::where('status', 'sold')->firstOrFail();

        $this->get(route('cars.show', $sold))
            ->assertOk()
            ->assertSee('TERJUAL')
            ->assertSee('Mobil ini sudah terjual')
            ->assertDontSee('Jadwal Test Drive');
    }

    public function test_unknown_car_returns_404(): void
    {
        $this->get('/mobil/99999')->assertNotFound();
    }
}
