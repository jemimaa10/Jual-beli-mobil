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
}
