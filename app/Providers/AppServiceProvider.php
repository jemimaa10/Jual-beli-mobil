<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\SellRequest;
use App\Models\Setting;
use App\Models\TestDrive;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Profil showroom: satu kali baca per permintaan. Bila tabel belum dimigrasi
        // (instalasi baru), pakai nilai bawaan dari config agar halaman tetap tampil.
        $this->app->scoped('store', function () {
            try {
                return Setting::current();
            } catch (QueryException) {
                return (object) config('auto18.store');
            }
        });
    }

    public function boot(): void
    {
        // Tersedia sebagai $store di semua view (navbar, footer, struk).
        View::composer('*', function ($view) {
            if (! array_key_exists('store', $view->getData())) {
                $view->with('store', app('store'));
            }
        });

        // Lencana angka di menu panel showroom (hanya dihitung untuk staf yang login)
        View::composer('partials.navbar', function ($view) {
            if (! auth()->check()) {
                return;
            }

            try {
                $view->with('navBadges', [
                    'showroom.cars.index' => SellRequest::where('status', 'new')->count() + TestDrive::where('status', 'pending')->count(),
                    'showroom.products.index' => Product::whereColumn('stock', '<=', 'min_stock')->count(),
                ]);
            } catch (QueryException) {
                // tabel belum dimigrasi: tanpa lencana
            }
        });
    }
}
