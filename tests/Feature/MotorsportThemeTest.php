<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Penanda markup gaya "Motorsport" yang dijaga oleh spesifikasi desain. */
class MotorsportThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_layout_loads_barlow_fonts_and_not_the_old_ones(): void
    {
        $this->get(route('cars.index'))
            ->assertOk()
            ->assertSee('family=Barlow+Condensed', false)
            ->assertSee('family=Barlow:', false)
            ->assertDontSee('Montserrat', false)
            ->assertDontSee('Plus+Jakarta+Sans', false);
    }

    public function test_header_has_racing_strip_and_phone_sub_nav(): void
    {
        $html = $this->get(route('cars.index'))->assertOk()->getContent();
        $this->assertStringContainsString('a18-racing-strip', $html);
        $this->assertMatchesRegularExpression('/<nav[^>]*aria-label="Menu ponsel"[^>]*md:hidden/', $html);
    }

    public function test_staff_header_shows_panel_label_once(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['showroom.dashboard', 'showroom.cars.index', 'showroom.products.index', 'showroom.suppliers.index', 'showroom.reports', 'showroom.settings'] as $r) {
            $html = $this->get(route($r))->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, 'Panel Showroom</'), $r);
        }
    }

    public function test_card_shows_grid_number_and_keeps_focus_ring_unclipped(): void
    {
        $html = $this->get(route('cars.index'))->assertOk()->getContent();
        $this->assertStringContainsString('data-grid-no="01"', $html);
        $this->assertDoesNotMatchRegularExpression('/<article[^>]*a18-cut-/', $html);
        $this->assertMatchesRegularExpression('/<article[^>]*focus-within:ring-2/', $html);
    }

    public function test_sold_card_keeps_stamp_and_sold_price_without_installment(): void
    {
        $car = Car::where('status', 'available')->firstOrFail();
        $car->update(['status' => 'sold', 'sold_price' => 230_000_000, 'sold_at' => now()->subDay()]);
        $html = view('partials.car-card', ['car' => $car->fresh(), 'gridNo' => 1])->render();
        $this->assertStringContainsString('TERJUAL', $html);
        $this->assertStringContainsString('Terjual Rp 230.000.000', $html);
        $this->assertStringNotContainsString('Cicilan mulai', $html);
    }

    public function test_booked_card_shows_amber_dipesan_label(): void
    {
        $car = Car::where('status', 'booked')->firstOrFail();
        $html = view('partials.car-card', ['car' => $car, 'gridNo' => 2])->render();
        $this->assertMatchesRegularExpression('/bg-amber-400[^"]*"[^>]*>\s*(<span[^>]*>\s*)?Dipesan/', $html);
    }

    public function test_home_has_motorsport_sections_and_server_ordered_grid(): void
    {
        $html = $this->get(route('cars.index', ['sort' => 'price_asc']))->assertOk()->getContent();
        foreach (['a18-cut-hero', 'a18-checker', 'a18-stripes-red'] as $cls) {
            $this->assertStringContainsString($cls, $html);
        }
        preg_match_all('/data-grid-no="(\d+)"/', $html, $m);
        $this->assertNotEmpty($m[1]);
        $this->assertSame(range(1, count($m[1])), array_map('intval', $m[1]));
    }

    public function test_featured_car_panel_shows_score_and_fuel(): void
    {
        $car = $this->get(route('cars.index'))->viewData('featuredCar');
        $this->assertNotNull($car->inspection_score);
        $this->assertNotNull($car->fuel);
    }

    public function test_detail_uses_motorsport_frame_and_keeps_content(): void
    {
        $car = Car::where('model', 'Xpander')->firstOrFail();
        $this->get(route('cars.show', $car))->assertOk()
            ->assertSee('a18-cut-card', false)
            ->assertSee('Mesin & Performa')
            ->assertSee('Jadwal Test Drive');
    }

    public function test_sell_page_has_skewed_stepper(): void
    {
        $html = $this->get(route('sell.create'))->assertOk()->getContent();
        $this->assertStringContainsString('data-step="01"', $html);
        $this->assertStringContainsString('data-step="03"', $html);
        $this->assertStringContainsString('a18-skew', $html);
    }

    public function test_home_keeps_all_six_sort_options(): void
    {
        $html = $this->get(route('cars.index'))->getContent();
        preg_match('/<select[^>]*name="sort"[^>]*>(.*?)<\/select>/s', $html, $m);
        $this->assertSame(6, substr_count($m[1] ?? '', '<option'));
    }
}
