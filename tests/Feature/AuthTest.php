<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_showroom(): void
    {
        $this->get('/showroom')->assertRedirect('/login');
    }

    public function test_admin_can_log_in(): void
    {
        $user = User::factory()->create(['email' => 'admin@auto18.test', 'password' => 'auto18admin']);

        $this->post(route('login.attempt'), ['email' => 'admin@auto18.test', 'password' => 'auto18admin'])
            ->assertRedirect('/showroom');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'admin@auto18.test', 'password' => 'auto18admin']);

        $this->from('/login')
            ->post(route('login.attempt'), ['email' => 'admin@auto18.test', 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_there_is_no_register_route(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
