<?php

namespace App\Http\Controllers\Showroom;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $q = is_string($v = $request->query('q')) ? trim($v) : '';
        $categoryId = is_numeric($v = $request->query('category')) ? (int) $v : null;
        $lowOnly = $request->boolean('low');

        $products = Product::query()->with('category:id,name')
            ->when($categoryId, fn ($query, $id) => $query->where('category_id', $id))
            ->when($lowOnly, fn ($query) => $query->whereColumn('stock', '<=', 'min_stock'));
        foreach (preg_split('/\s+/', str_replace(['%', '_'], '', $q), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $products->where(fn ($query) => $query->where('name', 'like', "%{$word}%")->orWhere('sku', 'like', "%{$word}%")->orWhere('barcode', 'like', "%{$word}%"));
        }

        $all = Product::query()->get(['stock', 'min_stock', 'buy_price', 'sell_price']);

        return view('showroom.products', [
            'products' => $products->orderBy('name')->get(),
            'categories' => Category::query()->withCount('products')->orderBy('name')->get(),
            'filters' => ['q' => $q, 'category' => $categoryId, 'low' => $lowOnly],
            'stats' => [
                'total' => $all->count(),
                'low' => $all->filter(fn ($p) => $p->stock <= $p->min_stock)->count(),
                'stockValue' => (int) $all->sum(fn ($p) => $p->stock * $p->buy_price),
                'retailValue' => (int) $all->sum(fn ($p) => $p->stock * $p->sell_price),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::create($this->validated($request));

        return back()->with('success', "Produk \"{$product->name}\" ditambahkan.");
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return back()->with('success', "Produk \"{$product->name}\" diperbarui.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $product->delete(); // item transaksi lama menyimpan salinan nama & harga, jadi nota tetap utuh

        return back()->with('success', "Produk \"{$name}\" dihapus.");
    }

    /** Penyesuaian stok manual: delta positif menambah, negatif mengurangi; tidak pernah di bawah 0. */
    public function adjust(Request $request, Product $product): RedirectResponse
    {
        $delta = (int) $request->validate(
            ['delta' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000']],
            ['delta.not_in' => 'Isi jumlah selain 0.', 'delta.integer' => 'Jumlah harus bilangan bulat, misalnya 12 atau -2.']
        )['delta'];

        DB::transaction(function () use ($product, $delta) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $locked->update(['stock' => max(0, $locked->stock + $delta)]);
        });

        return back()->with('success', "Stok \"{$product->name}\" sekarang {$product->fresh()->stock}.");
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit' => ['nullable', 'string', 'max:30'],
            'buy_price' => ['required', 'integer', 'min:0', 'max:100000000000'],
            'sell_price' => ['required', 'integer', 'min:0', 'max:100000000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:10000000'],
            'min_stock' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'barcode' => ['nullable', 'string', 'max:40'],
        ], [
            'sku.unique' => 'SKU ini sudah dipakai produk lain.',
            'name.required' => 'Nama produk wajib diisi.',
            'buy_price.integer' => 'Harga modal harus berupa angka bulat (Rp).',
            'sell_price.integer' => 'Harga jual harus berupa angka bulat (Rp).',
            'stock.integer' => 'Stok harus berupa bilangan bulat.',
        ]);

        $data['unit'] = trim((string) ($data['unit'] ?? '')) ?: 'pcs';
        $data['min_stock'] = ($data['min_stock'] ?? null) === null ? 5 : (int) $data['min_stock'];

        return $data;
    }
}
