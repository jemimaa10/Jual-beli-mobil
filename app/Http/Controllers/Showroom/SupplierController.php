<?php

namespace App\Http\Controllers\Showroom;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        return view('showroom.suppliers', [
            'suppliers' => Supplier::query()->withCount('products')->orderBy('name')->get(),
            'products' => Product::query()->orderBy('name')->get(['id', 'sku', 'name', 'unit', 'stock', 'buy_price'])
                ->map(fn (Product $p) => ['id' => $p->id, 'label' => "{$p->name} ({$p->sku})", 'unit' => $p->unit, 'stock' => (int) $p->stock, 'buy_price' => (int) $p->buy_price])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($this->validated($request));

        return back()->with('success', "Suplier \"{$supplier->name}\" ditambahkan.");
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return back()->with('success', "Suplier \"{$supplier->name}\" diperbarui.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $name = $supplier->name;
        $supplier->delete(); // produk yang pernah dipasok tetap ada

        return back()->with('success', "Suplier \"{$name}\" dihapus.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
        ], ['name.required' => 'Nama suplier wajib diisi.']);
    }
}
