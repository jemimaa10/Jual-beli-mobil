<?php

namespace Tests\Feature;

use App\Exceptions\SaleException;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\RetailSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesShowroomData;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use MakesShowroomData, RefreshDatabase;

    private Product $oli;   // A: jual 335.000, modal 285.000, stok 10

    private Product $parfum; // B: jual 30.000, modal 18.000, stok 5

    protected function setUp(): void
    {
        parent::setUp();
        $this->oli = Product::create(['sku' => 'OLI-1001', 'name' => 'Oli Mesin Shell Helix HX7 10W-40 4L', 'unit' => 'botol', 'buy_price' => 285_000, 'sell_price' => 335_000, 'stock' => 10, 'min_stock' => 5]);
        $this->parfum = Product::create(['sku' => 'INT-4002', 'name' => 'Parfum Mobil Gantung Aroma Ocean', 'unit' => 'pcs', 'buy_price' => 18_000, 'sell_price' => 30_000, 'stock' => 5, 'min_stock' => 5]);
    }

    private function cart(int $olis = 1, int $parfums = 2): array
    {
        return array_values(array_filter([
            $olis ? ['product_id' => $this->oli->id, 'qty' => $olis] : null,
            $parfums ? ['product_id' => $this->parfum->id, 'qty' => $parfums] : null,
        ]));
    }

    private function checkout(array $items, array $data = []): Transaction
    {
        return app(RetailSaleService::class)->checkout($items, array_merge(['payment_method' => 'cash', 'paid_amount' => 400_000], $data));
    }

    private function assertRefused(callable $action, string $contains): void
    {
        try {
            $action();
            $this->fail('SaleException tidak dilempar');
        } catch (SaleException $e) {
            $this->assertStringContainsString($contains, $e->getMessage());
        }
    }

    public function test_plain_cash_checkout(): void
    {
        $trx = $this->checkout($this->cart());

        $this->assertSame('retail', $trx->sale_type);
        $this->assertSame(395_000, $trx->subtotal);
        $this->assertSame(0, $trx->discount);
        $this->assertSame(0, $trx->tax);
        $this->assertSame(395_000, $trx->total);
        $this->assertSame(400_000, $trx->paid_amount);
        $this->assertSame(5_000, $trx->change_amount);
        $this->assertSame(74_000, $trx->total_profit);
        $this->assertSame('Pelanggan Umum', $trx->customer_name);
        $this->assertSame(Setting::current()->cashier_name, $trx->cashier);
        $this->assertSame('completed', $trx->status);

        $this->assertSame(9, $this->oli->fresh()->stock);
        $this->assertSame(3, $this->parfum->fresh()->stock);

        $this->assertCount(2, $trx->items);
        $line = $trx->items->firstWhere('sku', 'OLI-1001');
        $this->assertSame('Oli Mesin Shell Helix HX7 10W-40 4L', $line->name);
        $this->assertSame(335_000, $line->price);
        $this->assertSame(285_000, $line->buy_price);
        $this->assertSame(335_000, $line->subtotal);
        $this->assertSame($this->oli->id, $line->product_id);
    }

    public function test_percent_discount_and_tax(): void
    {
        Setting::current()->update(['tax_enabled' => true, 'tax_rate' => 11]);

        $trx = $this->checkout($this->cart(), ['discount_type' => 'percent', 'discount_value' => 10, 'paid_amount' => 500_000]);

        $this->assertSame(39_500, $trx->discount);
        $this->assertSame(39_105, $trx->tax);
        $this->assertSame(394_605, $trx->total);
        $this->assertSame(34_500, $trx->total_profit);
        $this->assertSame(105_395, $trx->change_amount);
    }

    public function test_nominal_discount_is_capped_at_the_subtotal(): void
    {
        $trx = $this->checkout($this->cart(), ['discount_type' => 'nominal', 'discount_value' => 9_999_999, 'paid_amount' => null]);

        $this->assertSame(395_000, $trx->discount);
        $this->assertSame(0, $trx->total);
    }

    public function test_percent_is_clamped_between_0_and_100(): void
    {
        $over = $this->checkout($this->cart(1, 0), ['discount_type' => 'percent', 'discount_value' => 250, 'paid_amount' => null]);
        $this->assertSame(335_000, $over->discount);

        $under = $this->checkout($this->cart(1, 0), ['discount_type' => 'percent', 'discount_value' => -20, 'paid_amount' => 400_000]);
        $this->assertSame(0, $under->discount);
    }

    public function test_quantity_above_stock_fails_the_whole_checkout(): void
    {
        $this->assertRefused(fn () => $this->checkout($this->cart(1, 6)), 'Parfum Mobil Gantung Aroma Ocean');

        $this->assertSame(10, $this->oli->fresh()->stock, 'stok barang lain tidak ikut berkurang');
        $this->assertSame(5, $this->parfum->fresh()->stock);
        $this->assertSame(0, Transaction::count());
    }

    public function test_the_same_product_twice_in_a_cart_is_summed_against_stock(): void
    {
        $items = [['product_id' => $this->parfum->id, 'qty' => 3], ['product_id' => $this->parfum->id, 'qty' => 3]];

        $this->assertRefused(fn () => $this->checkout($items), 'Parfum Mobil Gantung Aroma Ocean');
        $this->assertSame(5, $this->parfum->fresh()->stock);

        $ok = $this->checkout([['product_id' => $this->parfum->id, 'qty' => 2], ['product_id' => $this->parfum->id, 'qty' => 3]], ['paid_amount' => 150_000]);
        $this->assertSame(150_000, $ok->subtotal);
        $this->assertCount(1, $ok->items);
        $this->assertSame(0, $this->parfum->fresh()->stock);
    }

    public function test_cash_underpaid_is_refused(): void
    {
        $this->assertRefused(fn () => $this->checkout($this->cart(), ['paid_amount' => 100_000]), 'Nominal uang bayar masih kurang');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(10, $this->oli->fresh()->stock);
    }

    public function test_qris_and_transfer_are_paid_in_full(): void
    {
        foreach (['qris', 'transfer'] as $method) {
            $trx = $this->checkout($this->cart(1, 0), ['payment_method' => $method, 'paid_amount' => null]);

            $this->assertSame($method, $trx->payment_method);
            $this->assertSame(335_000, $trx->paid_amount);
            $this->assertSame(0, $trx->change_amount);
        }
    }

    public function test_empty_cart_and_bad_lines_are_refused(): void
    {
        $this->assertRefused(fn () => $this->checkout([]), 'Keranjang');
        $this->assertRefused(fn () => $this->checkout([['product_id' => $this->oli->id, 'qty' => 0]]), 'Jumlah');
        $this->assertRefused(fn () => $this->checkout([['product_id' => $this->oli->id, 'qty' => -2]]), 'Jumlah');
        $this->assertRefused(fn () => $this->checkout([['product_id' => 999999, 'qty' => 1]]), 'tidak ditemukan');

        $this->assertSame(0, Transaction::count());
    }

    public function test_http_checkout_redirects_to_the_receipt(): void
    {
        $this->actingAs($this->staff());

        $response = $this->post(route('showroom.pos.checkout'), [
            'items' => $this->cart(),
            'payment_method' => 'cash',
            'paid_amount' => '400000',
            'customer_name' => 'Ibu Rahayu',
        ]);

        $trx = Transaction::firstOrFail();
        $response->assertRedirect(route('showroom.receipt', $trx));
        $this->assertSame('Ibu Rahayu', $trx->customer_name);
        $this->get(route('showroom.receipt', $trx))->assertOk()->assertSee('Oli Mesin Shell Helix HX7 10W-40 4L')->assertSee('Kembalian');
    }

    public function test_http_ignores_prices_sent_by_the_browser(): void
    {
        $this->actingAs($this->staff());

        $this->post(route('showroom.pos.checkout'), [
            'items' => [['product_id' => $this->oli->id, 'qty' => 1, 'price' => 1, 'buy_price' => 1]],
            'payment_method' => 'transfer',
            'price' => 1,
            'total' => 1,
        ]);

        $trx = Transaction::firstOrFail();
        $this->assertSame(335_000, $trx->total);
        $this->assertSame(285_000, $trx->items->first()->buy_price);
    }

    public function test_http_validation_errors_instead_of_500s(): void
    {
        $this->actingAs($this->staff());
        $base = ['items' => $this->cart(), 'payment_method' => 'cash'];

        $this->post(route('showroom.pos.checkout'), $base + ['paid_amount' => 'abc'])->assertSessionHasErrors('paid_amount');
        $this->post(route('showroom.pos.checkout'), $base + ['paid_amount' => '99999999999999999999'])->assertSessionHasErrors('paid_amount');
        $this->post(route('showroom.pos.checkout'), $base + ['paid_amount' => '-5'])->assertSessionHasErrors('paid_amount');
        $this->post(route('showroom.pos.checkout'), $base + ['discount_type' => 'percent', 'discount_value' => 'banyak'])->assertSessionHasErrors('discount_value');
        $this->post(route('showroom.pos.checkout'), ['payment_method' => 'cash'])->assertSessionHasErrors('items');
        $this->post(route('showroom.pos.checkout'), ['items' => [['product_id' => $this->oli->id, 'qty' => 0]], 'payment_method' => 'cash'])->assertSessionHasErrors('items.0.qty');
        $this->post(route('showroom.pos.checkout'), ['items' => [['product_id' => 999999, 'qty' => 1]], 'payment_method' => 'cash'])->assertSessionHasErrors('items.0.product_id');
        $this->post(route('showroom.pos.checkout'), array_merge($base, ['payment_method' => 'bitcoin']))->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(10, $this->oli->fresh()->stock);
    }

    public function test_http_stock_change_after_loading_the_page_fails_the_checkout(): void
    {
        $this->actingAs($this->staff());
        $this->parfum->update(['stock' => 1]); // stok berkurang sejak kasir membuka halaman

        $this->from(route('showroom.pos'))
            ->post(route('showroom.pos.checkout'), ['items' => $this->cart(1, 2), 'payment_method' => 'cash', 'paid_amount' => '400000'])
            ->assertRedirect(route('showroom.pos'))
            ->assertSessionHas('error');

        $this->assertStringContainsString('Parfum Mobil Gantung Aroma Ocean', session('error'));
        $this->assertSame(10, $this->oli->fresh()->stock);
        $this->assertSame(0, Transaction::count());
    }

    public function test_the_pos_page_lists_products_for_staff_only(): void
    {
        $this->get(route('showroom.pos'))->assertRedirect('/login');

        $this->actingAs($this->staff());
        $this->get(route('showroom.pos'))->assertOk()->assertSee('Kasir Aksesoris')->assertSee('Oli Mesin Shell Helix HX7 10W-40 4L');
        $this->post(route('showroom.pos.checkout'), [])->assertSessionHasErrors('items');
    }

    public function test_guests_cannot_check_out(): void
    {
        $this->post(route('showroom.pos.checkout'), ['items' => $this->cart(), 'payment_method' => 'cash'])->assertRedirect('/login');

        $this->assertSame(0, Transaction::count());
    }
}
