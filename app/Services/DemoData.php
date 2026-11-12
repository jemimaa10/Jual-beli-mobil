<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Category;
use App\Models\Product;
use App\Models\SellRequest;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\TestDrive;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Database\Seeders\CarSeeder;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RequestSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TransactionSeeder;
use Illuminate\Support\Facades\DB;

class DemoData
{
    /** Mengembalikan semua data showroom ke data demo awal. Akun staf (users) tidak disentuh. */
    public static function reset(): void
    {
        DB::transaction(function () {
            // Urutan: anak dulu, baru induk
            foreach ([TransactionItem::class, Transaction::class, TestDrive::class, SellRequest::class, Car::class, Product::class, Supplier::class, Category::class, Setting::class] as $model) {
                $model::query()->delete();
            }

            foreach ([SettingSeeder::class, CatalogSeeder::class, CarSeeder::class, RequestSeeder::class, TransactionSeeder::class] as $seeder) {
                (new $seeder)->run();
            }
        });

        app()->forgetInstance('store');
    }
}
