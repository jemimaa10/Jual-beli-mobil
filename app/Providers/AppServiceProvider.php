<?php

namespace App\Providers;

use App\Models\Setting;
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
    }
}
