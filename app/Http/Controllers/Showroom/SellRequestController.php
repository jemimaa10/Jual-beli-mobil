<?php

namespace App\Http\Controllers\Showroom;

use App\Exceptions\SaleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTestDriveRequest;
use App\Models\SellRequest;
use App\Services\SellRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SellRequestController extends Controller
{
    /** Alur status: new -> scheduled -> inspected -> (dibeli lewat purchase); "reject" dari tiga status awal. */
    public function update(Request $request, SellRequest $sellRequest): RedirectResponse
    {
        $action = $request->validate(['action' => ['required', Rule::in(['schedule', 'inspect', 'reject'])]])['action'];

        if ($sellRequest->status === 'purchased') {
            return back()->with('error', 'Permintaan yang sudah dibeli tidak bisa diubah.');
        }

        if ($action === 'schedule') {
            $data = $request->validate([
                'inspection_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
                'inspection_time' => ['required', 'date_format:H:i'],
            ]);
            if ($sellRequest->status !== 'new') {
                return back()->with('error', 'Hanya permintaan baru yang bisa dijadwalkan.');
            }
            $sellRequest->update($data + ['status' => 'scheduled']);
            $message = "Inspeksi {$sellRequest->code} dijadwalkan.";
        } elseif ($action === 'inspect') {
            $data = $request->validate(['offer_price' => ['required', 'integer', 'min:0', 'max:100000000000']]);
            if ($sellRequest->status !== 'scheduled') {
                return back()->with('error', 'Jadwalkan inspeksi terlebih dahulu.');
            }
            $sellRequest->update(['status' => 'inspected', 'offer_price' => (int) $data['offer_price']]);
            $message = "Hasil inspeksi {$sellRequest->code} tersimpan.";
        } else {
            if (! in_array($sellRequest->status, ['new', 'scheduled', 'inspected'], true)) {
                return back()->with('error', 'Permintaan ini tidak bisa ditolak.');
            }
            $sellRequest->update(['status' => 'rejected']);
            $message = "Permintaan {$sellRequest->code} ditolak.";
        }

        return back()->with('success', $message);
    }

    public function purchase(Request $request, SellRequest $sellRequest, SellRequestService $service): RedirectResponse
    {
        $data = $request->validate([
            'offer_price' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'sell_price' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
        ]);

        try {
            $car = $service->purchase($sellRequest, $data['offer_price'] ?? null, $data['sell_price'] ?? null);
        } catch (SaleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('showroom.cars.index', ['q' => $car->code])
            ->with('success', "{$car->code} masuk stok dari permintaan {$sellRequest->code}.");
    }

    public function destroy(SellRequest $sellRequest): RedirectResponse
    {
        $code = $sellRequest->code;
        $sellRequest->delete();

        return back()->with('success', "Permintaan {$code} dihapus.");
    }
}
