<?php

namespace App\Services;

use App\Exceptions\SaleException;
use App\Models\Car;
use App\Models\SellRequest;
use Illuminate\Support\Facades\DB;

class SellRequestService
{
    /** Skor inspeksi awal untuk mobil hasil pembelian dari pelanggan (sama dengan React: 90). */
    private const DEFAULT_INSPECTION_SCORE = 90;

    /**
     * Showroom membeli mobil pelanggan: mobil masuk stok (tersedia) dan permintaan menjadi "purchased".
     * Harga beli: penawaran yang diberikan, lalu penawaran tersimpan, lalu harga yang diharapkan pelanggan.
     * Harga jual bawaan: harga beli x 1,12 dibulatkan ke Rp 500.000.
     */
    public function purchase(SellRequest $request, int|string|null $offerPrice = null, int|string|null $sellPrice = null): Car
    {
        return DB::transaction(function () use ($request, $offerPrice, $sellPrice) {
            $request = SellRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->status === 'purchased') {
                throw new SaleException('Permintaan ini sudah dibeli.');
            }

            if ($request->status === 'rejected') {
                throw new SaleException('Permintaan yang ditolak tidak bisa dibeli.');
            }

            $buyPrice = $this->positive($offerPrice) ?: ((int) $request->offer_price ?: (int) $request->asking_price);
            if ($buyPrice <= 0) {
                throw new SaleException('Isi harga beli terlebih dahulu.');
            }
            $price = $this->positive($sellPrice) ?: (int) (round(($buyPrice * 1.12) / 500_000) * 500_000);
            $origin = $request->type === 'trade-in' ? 'tukar tambah' : 'pembelian';

            $car = Car::create([
                'code' => CodeGenerator::car(),
                'brand' => $request->brand,
                'model' => $request->model,
                'variant' => $request->variant,
                'year' => $request->year,
                'body_type' => $request->body_type ?: 'MPV',
                'transmission' => $request->transmission ?: 'Manual',
                'fuel' => $request->fuel ?: 'Bensin',
                'mileage' => $request->mileage,
                'color' => $request->color,
                'color_hex' => collect(config('auto18.colors'))->firstWhere('name', $request->color)['hex'] ?? null,
                'location' => $request->location,
                'price' => $price,
                'buy_price' => $buyPrice,
                'status' => 'available',
                'owner_count' => 1,
                'service_record' => false,
                'features' => [],
                'description' => "Mobil hasil {$origin} dari pelanggan ({$request->code}). Kondisi awal: " . ($request->condition ?: '-') . '.',
                'inspection' => collect(config('auto18.inspection_categories'))->mapWithKeys(fn ($c) => [$c['key'] => self::DEFAULT_INSPECTION_SCORE])->all(),
                'inspection_score' => self::DEFAULT_INSPECTION_SCORE,
            ]);

            $request->update([
                'status' => 'purchased',
                'offer_price' => $buyPrice,
                'purchased_car_id' => $car->id,
            ]);

            return $car;
        });
    }

    private function positive(int|string|null $value): int
    {
        return is_numeric($value) && $value > 0 ? (int) $value : 0;
    }
}
