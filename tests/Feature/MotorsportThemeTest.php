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
}
