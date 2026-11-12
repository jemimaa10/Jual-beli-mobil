<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            SettingSeeder::class,
            CatalogSeeder::class,
            CarSeeder::class,
            RequestSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
