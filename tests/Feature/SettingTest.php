<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RetailSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@auto18.test')->firstOrFail();
        $this->actingAs($this->admin);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Auto18 Kelapa Gading', 'tagline' => 'Mobil Second Terpercaya', 'address' => 'Jl. Boulevard 18, Jakarta Utara',
            'phone' => '021-4500018', 'call_center' => '0800-1-181818', 'whatsapp' => '6281218181818', 'email' => 'halo@auto18.id',
            'instagram' => 'auto18kg', 'tiktok' => 'auto18kg', 'youtube' => 'Auto18 KG', 'support_partner' => 'LEMONAIDE',
            'cashier_name' => 'Kasir Dina', 'receipt_footer' => "Terima kasih!\nSampai jumpa lagi.",
            'tax_enabled' => '1', 'tax_rate' => '11', 'credit_rate' => '6.5', 'min_dp_percent' => '25',
        ], $overrides);
    }

    public function test_settings_page_shows_current_values(): void
    {
        $this->get(route('showroom.settings'))->assertOk()->assertSee('Nama Showroom')->assertSee('021-31172266');
    }

    public function test_update_saves_every_field(): void
    {
        $this->put(route('showroom.settings.update'), $this->payload())->assertSessionHasNoErrors()->assertSessionHas('success');

        $s = Setting::current();
        $this->assertSame('Auto18 Kelapa Gading', $s->name);
        $this->assertSame('6281218181818', $s->whatsapp);
        $this->assertSame('Kasir Dina', $s->cashier_name);
        $this->assertTrue($s->tax_enabled);
        $this->assertSame(6.5, $s->credit_rate);
        $this->assertSame(25.0, $s->min_dp_percent);
        $this->assertSame("Terima kasih!\nSampai jumpa lagi.", str_replace("\r", '', $s->receipt_footer));
        $this->assertSame(1, Setting::count());
    }

    public function test_unchecked_tax_box_turns_tax_off(): void
    {
        Setting::current()->update(['tax_enabled' => true]);
        $payload = $this->payload();
        unset($payload['tax_enabled']);

        $this->put(route('showroom.settings.update'), $payload)->assertSessionHasNoErrors();

        $this->assertFalse(Setting::current()->tax_enabled);
    }

    public function test_validation(): void
    {
        $this->put(route('showroom.settings.update'), $this->payload(['name' => '']))->assertSessionHasErrors('name');
        $this->put(route('showroom.settings.update'), $this->payload(['tax_rate' => '101']))->assertSessionHasErrors('tax_rate');
        $this->put(route('showroom.settings.update'), $this->payload(['tax_rate' => '-1']))->assertSessionHasErrors('tax_rate');
        $this->put(route('showroom.settings.update'), $this->payload(['min_dp_percent' => '150']))->assertSessionHasErrors('min_dp_percent');
        $this->put(route('showroom.settings.update'), $this->payload(['credit_rate' => 'tinggi']))->assertSessionHasErrors('credit_rate');
        $this->put(route('showroom.settings.update'), $this->payload(['email' => 'bukan-email']))->assertSessionHasErrors('email');
        $this->put(route('showroom.settings.update'), $this->payload(['whatsapp' => '0812-abc']))->assertSessionHasErrors('whatsapp');

        $this->assertSame('Auto18', Setting::current()->name);
    }

    public function test_new_settings_reach_the_public_site_the_cashier_and_receipts(): void
    {
        $this->put(route('showroom.settings.update'), $this->payload());

        // tombol WhatsApp di detail mobil
        $car = Car::where('status', 'available')->firstOrFail();
        $this->get(route('cars.show', $car))->assertSee('wa.me/6281218181818', false)->assertSee('Chat WhatsApp');
        // footer publik
        $this->get('/')->assertSee('0800-1-181818');

        // kasir memakai PPN & nama kasir baru; struk memakai kaki struk baru
        $product = Product::firstOrFail();
        $trx = app(RetailSaleService::class)->checkout([['product_id' => $product->id, 'qty' => 1]], ['payment_method' => 'transfer']);
        $this->assertSame('Kasir Dina', $trx->cashier);
        $this->assertSame((int) round($product->sell_price * 0.11), $trx->tax);
        $this->get(route('showroom.receipt', $trx))->assertSee('Sampai jumpa lagi.')->assertSee('Auto18 Kelapa Gading');
    }

    public function test_reset_restores_demo_data_and_keeps_the_admin_logged_in(): void
    {
        Setting::current()->update(['name' => 'Berubah']);
        Car::query()->where('code', 'A18-0001')->delete();
        Product::query()->update(['stock' => 0]);
        app(RetailSaleService::class);

        $this->post(route('showroom.settings.reset'))->assertSessionHas('success');

        $this->assertSame(12, Car::count());
        $this->assertSame('Auto18', Setting::current()->name);
        $this->assertSame(5, Transaction::count());
        $this->assertGreaterThan(0, Product::where('sku', 'OLI-1001')->value('stock'));
        $this->assertSame(1, User::count());
        $this->assertAuthenticatedAs($this->admin);
        $this->get(route('showroom.dashboard'))->assertOk();
    }

    public function test_settings_require_login(): void
    {
        auth()->logout();

        $this->get(route('showroom.settings'))->assertRedirect('/login');
        $this->put(route('showroom.settings.update'), $this->payload())->assertRedirect('/login');
        $this->post(route('showroom.settings.reset'))->assertRedirect('/login');
        $this->assertSame('Auto18', Setting::current()->name);
    }
}
