<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSellRequest;
use App\Models\Car;
use App\Models\SellRequest;
use App\Services\CarPricing;
use App\Services\CodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SellCarController extends Controller
{
    public function create(): View
    {
        $submitted = session('sell_request_id')
            ? SellRequest::with('tradeInCar')->find(session('sell_request_id'))
            : null;

        return view('public.sell', [
            'submitted' => $submitted,
            'tables' => CarPricing::tables(),
            'targetCars' => Car::query()->available()->orderBy('brand')->orderBy('model')->get()
                ->map(fn (Car $car) => [
                    'id' => $car->id,
                    'name' => car_name($car, true),
                    'price' => (int) $car->price,
                    'image' => $car->image_src,
                ])->values(),
            'years' => range(now()->year, now()->year - 25),
            'timeSlots' => \App\Http\Requests\StoreTestDriveRequest::TIME_SLOTS,
        ]);
    }

    public function store(StoreSellRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Estimasi selalu dihitung ulang di server; nilai dari browser diabaikan
        $estimate = CarPricing::estimate($data);

        $sellRequest = DB::transaction(fn () => SellRequest::create([
            'code' => CodeGenerator::sellRequest(),
            'type' => $data['type'],
            'customer_name' => trim($data['customer_name']),
            'phone' => trim($data['phone']),
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'brand' => $data['brand'],
            'model' => trim($data['model']),
            'variant' => trim((string) ($data['variant'] ?? '')) ?: null,
            'year' => $data['year'],
            'body_type' => $data['body_type'],
            'transmission' => $data['transmission'],
            'fuel' => $data['fuel'],
            'mileage' => $data['mileage'],
            'color' => $data['color'],
            'location' => $data['location'],
            'condition' => $data['condition'],
            'asking_price' => (int) ($data['asking_price'] ?? 0),
            'estimate_low' => $estimate['low'],
            'estimate_high' => $estimate['high'],
            'inspection_date' => $data['inspection_date'],
            'inspection_time' => $data['inspection_time'],
            'trade_in_car_id' => $data['type'] === 'trade-in' ? $data['trade_in_car_id'] : null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'status' => 'new',
        ]));

        return redirect()->route('sell.create')->with('sell_request_id', $sellRequest->id);
    }
}
