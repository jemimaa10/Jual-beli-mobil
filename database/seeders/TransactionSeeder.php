<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Product;
use App\Models\Transaction;
use Database\Seeders\Concerns\ReadsDemoData;
use Illuminate\Database\Seeder;

/** Contoh transaksi kasir aksesoris dan satu penjualan mobil kredit. */
class TransactionSeeder extends Seeder
{
    use ReadsDemoData;

    public function run(): void
    {
        $productIds = Product::pluck('id', 'sku');

        foreach ($this->demo('transactions') as $row) {
            $isCar = $row['saleType'] === 'car';
            $carId = $isCar ? $this->carId($row['car']['id']) : null;

            $trx = Transaction::create([
                'code' => $row['code'],
                'date' => $this->when($row['date']),
                'sale_type' => $row['saleType'],
                'customer_name' => $row['customerName'],
                'customer_phone' => $row['customerPhone'] ?? null,
                'cashier' => $row['cashier'],
                'subtotal' => $row['subtotal'],
                'discount' => $row['discount'],
                'tax' => $row['tax'],
                'total' => $row['total'],
                'amount_due' => $row['amountDue'] ?? $row['total'],
                'payment_method' => $row['paymentMethod'],
                'paid_amount' => $row['paidAmount'],
                'change_amount' => $row['changeAmount'],
                'total_profit' => $row['totalProfit'],
                'status' => $row['status'] ?? 'completed',
                'car_id' => $carId,
                'car_snapshot' => $isCar ? $this->snake($row['car']) : null,
                'credit' => ! empty($row['credit']) ? $this->snake($row['credit']) : null,
                'trade_in' => ! empty($row['tradeIn']) ? $this->snake($row['tradeIn']) : null,
                'notes' => $row['notes'] ?? null,
            ]);

            foreach ($row['items'] as $item) {
                $trx->items()->create([
                    'product_id' => $isCar ? null : ($productIds[$item['sku']] ?? null),
                    'sku' => $item['sku'],
                    'name' => $item['name'],
                    'unit' => $item['unit'],
                    'price' => $item['price'],
                    'buy_price' => $item['buyPrice'] ?? 0,
                    'qty' => $item['qty'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            // Mobil yang sudah terjual menunjuk balik ke transaksinya
            if ($carId) {
                Car::whereKey($carId)->update(['sold_transaction_id' => $trx->id]);
            }
        }
    }
}
