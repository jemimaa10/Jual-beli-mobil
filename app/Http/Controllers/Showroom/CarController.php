<?php

namespace App\Http\Controllers\Showroom;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveCarRequest;
use App\Models\Car;
use App\Models\SellRequest;
use App\Models\TestDrive;
use App\Services\CarPricing;
use App\Services\CodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CarController extends Controller
{
    public const REQUEST_STATUS = [
        'new' => ['Baru Masuk', 'bg-sky-500/15 text-sky-300 border-sky-500/30'],
        'scheduled' => ['Inspeksi Dijadwalkan', 'bg-amber-500/15 text-amber-300 border-amber-500/30'],
        'inspected' => ['Sudah Diinspeksi', 'bg-violet-500/15 text-violet-300 border-violet-500/30'],
        'purchased' => ['Dibeli Auto18', 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'],
        'rejected' => ['Ditolak', 'bg-neutral-500/15 text-neutral-400 border-neutral-500/30'],
    ];

    public const TEST_DRIVE_STATUS = [
        'pending' => ['Menunggu Konfirmasi', 'bg-amber-500/15 text-amber-300 border-amber-500/30'],
        'confirmed' => ['Terkonfirmasi', 'bg-sky-500/15 text-sky-300 border-sky-500/30'],
        'done' => ['Selesai', 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'],
        'cancelled' => ['Dibatalkan', 'bg-neutral-500/15 text-neutral-400 border-neutral-500/30'],
    ];

    public function index(Request $request): View
    {
        $text = fn (string $key) => is_string($v = $request->query($key)) ? trim($v) : '';
        $tab = in_array($text('tab'), ['stock', 'requests', 'testdrive'], true) ? $text('tab') : 'stock';

        $allCars = Car::query()->get(['id', 'status', 'price', 'buy_price', 'sold_at']);
        $unsold = $allCars->where('status', '!=', 'sold');
        $stats = [
            'total' => $allCars->count(),
            'available' => $allCars->where('status', 'available')->count(),
            'booked' => $allCars->where('status', 'booked')->count(),
            'soldThisMonth' => $allCars->where('status', 'sold')->filter(fn ($c) => $c->sold_at && $c->sold_at->isSameMonth(now()))->count(),
            'stockValue' => (int) $unsold->sum('buy_price'),
            'potentialProfit' => (int) $unsold->sum('price') - (int) $unsold->sum('buy_price'),
        ];

        $stock = [
            'q' => $text('q'),
            'status' => in_array($text('status'), ['available', 'booked', 'sold'], true) ? $text('status') : '',
            'brand' => in_array($text('brand'), config('auto18.brands'), true) ? $text('brand') : '',
            'sort' => in_array($text('sort'), ['newest', 'price', 'margin', 'year'], true) ? $text('sort') : 'newest',
        ];

        $cars = Car::query()
            ->when($stock['status'], fn ($q, $v) => $q->where('status', $v))
            ->when($stock['brand'], fn ($q, $v) => $q->where('brand', $v));
        foreach (preg_split('/\s+/', str_replace(['%', '_'], '', $stock['q']), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $cars->where(function ($q) use ($word) {
                foreach (['brand', 'model', 'variant', 'code', 'plate'] as $column) {
                    $q->orWhere($column, 'like', "%{$word}%");
                }
            });
        }
        match ($stock['sort']) {
            'price' => $cars->orderByDesc('price'),
            'margin' => $cars->orderByRaw('(price - buy_price) DESC'),
            'year' => $cars->orderByDesc('year'),
            default => $cars->orderByDesc('created_at')->orderByDesc('id'),
        };

        $rFilter = [
            'status' => array_key_exists($text('rstatus'), self::REQUEST_STATUS) ? $text('rstatus') : '',
            'type' => in_array($text('rtype'), ['sell', 'trade-in'], true) ? $text('rtype') : '',
        ];
        $requests = SellRequest::query()->with(['tradeInCar', 'purchasedCar'])
            ->when($rFilter['status'], fn ($q, $v) => $q->where('status', $v))
            ->when($rFilter['type'], fn ($q, $v) => $q->where('type', $v))
            ->orderByDesc('created_at')->orderByDesc('id')->get()
            ->each(function (SellRequest $r) {
                $r->setAttribute('system_estimate', $r->estimate_low > 0
                    ? ['low' => $r->estimate_low, 'high' => $r->estimate_high, 'mid' => (int) round(($r->estimate_low + $r->estimate_high) / 2)]
                    : CarPricing::estimate($r->only(['brand', 'model', 'body_type', 'year', 'mileage', 'transmission', 'condition'])));
            });

        $tdFilter = [
            'status' => array_key_exists($text('tstatus'), self::TEST_DRIVE_STATUS) ? $text('tstatus') : '',
            'today' => $request->boolean('today'),
        ];
        $today = today();
        $testDrives = TestDrive::query()->with('car:id')
            ->when($tdFilter['status'], fn ($q, $v) => $q->where('status', $v))
            ->when($tdFilter['today'], fn ($q) => $q->whereDate('date', $today))
            ->get();
        // Mendatang: terdekat dulu. Lewat: terbaru dulu.
        $upcoming = $testDrives->filter(fn ($t) => $t->date->gte($today))->sortBy(fn ($t) => $t->date->format('Y-m-d') . ' ' . $t->time);
        $past = $testDrives->filter(fn ($t) => $t->date->lt($today))->sortByDesc(fn ($t) => $t->date->format('Y-m-d') . ' ' . $t->time);
        $testDrives = $upcoming->concat($past)->values();

        return view('showroom.cars.index', [
            'tab' => $tab,
            'stats' => $stats,
            'cars' => $cars->get(),
            'stock' => $stock,
            'requests' => $requests,
            'rFilter' => $rFilter,
            'testDrives' => $testDrives,
            'tdFilter' => $tdFilter,
            'newRequestCount' => SellRequest::where('status', 'new')->count(),
            'pendingTestDrives' => TestDrive::where('status', 'pending')->count(),
            'requestStatus' => self::REQUEST_STATUS,
            'testDriveStatus' => self::TEST_DRIVE_STATUS,
            'today' => $today,
        ]);
    }

    public function create(): View
    {
        return view('showroom.cars.form', ['car' => new Car($this->defaults()), 'isEdit' => false, ...$this->formOptions()]);
    }

    public function store(SaveCarRequest $request): RedirectResponse
    {
        $car = Car::create($this->attributes($request) + ['code' => CodeGenerator::car()]);

        return redirect()->route('showroom.cars.index', ['q' => $car->code])->with('success', "{$car->code} ditambahkan ke stok.");
    }

    public function edit(Car $car): View
    {
        return view('showroom.cars.form', ['car' => $car, 'isEdit' => true, ...$this->formOptions()]);
    }

    public function update(SaveCarRequest $request, Car $car): RedirectResponse
    {
        $attributes = $this->attributes($request);
        if ($car->status === 'sold') {
            unset($attributes['status']); // mobil terjual hanya bisa dibatalkan lewat "Batalkan penjualan"
        }
        $car->update($attributes);

        return redirect()->route('showroom.cars.index', ['q' => $car->code])->with('success', "{$car->code} diperbarui.");
    }

    public function destroy(Car $car): RedirectResponse
    {
        $name = car_name($car, true);
        $car->delete();

        return redirect()->route('showroom.cars.index')->with('success', "{$name} dihapus dari stok.");
    }

    public function status(Request $request, Car $car): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', Rule::in(['available', 'booked'])]])['status'];

        if ($car->status === 'sold') {
            return back()->with('error', 'Mobil yang sudah terjual tidak bisa diubah statusnya. Gunakan "Batalkan penjualan".');
        }

        $car->update(['status' => $status]);

        return back()->with('success', car_name($car) . ($status === 'booked' ? ' ditandai Dipesan.' : ' kembali Tersedia.'));
    }

    public function feature(Car $car): RedirectResponse
    {
        $car->update(['is_featured' => ! $car->is_featured]);

        return back()->with('success', car_name($car) . ($car->is_featured ? ' dijadikan Pilihan Auto18.' : ' dilepas dari Pilihan Auto18.'));
    }

    private function defaults(): array
    {
        return [
            'brand' => 'Toyota', 'year' => now()->year - 3, 'body_type' => 'MPV', 'transmission' => 'Otomatis', 'fuel' => 'Bensin',
            'engine_cc' => 1500, 'seats' => 7, 'mileage' => 0, 'color' => 'Putih', 'location' => 'Jakarta Barat', 'price' => 0, 'buy_price' => 0,
            'status' => 'available', 'owner_count' => 1, 'service_record' => true, 'is_featured' => false, 'features' => [],
            'inspection' => collect(config('auto18.inspection_categories'))->mapWithKeys(fn ($c) => [$c['key'] => 92])->all(),
        ];
    }

    private function formOptions(): array
    {
        return ['photoKeys' => array_column(config('auto18.photo_credits'), 'key')];
    }

    private function attributes(SaveCarRequest $request): array
    {
        $v = $request->validated();

        $inspection = [];
        foreach (config('auto18.inspection_categories') as $category) {
            $inspection[$category['key']] = (int) min(100, max(0, round((float) ($v['inspection'][$category['key']] ?? 90))));
        }

        $features = collect(explode(',', (string) ($v['features'] ?? '')))->map(fn ($f) => trim($f))->filter()->values()->all();
        $colorHex = $v['color_hex'] ?? (collect(config('auto18.colors'))->firstWhere('name', $v['color'])['hex'] ?? null);

        return [
            'brand' => $v['brand'],
            'model' => trim($v['model']),
            'variant' => trim((string) ($v['variant'] ?? '')) ?: null,
            'year' => (int) $v['year'],
            'body_type' => $v['body_type'],
            'transmission' => $v['transmission'],
            'fuel' => $v['fuel'],
            'engine_cc' => (int) ($v['engine_cc'] ?? 0),
            'seats' => (int) ($v['seats'] ?? 5),
            'mileage' => (int) $v['mileage'],
            'color' => $v['color'],
            'color_hex' => $colorHex,
            'plate' => trim((string) ($v['plate'] ?? '')) ?: null,
            'location' => trim((string) ($v['location'] ?? '')) ?: null,
            'price' => (int) $v['price'],
            'buy_price' => (int) $v['buy_price'],
            'status' => $v['status'],
            'owner_count' => (int) ($v['owner_count'] ?? 1),
            'tax_valid_until' => $v['tax_valid_until'] ?? null,
            'service_record' => $request->boolean('service_record'),
            'is_featured' => $request->boolean('is_featured'),
            'image_key' => $v['image_key'] ?? null,
            'image_url' => trim((string) ($v['image_url'] ?? '')) ?: null,
            'features' => $features,
            'description' => trim((string) ($v['description'] ?? '')) ?: null,
            'inspection' => $inspection,
            'inspection_score' => (int) round(array_sum($inspection) / count($inspection)),
        ];
    }
}
