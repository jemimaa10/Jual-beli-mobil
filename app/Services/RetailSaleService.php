<?php

namespace App\Services;

use App\Exceptions\SaleException;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class RetailSaleService
{
    /**
     * Checkout kasir aksesoris. Harga dan modal selalu dibaca dari database (bukan dari browser),
     * stok dicek & dikurangi dalam satu transaksi: kalau satu barang kurang, seluruh checkout batal.
     *
     * @param  list<array{product_id:mixed, qty:mixed}>  $items
     * @param  array{discount_type?:string, discount_value?:mixed, payment_method?:string, paid_amount?:mixed, customer_name?:?string}  $data
     *
     * @throws SaleException
     */
    public function checkout(array $items, array $data): Transaction
    {
        if ($items === []) {
            throw new SaleException('Keranjang masih kosong.');
        }

        // Barang yang sama di dua baris dijumlahkan, supaya batas stok dicek terhadap totalnya
        $wanted = [];
        foreach ($items as $line) {
            $qty = $line['qty'] ?? null;
            if (! is_numeric($qty) || (int) $qty != $qty || $qty < 1) {
                throw new SaleException('Jumlah barang harus berupa bilangan bulat minimal 1.');
            }
            $id = $line['product_id'] ?? null;
            $wanted[$id] = ($wanted[$id] ?? 0) + (int) $qty;
        }

        return DB::transaction(function () use ($wanted, $data) {
            $products = Product::query()->whereIn('id', array_keys($wanted))->lockForUpdate()->get()->keyBy('id');
            $settings = Setting::current();

            $subtotal = 0;
            $profit = 0;
            foreach ($wanted as $id => $qty) {
                $product = $products->get($id);
                if (! $product) {
                    throw new SaleException('Barang tidak ditemukan. Muat ulang halaman kasir.');
                }
                if ($product->stock < $qty) {
                    throw new SaleException("Stok {$product->name} tidak cukup (tersisa {$product->stock}).");
                }
                $subtotal += (int) $product->sell_price * $qty;
                $profit += ((int) $product->sell_price - (int) $product->buy_price) * $qty;
            }

            // Diskon dihitung dari subtotal dan tidak pernah melebihi subtotal
            $value = $data['discount_value'] ?? 0;
            $value = is_numeric($value) ? (float) $value : 0.0;
            $raw = ($data['discount_type'] ?? 'nominal') === 'percent'
                ? round($subtotal * min(100.0, max(0.0, $value)) / 100)
                : max(0.0, $value);
            $discount = (int) min($subtotal, $raw);

            $tax = $settings->tax_enabled ? (int) round(max(0, $subtotal - $discount) * ((float) $settings->tax_rate / 100)) : 0;
            $total = max(0, $subtotal - $discount + $tax);

            $method = $data['payment_method'] ?? 'cash';
            $paid = $total;
            $change = 0;
            if ($method === 'cash') {
                $received = $data['paid_amount'] ?? null;
                $received = ($received === null || $received === '') ? $total : $received;
                if (! is_numeric($received) || $received < $total) {
                    throw new SaleException('Nominal uang bayar masih kurang!');
                }
                $paid = (int) $received;
                $change = $paid - $total;
            }

            $trx = Transaction::create([
                'code' => CodeGenerator::transaction(),
                'date' => now(),
                'sale_type' => 'retail',
                'customer_name' => trim((string) ($data['customer_name'] ?? '')) ?: 'Pelanggan Umum',
                'cashier' => $settings->cashier_name ?: 'Admin Showroom',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'amount_due' => $total,
                'payment_method' => $method,
                'paid_amount' => $paid,
                'change_amount' => $change,
                'total_profit' => $profit - $discount,
                'status' => 'completed',
            ]);

            foreach ($wanted as $id => $qty) {
                $product = $products->get($id);
                $trx->items()->create([
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'unit' => $product->unit,
                    'price' => $product->sell_price,
                    'buy_price' => $product->buy_price,
                    'qty' => $qty,
                    'subtotal' => (int) $product->sell_price * $qty,
                ]);
                $product->decrement('stock', $qty);
            }

            return $trx;
        });
    }
}
