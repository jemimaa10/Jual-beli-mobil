<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Profil showroom untuk semua view (navbar, footer, struk).
        // Task 2 mengganti sumbernya menjadi Setting::current().
        View::composer('*', function ($view) {
            if (! array_key_exists('store', $view->getData())) {
                $view->with('store', (object) config('auto18.store'));
            }
        });
    }
}
