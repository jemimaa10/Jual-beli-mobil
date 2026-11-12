<?php

namespace App\Http\Controllers\Showroom;

use App\Exceptions\SaleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SellCarRequest;
use App\Models\Car;
use App\Models\SellRequest;
use App\Services\CarSaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CarSaleController extends Controller
{
    public function create(Car $car): View|RedirectResponse
    {
        if ($car->status === 'sold') {
            return redirect()->route('showroom.cars.index')->with('error', 'Mobil ini sudah terjual.');
        }

        return view('showroom.cars.sale', [
            'car' => $car,
            'tradeInRequests' => SellRequest::query()
                ->where('type', 'trade-in')
                ->whereIn('status', ['new', 'scheduled', 'inspected'])
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (SellRequest $r) => [
                    'id' => $r->id,
                    'label' => "{$r->customer_name} · {$r->brand} {$r->model} {$r->year}",
                    'name' => trim(preg_replace('/\s+/', ' ', "{$r->brand} {$r->model} {$r->variant} {$r->year}")),
                    // nilai awal: penawaran tersimpan, lalu estimasi sistem
                    'value' => (int) ($r->offer_price ?: $r->estimate_low ?: \App\Services\CarPricing::estimate($r->only(['brand', 'model', 'body_type', 'year', 'mileage', 'transmission', 'condition']))['mid']),
                ])->values(),
            'leasing' => config('auto18.leasing_partners'),
        ]);
    }

    public function store(SellCarRequest $request, Car $car, CarSaleService $sales): RedirectResponse
    {
        try {
            $trx = $sales->sell($car, $request->saleData());
        } catch (SaleException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('showroom.receipt', $trx)->with('success', 'Penjualan mobil tercatat.');
    }

    public function void(Car $car, CarSaleService $sales): RedirectResponse
    {
        try {
            $sales->void($car);
        } catch (SaleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('showroom.cars.index')->with('success', 'Penjualan dibatalkan. Mobil kembali berstatus Tersedia.');
    }
}
