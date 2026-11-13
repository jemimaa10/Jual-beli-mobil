<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesShowroomData;
use Tests\TestCase;

class SupplierStockInTest extends TestCase
{
    use MakesShowroomData, RefreshDatabase;

    private Supplier $supplier;

    private Product $a;

    private Product $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->staff());
        $this->supplier = Supplier::create(['name' => 'PT Sinar Pelumas Nusantara', 'contact_person' => 'Bpk. Rudi', 'phone' => '0812-7788-1801']);
        $this->a = Product::create(['sku' => 'A-1', 'name' => 'Oli A', 'unit' => 'botol', 'buy_price' => 50_000, 'sell_price' => 65_000, 'stock' => 10, 'min_stock' => 5]);
        $this->b = Product::create(['sku' => 'B-1', 'name' => 'Aki B', 'unit' => 'pcs', 'buy_price' => 700_000, 'sell_price' => 820_000, 'stock' => 2, 'min_stock' => 3]);
    }

    public function test_page_lists_suppliers(): void
    {
        $this->get(route('showroom.suppliers.index'))->assertOk()->assertSee('PT Sinar Pelumas Nusantara')->assertSee('Catat Barang Masuk');
    }

    public function test_supplier_create_update_delete(): void
    {
        $this->post(route('showroom.suppliers.store'), ['name' => 'CV Aki Jaya', 'contact_person' => 'Ibu Sari', 'phone' => '021-555', 'address' => 'Jl. Daan Mogot'])->assertSessionHasNoErrors();
        $created = Supplier::where('name', 'CV Aki Jaya')->firstOrFail();

        $this->put(route('showroom.suppliers.update', $created), ['name' => 'CV Aki Jaya Abadi', 'phone' => '021-556'])->assertSessionHasNoErrors();
        $this->assertSame('CV Aki Jaya Abadi', $created->fresh()->name);

        $this->post(route('showroom.suppliers.store'), ['name' => ''])->assertSessionHasErrors('name');

        $this->a->update(['supplier_id' => $created->id]);
        $this->delete(route('showroom.suppliers.destroy', $created))->assertSessionHas('success');
        $this->assertNull(Supplier::find($created->id));
        $this->assertNull($this->a->fresh()->supplier_id);
        $this->get(route('showroom.products.index'))->assertOk()->assertSee('Oli A');
    }

    public function test_stock_in_adds_quantities_and_updates_cost_when_given(): void
    {
        $this->post(route('showroom.stock-in'), [
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'INV-A18-0001',
            'items' => [
                ['product_id' => $this->a->id, 'qty' => '12', 'buy_price' => '52000'],
                ['product_id' => $this->b->id, 'qty' => '4', 'buy_price' => ''],
            ],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(22, $this->a->fresh()->stock);
        $this->assertSame(52_000, $this->a->fresh()->buy_price);
        $this->assertSame(6, $this->b->fresh()->stock);
        $this->assertSame(700_000, $this->b->fresh()->buy_price, 'harga modal tidak berubah bila kosong');
        $this->assertSame($this->supplier->id, $this->a->fresh()->supplier_id);
    }

    public function test_stock_in_is_all_or_nothing(): void
    {
        $this->post(route('showroom.stock-in'), [
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['product_id' => $this->a->id, 'qty' => '12'],
                ['product_id' => 999999, 'qty' => '1'],
            ],
        ])->assertSessionHasErrors('items.1.product_id');

        $this->assertSame(10, $this->a->fresh()->stock);
    }

    public function test_stock_in_validation(): void
    {
        $line = fn (array $o) => ['supplier_id' => $this->supplier->id, 'items' => [array_merge(['product_id' => $this->a->id, 'qty' => '1'], $o)]];

        $this->post(route('showroom.stock-in'), $line(['qty' => '0']))->assertSessionHasErrors('items.0.qty');
        $this->post(route('showroom.stock-in'), $line(['qty' => '-4']))->assertSessionHasErrors('items.0.qty');
        $this->post(route('showroom.stock-in'), $line(['qty' => 'dua']))->assertSessionHasErrors('items.0.qty');
        $this->post(route('showroom.stock-in'), $line(['buy_price' => '-1']))->assertSessionHasErrors('items.0.buy_price');
        $this->post(route('showroom.stock-in'), $line(['buy_price' => '99999999999999999999']))->assertSessionHasErrors('items.0.buy_price');
        $this->post(route('showroom.stock-in'), ['supplier_id' => $this->supplier->id])->assertSessionHasErrors('items');
        $this->post(route('showroom.stock-in'), ['supplier_id' => 99999, 'items' => [['product_id' => $this->a->id, 'qty' => '1']]])->assertSessionHasErrors('supplier_id');

        $this->assertSame(10, $this->a->fresh()->stock);
    }

    public function test_supplier_pages_require_login(): void
    {
        auth()->logout();

        $this->get(route('showroom.suppliers.index'))->assertRedirect('/login');
        $this->post(route('showroom.stock-in'), ['supplier_id' => $this->supplier->id, 'items' => [['product_id' => $this->a->id, 'qty' => '5']]])->assertRedirect('/login');
        $this->assertSame(10, $this->a->fresh()->stock);
    }
}
