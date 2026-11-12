<?php

namespace App\Http\Controllers\Showroom;

use App\Exceptions\SaleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\RetailSaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        return view('showroom.pos', [
            'products' => Product::query()->with('category:id,name')->orderBy('name')->get()->map(fn (Product $p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'category_id' => $p->category_id,
                'category_name' => $p->category?->name,
                'unit' => $p->unit,
                'sell_price' => (int) $p->sell_price,
                'stock' => (int) $p->stock,
                'min_stock' => (int) $p->min_stock,
                'barcode' => $p->barcode,
            ])->values(),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function checkout(CheckoutRequest $request, RetailSaleService $sales): RedirectResponse
    {
        $data = $request->validated();

        try {
            $trx = $sales->checkout($data['items'], $data);
        } catch (SaleException $e) {
            // Kembali ke kasir dengan pesan; keranjang di browser tetap utuh supaya bisa diperbaiki
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('showroom.receipt', $trx)->with('success', 'Transaksi berhasil disimpan.');
    }
}
