<?php

namespace App\Services;

use App\Exceptions\SaleException;
use App\Models\Car;
use App\Models\SellRequest;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class CarSaleService
{
    public function __construct(private SellRequestService $requests)
    {
    }

    /**
     * Menjual satu mobil. Semua aturan uang dicek di sini (bukan di browser) dan seluruh perubahan
     * (transaksi, status mobil, mobil tukar tambah) disimpan bersama atau tidak sama sekali.
     *
     * @param  array{customer_name?:string, customer_phone?:?string, payment_method?:string, discount?:mixed, paid_amount?:mixed,
     *               credit?:array{leasing?:string, dp_percent?:mixed, tenor_months?:mixed, annual_rate?:mixed},
     *               trade_in?:array{request_id?:mixed, car_name?:string, value?:mixed}, notes?:?string}  $data
     *
     * @throws SaleException
     */
    public function sell(Car $car, array $data): Transaction
    {
        return DB::transaction(function () use ($car, $data) {
            // Baris dikunci dan dibaca ulang: dua permintaan serentak tidak bisa menjual unit yang sama
            $car = Car::query()->lockForUpdate()->findOrFail($car->id);
            if ($car->status === 'sold') {
                throw new SaleException('Mobil ini sudah terjual.');
            }

            $settings = Setting::current();
            $method = $data['payment_method'] ?? 'cash';
            $price = (int) $car->price;

            $discount = $data['discount'] ?? 0;
            if (! is_numeric($discount) || $discount < 0 || $discount >= $price) {
                throw new SaleException('Diskon tidak valid atau melebihi harga mobil.');
            }
            $discount = (int) $discount;
            $total = $price - $discount;

            $tradeIn = $data['trade_in'] ?? null;
            $tradeInValue = 0;
            if ($tradeIn && ($tradeIn['value'] ?? 0) !== 0 && ($tradeIn['value'] ?? '') !== '') {
                $raw = $tradeIn['value'];
                if (! is_numeric($raw) || $raw < 0 || $raw > $total) {
                    throw new SaleException('Nilai tukar tambah melebihi total harga mobil.');
                }
                $tradeInValue = (int) $raw;
            }
            $amountDue = $total - $tradeInValue;

            $credit = null;
            $paid = $amountDue;
            $change = 0;

            if ($method === 'credit') {
                $input = $data['credit'] ?? [];
                $dp = $this->number($input['dp_percent'] ?? null, (float) $settings->min_dp_percent);
                $tenor = (int) $this->number($input['tenor_months'] ?? null, 36);
                $rate = $this->number($input['annual_rate'] ?? null, (float) $settings->credit_rate);

                if ($dp < $settings->min_dp_percent) {
                    throw new SaleException('DP minimal ' . $this->plain($settings->min_dp_percent) . '%.');
                }

                $sim = CarPricing::installment($amountDue, $dp, $tenor, $rate);
                $credit = [
                    'leasing' => $input['leasing'] ?? 'Leasing Partner',
                    'dp_percent' => $dp,
                    'tenor_months' => $tenor,
                    'annual_rate' => $rate,
                    'dp_amount' => $sim['dpAmount'],
                    'principal' => $sim['principal'],
                    'total_interest' => $sim['totalInterest'],
                    'monthly' => $sim['monthly'],
                ];
                $paid = $sim['dpAmount'];
            } elseif ($method === 'cash') {
                // Kosong berarti uang pas; angka lain harus mencukupi
                $received = $data['paid_amount'] ?? null;
                $received = ($received === null || $received === '') ? $amountDue : $received;
                if (! is_numeric($received) || $received < $amountDue) {
                    throw new SaleException('Uang diterima kurang dari sisa yang harus dibayar.');
                }
                $paid = (int) $received;
                $change = $paid - $amountDue;
            }

            $now = now();
            $name = car_name($car, true);

            $trx = Transaction::create([
                'code' => CodeGenerator::transaction(),
                'date' => $now,
                'sale_type' => 'car',
                'customer_name' => trim((string) ($data['customer_name'] ?? '')) ?: 'Pelanggan Umum',
                'customer_phone' => trim((string) ($data['customer_phone'] ?? '')) ?: null,
                'cashier' => $settings->cashier_name ?: 'Admin Showroom',
                'subtotal' => $price,
                'discount' => $discount,
                'tax' => 0,
                'total' => $total,
                'amount_due' => $amountDue,
                'payment_method' => $method,
                'paid_amount' => $paid,
                'change_amount' => $change,
                'total_profit' => $total - (int) $car->buy_price,
                'status' => 'completed',
                'car_id' => $car->id,
                'car_snapshot' => [
                    'id' => $car->id,
                    'code' => $car->code,
                    'brand' => $car->brand,
                    'model' => $car->model,
                    'variant' => $car->variant,
                    'year' => $car->year,
                    'plate' => $car->plate,
                    'mileage' => $car->mileage,
                    'transmission' => $car->transmission,
                    'fuel' => $car->fuel,
                    'color' => $car->color,
                ],
                'credit' => $credit,
                'trade_in' => $tradeInValue > 0 ? [
                    'request_id' => $tradeIn['request_id'] ?? null,
                    'car_name' => trim((string) ($tradeIn['car_name'] ?? '')),
                    'value' => $tradeInValue,
                ] : null,
                'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            ]);

            $trx->items()->create([
                'product_id' => null,
                'sku' => $car->code,
                'name' => $name,
                'unit' => 'unit',
                'price' => $price,
                'buy_price' => (int) $car->buy_price,
                'qty' => 1,
                'subtotal' => $price,
            ]);

            $car->update([
                'status' => 'sold',
                'sold_at' => $now,
                'sold_price' => $total,
                'sold_transaction_id' => $trx->id,
            ]);

            // Mobil tukar tambah masuk stok hanya bila diambil dari permintaan yang ada
            if ($tradeInValue > 0 && filled($tradeIn['request_id'] ?? null)) {
                $request = SellRequest::find($tradeIn['request_id']);
                if (! $request) {
                    throw new SaleException('Permintaan tukar tambah tidak ditemukan.');
                }
                // Hanya permintaan tukar tambah yang masih terbuka; yang ditolak, sudah dibeli,
                // atau permintaan jual biasa tidak boleh ditarik lewat form penjualan
                if ($request->type !== 'trade-in' || ! in_array($request->status, ['new', 'scheduled', 'inspected'], true)) {
                    throw new SaleException('Permintaan tukar tambah ini tidak bisa dipakai.');
                }
                $this->requests->purchase($request, $tradeInValue);
            }

            return $trx;
        });
    }

    /**
     * Membatalkan penjualan: transaksi ditandai "void" (tetap tercatat, tidak dihitung di omset/laba)
     * dan mobil kembali tersedia. Mobil tukar tambah yang sudah masuk stok TIDAK ikut ditarik.
     *
     * @throws SaleException
     */
    public function void(Car $car): ?Transaction
    {
        return DB::transaction(function () use ($car) {
            $car = Car::query()->lockForUpdate()->findOrFail($car->id);
            if ($car->status !== 'sold') {
                throw new SaleException('Mobil ini belum terjual.');
            }

            $trx = $car->sold_transaction_id ? Transaction::query()->lockForUpdate()->find($car->sold_transaction_id) : null;
            if ($trx && $trx->status !== 'void') {
                $trx->update(['status' => 'void', 'voided_at' => now()]);
            }

            $car->update([
                'status' => 'available',
                'sold_at' => null,
                'sold_price' => null,
                'sold_transaction_id' => null,
            ]);

            return $trx;
        });
    }

    private function number(mixed $value, float|int $fallback): float|int
    {
        return ($value !== null && $value !== '' && is_numeric($value)) ? $value + 0 : $fallback;
    }

    private function plain(float|int $n): string
    {
        return rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
    }
}
