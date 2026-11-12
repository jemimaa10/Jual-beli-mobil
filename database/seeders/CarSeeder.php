<?php

namespace Database\Seeders;

use App\Models\Car;
use Database\Seeders\Concerns\ReadsDemoData;
use Illuminate\Database\Seeder;

/** 12 mobil bekas demo. sold_transaction_id diisi oleh TransactionSeeder. */
class CarSeeder extends Seeder
{
    use ReadsDemoData;

    public function run(): void
    {
        foreach ($this->demo('cars') as $row) {
            $data = $this->snake($row);
            unset($data['id'], $data['sold_transaction_id']);

            // Kunci inspeksi (mis. kakiKaki) harus tetap sama dengan config('auto18.inspection_categories')
            $data['inspection'] = $row['inspection'];
            $data['created_at'] = $this->when($row['createdAt'] ?? null);
            $data['sold_at'] = $this->when($row['soldAt'] ?? null);

            Car::create($data);
        }
    }
}
