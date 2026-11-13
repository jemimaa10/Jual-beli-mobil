<?php

namespace Database\Seeders;

use App\Models\SellRequest;
use App\Models\TestDrive;
use Database\Seeders\Concerns\ReadsDemoData;
use Illuminate\Database\Seeder;

/** Permintaan jual/tukar tambah dan booking test drive. */
class RequestSeeder extends Seeder
{
    use ReadsDemoData;

    public function run(): void
    {
        foreach ($this->demo('sell_requests') as $row) {
            $data = $this->snake($row);
            unset($data['id']);

            $data['inspection_date'] = $this->day($row['inspectionDate'] ?? null);
            $data['trade_in_car_id'] = $this->carId($row['tradeInCarId'] ?? null);
            $data['purchased_car_id'] = $this->carId($row['purchasedCarId'] ?? null);
            $data['created_at'] = $this->when($row['createdAt']);

            SellRequest::create($data);
        }

        foreach ($this->demo('test_drives') as $row) {
            $data = $this->snake($row);
            unset($data['id']);

            $data['car_id'] = $this->carId($row['carId'] ?? null);
            $data['date'] = $this->day($row['date']);
            $data['created_at'] = $this->when($row['createdAt']);

            TestDrive::create($data);
        }
    }
}
