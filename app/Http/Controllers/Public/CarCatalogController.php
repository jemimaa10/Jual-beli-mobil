<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Services\CarPricing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarCatalogController extends Controller
{
    public const MILEAGE_OPTIONS = [
        ['id' => '30000', 'label' => '< 30.000 km'],
        ['id' => '50000', 'label' => '< 50.000 km'],
        ['id' => '80000', 'label' => '< 80.000 km'],
        ['id' => '100000', 'label' => '< 100.000 km'],
    ];

    public const SORT_OPTIONS = [
        'recommended' => 'Rekomendasi',
        'newest' => 'Terbaru',
        'price_asc' => 'Harga Terendah',
        'price_desc' => 'Harga Tertinggi',
        'km_asc' => 'Kilometer Terendah',
        'year_desc' => 'Tahun Terbaru',
    ];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $cars = $this->query($filters)->get();

        $unsold = Car::query()->notSold()->get(['id', 'brand', 'body_type', 'price', 'is_featured', 'status', 'model', 'image_key', 'image_url', 'location', 'year', 'variant']);

        $prices = $unsold->pluck('price')->sort()->values();

        return view('public.catalog', [
            'cars' => $cars,
            'filters' => $filters,
            'sortOptions' => self::SORT_OPTIONS,
            'mileageOptions' => self::MILEAGE_OPTIONS,
            'brandCounts' => $unsold->countBy('brand')->all(),
            'bodyCounts' => $unsold->countBy('body_type')->all(),
            'unsoldCount' => $unsold->count(),
            'featuredCar' => $unsold->firstWhere('is_featured', true) ?? $unsold->first(),
            'medianPrice' => $prices->isEmpty() ? 200_000_000 : $prices[intdiv($prices->count(), 2)],
            'simulationCars' => $unsold->take(4),
            'activeChips' => $this->chips($filters),
        ]);
    }

    public function show(Car $car): View
    {
        return view('public.car-detail', [
            'car' => $car,
            'photoCredit' => $this->photoCredit($car),
        ]);
    }

    /** Parameter query dibersihkan di sini; nilai aneh jatuh ke "tanpa filter", bukan error. */
    private function filters(Request $request): array
    {
        $text = fn (string $key) => is_string($v = $request->query($key)) ? trim($v) : '';
        $number = fn (string $key) => is_numeric($v = $request->query($key)) && $v >= 0 ? (int) min((float) $v, 9_000_000_000_000_000) : null;

        $brands = collect((array) $request->query('brand', []))
            ->filter(fn ($b) => is_string($b) && $b !== '')
            ->values()
            ->all();

        $priceMin = $number('price_min');
        $priceMax = $number('price_max');
        $priceRange = collect(config('auto18.price_ranges'))->firstWhere('id', $text('price'));
        if ($priceRange && $priceMin === null && $priceMax === null) {
            $priceMin = $priceRange['min'];
            $priceMax = $priceRange['max'];
        }

        $sort = $text('sort');

        return [
            'q' => $text('q'),
            'brand' => $brands,
            'body' => $text('body'),
            'transmission' => $text('transmission'),
            'fuel' => $text('fuel'),
            'price' => $priceRange['id'] ?? '',
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'year_min' => $number('year_min'),
            'year_max' => $number('year_max'),
            'km_max' => $number('km_max'),
            'location' => $text('location'),
            'sold' => $request->boolean('sold'),
            'favorit' => $request->boolean('favorit'),
            'sort' => array_key_exists($sort, self::SORT_OPTIONS) ? $sort : 'recommended',
        ];
    }

    private function query(array $f): Builder
    {
        $query = Car::query();

        if (! $f['sold']) {
            $query->notSold();
        }
        // Setiap kata harus cocok dengan salah satu kolom (merek, model, varian, tahun, kode, tipe)
        foreach (preg_split('/\s+/', str_replace(['%', '_'], '', $f['q']), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $query->where(function (Builder $q) use ($word) {
                foreach (['brand', 'model', 'variant', 'year', 'code', 'body_type'] as $column) {
                    $q->orWhere($column, 'like', "%{$word}%");
                }
            });
        }
        if ($f['brand']) {
            $query->whereIn('brand', $f['brand']);
        }
        foreach (['body' => 'body_type', 'transmission' => 'transmission', 'fuel' => 'fuel', 'location' => 'location'] as $param => $column) {
            if ($f[$param] !== '') {
                $query->where($column, $f[$param]);
            }
        }
        if ($f['price_min'] !== null) {
            $query->where('price', '>=', $f['price_min']);
        }
        if ($f['price_max'] !== null) {
            $query->where('price', '<=', $f['price_max']);
        }
        if ($f['year_min'] !== null) {
            $query->where('year', '>=', $f['year_min']);
        }
        if ($f['year_max'] !== null) {
            $query->where('year', '<=', $f['year_max']);
        }
        if ($f['km_max'] !== null) {
            $query->where('mileage', '<=', $f['km_max']);
        }

        return match ($f['sort']) {
            'newest' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'price_asc' => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderBy('id'),
            'km_asc' => $query->orderBy('mileage')->orderBy('id'),
            'year_desc' => $query->orderByDesc('year')->orderBy('id'),
            default => $query
                ->orderByRaw("CASE status WHEN 'available' THEN 0 WHEN 'booked' THEN 1 ELSE 2 END")
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->orderBy('id'),
        };
    }

    /** Chip filter aktif: [label, query tanpa filter itu]. */
    private function chips(array $f): array
    {
        $chips = [];
        $without = function (array $keys, ?string $brand = null) use ($f) {
            $q = $f;
            foreach ($keys as $key) {
                $q[$key] = is_array($f[$key]) ? [] : (is_bool($f[$key]) ? false : (is_int($f[$key]) || $f[$key] === null ? null : ''));
            }
            if ($brand !== null) {
                $q['brand'] = array_values(array_diff($f['brand'], [$brand]));
            }

            return self::queryFor($q);
        };

        if ($f['q'] !== '') {
            $chips[] = ['label' => "\"{$f['q']}\"", 'url' => $without(['q'])];
        }
        foreach ($f['brand'] as $brand) {
            $chips[] = ['label' => $brand, 'url' => $without([], $brand)];
        }
        if ($f['price'] !== '') {
            $label = collect(config('auto18.price_ranges'))->firstWhere('id', $f['price'])['label'] ?? '';
            $chips[] = ['label' => $label, 'url' => $without(['price', 'price_min', 'price_max'])];
        } elseif ($f['price_min'] !== null || $f['price_max'] !== null) {
            $chips[] = ['label' => 'Harga ' . rupiah_short($f['price_min'] ?? 0) . ' - ' . ($f['price_max'] !== null ? rupiah_short($f['price_max']) : '...'), 'url' => $without(['price_min', 'price_max'])];
        }
        foreach (['body' => 'Tipe', 'transmission' => 'Transmisi', 'fuel' => 'Bahan bakar', 'location' => 'Lokasi'] as $key => $title) {
            if ($f[$key] !== '') {
                $chips[] = ['label' => $f[$key], 'url' => $without([$key])];
            }
        }
        if ($f['km_max'] !== null) {
            $chips[] = ['label' => '< ' . number_format($f['km_max'], 0, ',', '.') . ' km', 'url' => $without(['km_max'])];
        }
        if ($f['year_min'] !== null) {
            $chips[] = ['label' => "Dari {$f['year_min']}", 'url' => $without(['year_min'])];
        }
        if ($f['year_max'] !== null) {
            $chips[] = ['label' => "Sampai {$f['year_max']}", 'url' => $without(['year_max'])];
        }
        if ($f['favorit']) {
            $chips[] = ['label' => 'Favorit saja', 'url' => $without(['favorit'])];
        }
        if ($f['sold']) {
            $chips[] = ['label' => 'Termasuk terjual', 'url' => $without(['sold'])];
        }

        return $chips;
    }

    /** URL katalog dari kumpulan filter (hanya yang terisi). */
    public static function queryFor(array $f): string
    {
        $params = array_filter($f, fn ($v, $k) => ! ($k === 'sort' && $v === 'recommended') && $v !== null && $v !== '' && $v !== [] && $v !== false, ARRAY_FILTER_USE_BOTH);
        foreach (['sold', 'favorit'] as $flag) {
            if (isset($params[$flag])) {
                $params[$flag] = 1;
            }
        }

        return route('cars.index', $params) . '#daftar-mobil';
    }

    /** Foto bawaan dari Wikimedia Commons wajib mencantumkan sumbernya. */
    private function photoCredit(Car $car): ?array
    {
        if (filled($car->image_url) || blank($car->image_key)) {
            return null;
        }

        return collect(config('auto18.photo_credits'))->firstWhere('key', $car->image_key);
    }
}
