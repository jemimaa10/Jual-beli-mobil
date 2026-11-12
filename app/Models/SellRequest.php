<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['inspection_date' => 'date'];
    }

    public function tradeInCar(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'trade_in_car_id');
    }

    public function purchasedCar(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'purchased_car_id');
    }
}
