<?php

namespace App\Http\Controllers\Showroom;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockInController extends Controller
{
    /** Barang masuk dari suplier: stok bertambah; harga modal diperbarui hanya bila diisi. Semua baris atau tidak sama sekali. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'invoice_no' => ['nullable', 'string', 'max:60'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.buy_price' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
        ], [
            'supplier_id.required' => 'Pilih suplier.',
            'items.required' => 'Tambahkan minimal satu produk.',
            'items.*.qty.min' => 'Jumlah masuk minimal 1.',
            'items.*.qty.integer' => 'Jumlah masuk harus bilangan bulat.',
            'items.*.product_id.exists' => 'Produk tidak ditemukan.',
            'items.*.buy_price.integer' => 'Harga modal harus berupa angka bulat (Rp).',
        ]);

        $units = DB::transaction(function () use ($data) {
            $units = 0;
            foreach ($data['items'] as $line) {
                $product = Product::query()->lockForUpdate()->findOrFail($line['product_id']);
                $changes = ['stock' => $product->stock + (int) $line['qty'], 'supplier_id' => $data['supplier_id']];
                if (($line['buy_price'] ?? null) !== null && $line['buy_price'] !== '') {
                    $changes['buy_price'] = (int) $line['buy_price'];
                }
                $product->update($changes);
                $units += (int) $line['qty'];
            }

            return $units;
        });

        $invoice = filled($data['invoice_no'] ?? null) ? " (faktur {$data['invoice_no']})" : '';

        return back()->with('success', "Berhasil menambah {$units} stok dari " . count($data['items']) . " produk{$invoice}.");
    }
}
