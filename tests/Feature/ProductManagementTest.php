<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\RetailSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesShowroomData;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use MakesShowroomData, RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->staff());
        $this->category = Category::create(['name' => 'Oli & Pelumas']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'sku' => 'OLI-9001', 'name' => 'Oli Uji 1L', 'category_id' => $this->category->id, 'unit' => 'botol',
            'buy_price' => '50000', 'sell_price' => '65000', 'stock' => '12', 'min_stock' => '', 'barcode' => '899000111',
        ], $overrides);
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge(['sku' => 'OLI-1', 'name' => 'Oli Lama', 'category_id' => $this->category->id, 'unit' => 'botol', 'buy_price' => 50_000, 'sell_price' => 65_000, 'stock' => 10, 'min_stock' => 5], $overrides));
    }

    public function test_page_lists_products_with_margin_and_low_stock_marker(): void
    {
        $this->product(['name' => 'Oli Aman', 'sku' => 'A-1', 'stock' => 20]);
        $this->product(['name' => 'Oli Tipis', 'sku' => 'A-2', 'stock' => 5]);

        $response = $this->get(route('showroom.products.index'))->assertOk()
            ->assertSee('Oli Aman')->assertSee('Oli Tipis')->assertSee('Rp 15.000');

        $this->assertSame(1, substr_count($response->getContent(), 'Stok menipis'));

        $this->get(route('showroom.products.index', ['q' => 'tipis']))->assertSee('Oli Tipis')->assertDontSee('Oli Aman');
        $this->get(route('showroom.products.index', ['low' => 1]))->assertSee('Oli Tipis')->assertDontSee('Oli Aman');
        $this->get(route('showroom.products.index', ['category' => 'x', 'q' => ['a']]))->assertOk();
    }

    public function test_creating_a_product_defaults_min_stock_to_five(): void
    {
        $this->post(route('showroom.products.store'), $this->payload())->assertSessionHasNoErrors();

        $product = Product::where('sku', 'OLI-9001')->firstOrFail();
        $this->assertSame(5, $product->min_stock);
        $this->assertSame(12, $product->stock);
        $this->assertSame(65_000, $product->sell_price);
        $this->assertSame($this->category->id, $product->category_id);
    }

    public function test_product_validation(): void
    {
        $this->product(['sku' => 'OLI-9001']);

        $this->post(route('showroom.products.store'), $this->payload())->assertSessionHasErrors('sku');
        $this->post(route('showroom.products.store'), $this->payload(['sku' => 'X-1', 'name' => '']))->assertSessionHasErrors('name');
        $this->post(route('showroom.products.store'), $this->payload(['sku' => 'X-1', 'sell_price' => 'abc']))->assertSessionHasErrors('sell_price');
        $this->post(route('showroom.products.store'), $this->payload(['sku' => 'X-1', 'buy_price' => '-1']))->assertSessionHasErrors('buy_price');
        $this->post(route('showroom.products.store'), $this->payload(['sku' => 'X-1', 'stock' => '1.5']))->assertSessionHasErrors('stock');
        $this->post(route('showroom.products.store'), $this->payload(['sku' => 'X-1', 'category_id' => 99999]))->assertSessionHasErrors('category_id');

        $this->assertSame(1, Product::count());
    }

    public function test_updating_a_product_may_keep_its_own_sku(): void
    {
        $product = $this->product(['sku' => 'OLI-9001']);

        $this->put(route('showroom.products.update', $product), $this->payload(['name' => 'Oli Baru', 'sell_price' => '70000']))->assertSessionHasNoErrors();

        $this->assertSame('Oli Baru', $product->fresh()->name);
        $this->assertSame(70_000, $product->fresh()->sell_price);
    }

    public function test_deleting_a_sold_product_keeps_old_receipts_and_reports_readable(): void
    {
        $product = $this->product(['name' => 'Oli Terjual']);
        $trx = app(RetailSaleService::class)->checkout([['product_id' => $product->id, 'qty' => 1]], ['payment_method' => 'transfer']);

        $this->delete(route('showroom.products.destroy', $product))->assertSessionHas('success');

        $this->assertNull(Product::find($product->id));
        $item = $trx->items()->first();
        $this->assertNull($item->product_id);
        $this->assertSame('Oli Terjual', $item->name);
        $this->assertSame(65_000, $item->price);
        $this->get(route('showroom.receipt', $trx))->assertOk()->assertSee('Oli Terjual');
    }

    public function test_manual_stock_adjustment_never_goes_below_zero(): void
    {
        $product = $this->product(['stock' => 10]);

        $this->post(route('showroom.products.adjust', $product), ['delta' => '5']);
        $this->assertSame(15, $product->fresh()->stock);
        $this->post(route('showroom.products.adjust', $product), ['delta' => '-3']);
        $this->assertSame(12, $product->fresh()->stock);
        $this->post(route('showroom.products.adjust', $product), ['delta' => '-100']);
        $this->assertSame(0, $product->fresh()->stock);

        $this->post(route('showroom.products.adjust', $product), ['delta' => 'abc'])->assertSessionHasErrors('delta');
        $this->post(route('showroom.products.adjust', $product), ['delta' => '0'])->assertSessionHasErrors('delta');
        $this->post(route('showroom.products.adjust', $product), ['delta' => '1.5'])->assertSessionHasErrors('delta');
    }

    public function test_categories_can_be_added_and_deleting_one_keeps_its_products(): void
    {
        $this->post(route('showroom.categories.store'), ['name' => 'Ban & Kaki-kaki'])->assertSessionHasNoErrors();
        $this->assertTrue(Category::where('name', 'Ban & Kaki-kaki')->exists());
        $this->post(route('showroom.categories.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->post(route('showroom.categories.store'), ['name' => 'Ban & Kaki-kaki'])->assertSessionHasErrors('name');

        $product = $this->product();
        $this->delete(route('showroom.categories.destroy', $this->category))->assertSessionHas('success');

        $this->assertNull(Category::find($this->category->id));
        $this->assertNull($product->fresh()->category_id);
        $this->get(route('showroom.products.index'))->assertOk()->assertSee('Oli Lama');
    }

    public function test_product_pages_require_login(): void
    {
        auth()->logout();
        $product = $this->product();

        $this->get(route('showroom.products.index'))->assertRedirect('/login');
        $this->delete(route('showroom.products.destroy', $product))->assertRedirect('/login');
        $this->assertNotNull($product->fresh());
    }
}
