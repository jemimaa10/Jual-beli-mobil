<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@auto18.test'],
            ['name' => 'Admin Showroom', 'password' => 'auto18admin'],
        );
    }
}
