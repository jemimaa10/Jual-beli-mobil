<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTestDriveRequest;
use App\Models\Car;
use App\Models\TestDrive;
use App\Services\CodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TestDriveBookingController extends Controller
{
    public function store(StoreTestDriveRequest $request, Car $car): RedirectResponse
    {
        if ($car->status === 'sold') {
            return back()->with('error', 'Mobil ini sudah terjual, test drive tidak bisa dibooking.');
        }

        $data = $request->validated();

        $drive = DB::transaction(fn () => TestDrive::create([
            'code' => CodeGenerator::testDrive(),
            'car_id' => $car->id,
            'car_name' => car_name($car, true),
            'customer_name' => trim($data['customer_name']),
            'phone' => trim($data['phone']),
            'date' => $data['date'],
            'time' => $data['time'],
            'location' => $data['location'],
            'address' => $data['location'] === 'rumah' ? trim((string) $data['address']) : null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'status' => 'pending',
        ]));

        return back()
            ->with('success', "Booking test drive terkirim. Kode booking {$drive->code}.")
            ->with('booking_code', $drive->code);
    }
}
