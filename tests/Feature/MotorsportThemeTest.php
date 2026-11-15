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

    public function test_404_uses_outline_numeral(): void
    {
        $this->get('/halaman-yang-tidak-ada')->assertNotFound()->assertSee('a18-outline-num', false);
    }

    public function test_standalone_error_pages_use_new_fonts_without_vite(): void
    {
        foreach (['errors.500', 'errors.503'] as $view) {
            $html = view($view)->render();
            $this->assertStringContainsString('Barlow+Condensed', $html, $view);
            $this->assertStringContainsString('#070708', $html, $view);
            $this->assertStringNotContainsString('/build/', $html, $view);
        }
    }

    public function test_login_page_uses_motorsport_card(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('a18-cut-tag', false)->assertSee('Masuk Panel Showroom');
    }

    public function test_staff_pages_have_no_slanted_or_rounded_tables_and_fields(): void
    {
        $this->actingAs(User::factory()->create());
        $car = Car::where('status', 'available')->firstOrFail();
        $routes = [
            route('showroom.dashboard'), route('showroom.cars.index'), route('showroom.cars.index', ['tab' => 'requests']),
            route('showroom.cars.index', ['tab' => 'testdrive']), route('showroom.cars.create'), route('showroom.cars.sale.create', $car),
            route('showroom.products.index'), route('showroom.suppliers.index'), route('showroom.reports'), route('showroom.settings'), route('showroom.pos'),
        ];
        foreach ($routes as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertDoesNotMatchRegularExpression('/<(table|td|th|input|select|textarea)[^>]*a18-(skew|cut-)/', $html, $url);
            $this->assertDoesNotMatchRegularExpression('/<(table|input|select|textarea)[^>]*\brounded(-[a-z0-9]+)?\b/', $html, $url);
        }
    }

    public function test_dashboard_kpis_use_display_numerals(): void
    {
        $this->actingAs(User::factory()->create());
        $html = $this->get(route('showroom.dashboard'))->assertOk()->getContent();
        preg_match_all('/<p[^>]*data-kpi[^>]*>/', $html, $m);
        $this->assertCount(7, $m[0]);
        foreach ($m[0] as $tag) {
            $this->assertStringContainsString('a18-display', $tag);
        }
    }

    public function test_staff_pages_use_light_touch_rules(): void
    {
        $this->actingAs(User::factory()->create());
        $car = Car::where('status', 'available')->firstOrFail();
        $routes = [
            route('showroom.dashboard'), route('showroom.cars.index'), route('showroom.cars.index', ['tab' => 'requests']),
            route('showroom.cars.index', ['tab' => 'testdrive']), route('showroom.cars.create'), route('showroom.cars.sale.create', $car),
            route('showroom.products.index'), route('showroom.suppliers.index'), route('showroom.reports', ['period' => 'all']), route('showroom.settings'), route('showroom.pos'),
        ];
        foreach ($routes as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('rounded-3xl', $html, $url);
            $this->assertStringNotContainsString('font-mono', $html, $url);
            $this->assertDoesNotMatchRegularExpression('/text-\[(9|10|11)px\]/', $html, $url);
        }
    }

    public function test_receipt_keeps_printable_paper_and_stamp(): void
    {
        $this->actingAs(User::factory()->create());
        $car = Car::where('status', 'available')->firstOrFail();
        $trx = app(\App\Services\CarSaleService::class)->sell($car, ['customer_name' => 'Budi', 'payment_method' => 'transfer']);
        $this->get(route('showroom.receipt', $trx))->assertOk()
            ->assertSee('id="printable-receipt"', false)
            ->assertSee('a18-stamp', false);
    }

    public function test_card_title_link_covers_whole_card_including_photo(): void
    {
        $car = Car::where('status', 'available')->firstOrFail();
        $html = view('partials.car-card', ['car' => $car, 'gridNo' => 1])->render();
        // Lapisan klik "after:absolute inset-0" pada tautan judul harus menempel ke <article>,
        // jadi tidak boleh ada pembungkus ber-position (relative/absolute) di antara keduanya.
        $this->assertMatchesRegularExpression('/<div data-card-info class="(?![^"]*\b(relative|absolute)\b)[^"]*"/', $html);
    }

    public function test_slanted_buttons_keep_a_visible_focus_ring(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        preg_match('/\.a18-btn-primary \{(.*?)\}/s', $css, $m);
        // clip-path memotong ring di luar kotak, jadi ring fokus harus di dalam (inset)
        $this->assertStringContainsString('focus-visible:ring-inset', $m[1]);
        $this->assertStringNotContainsString('ring-offset', $m[1]);
        $home = $this->get(route('cars.index'))->getContent();
        preg_match_all('/<a [^>]*class="[^"]*a18-cut-tag[^"]*"/', $home, $links);
        foreach ($links[0] as $tag) {
            $this->assertStringContainsString('focus-visible:ring-inset', $tag);
        }
    }

    public function test_home_stats_put_label_before_number_for_screen_readers(): void
    {
        $html = $this->get(route('cars.index'))->getContent();
        preg_match_all('/<(dt|dd)\b/', $html, $m);
        $this->assertNotEmpty($m[1]);
        $this->assertSame('dt', $m[1][0]);
        for ($i = 0; $i < count($m[1]); $i += 2) {
            $this->assertSame(['dt', 'dd'], [$m[1][$i], $m[1][$i + 1] ?? null]);
        }
    }

    public function test_layout_loads_upright_black_weight_of_barlow_condensed(): void
    {
        $this->get(route('cars.index'))->assertSee('family=Barlow+Condensed:ital,wght@0,600;0,700;0,800;0,900;', false);
    }

    public function test_sell_stepper_upcoming_labels_have_readable_contrast(): void
    {
        $this->get(route('sell.create'))->assertDontSee("'text-white/45'", false);
    }

    public function test_booked_card_photo_stays_in_colour(): void
    {
        $car = Car::where('status', 'booked')->firstOrFail();
        $html = view('partials.car-card', ['car' => $car, 'gridNo' => 1])->render();
        $this->assertStringNotContainsString('grayscale', $html);
    }

    public function test_sold_prices_are_not_struck_through(): void
    {
        $car = Car::where('status', 'available')->firstOrFail();
        $car->update(['status' => 'sold', 'sold_price' => 230_000_000, 'sold_at' => now()->subDay()]);
        $card = view('partials.car-card', ['car' => $car->fresh(), 'gridNo' => 1])->render();
        $this->assertStringNotContainsString('line-through', $card);
        $this->get(route('cars.show', $car))->assertOk()->assertDontSee('line-through', false);
    }

    public function test_active_filter_chips_and_brand_chips_render_together(): void
    {
        $html = $this->get(route('cars.index', ['brand' => ['Honda']]))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<a [^>]*aria-label="Hapus filter[^"]*Honda/', $html);
        $this->assertMatchesRegularExpression('/<a [^>]*aria-pressed="true"[^>]*class="a18-skew/s', $html);
    }

    public function test_home_keeps_all_six_sort_options(): void
    {
        $html = $this->get(route('cars.index'))->getContent();
        preg_match('/<select[^>]*name="sort"[^>]*>(.*?)<\/select>/s', $html, $m);
        $this->assertSame(6, substr_count($m[1] ?? '', '<option'));
    }
}
