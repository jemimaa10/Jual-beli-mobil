<?php

namespace App\Http\Controllers\Showroom;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Product;
use App\Models\SellRequest;
use App\Models\TestDrive;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = today();

        // Transaksi yang dibatalkan (void) tidak pernah ikut dihitung
        $todayTrx = Transaction::query()->completed()->whereDate('date', $today)->get(['total', 'total_profit']);

        $cars = Car::query()->get(['status', 'sold_at']);

        // Omset 7 hari terakhir, dipisah mobil & aksesoris
        $start = $today->copy()->subDays(6);
        $byDay = Transaction::query()->completed()->where('date', '>=', $start)->get(['date', 'sale_type', 'total'])
            ->groupBy(fn (Transaction $t) => $t->date->toDateString());
        $series = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i);
            $rows = $byDay->get($day->toDateString(), collect());
            $series[] = [
                'date' => $day->toDateString(),
                'label' => $day->locale('id')->translatedFormat('D'),
                'full' => $day->locale('id')->translatedFormat('l, j M'),
                'car' => (int) $rows->where('sale_type', 'car')->sum('total'),
                'retail' => (int) $rows->where('sale_type', 'retail')->sum('total'),
            ];
        }

        return view('showroom.dashboard', [
            'today' => [
                'revenue' => (int) $todayTrx->sum('total'),
                'profit' => (int) $todayTrx->sum('total_profit'),
                'count' => $todayTrx->count(),
            ],
            'showroom' => [
                'available' => $cars->where('status', 'available')->count(),
                'booked' => $cars->where('status', 'booked')->count(),
                'soldThisMonth' => $cars->where('status', 'sold')->filter(fn ($c) => $c->sold_at && $c->sold_at->isSameMonth($today))->count(),
                'newRequests' => SellRequest::where('status', 'new')->count(),
                'testDrivesToday' => TestDrive::whereIn('status', ['pending', 'confirmed'])->whereDate('date', $today)->count(),
                'testDrivesUpcoming' => TestDrive::whereIn('status', ['pending', 'confirmed'])->whereDate('date', '>=', $today)->count(),
            ],
            'series' => $series,
            'recent' => Transaction::query()->with('items')->orderByDesc('date')->orderByDesc('id')->limit(6)->get(),
            'upcomingDrives' => TestDrive::query()->whereIn('status', ['pending', 'confirmed'])->whereDate('date', '>=', $today)->orderBy('date')->orderBy('time')->limit(5)->get(),
            'newRequestList' => SellRequest::query()->whereIn('status', ['new', 'scheduled', 'inspected'])->orderByDesc('created_at')->limit(5)->get(),
            'lowStock' => Product::query()->whereColumn('stock', '<=', 'min_stock')->orderBy('stock')->limit(8)->get(),
            'topProducts' => TransactionItem::query()
                ->whereNotNull('product_id')
                ->whereHas('transaction', fn ($q) => $q->where('status', 'completed')->where('sale_type', 'retail'))
                ->selectRaw('name, SUM(qty) as sold, SUM(subtotal) as revenue')
                ->groupBy('name')->orderByDesc('sold')->limit(5)->get(),
        ]);
    }
}
