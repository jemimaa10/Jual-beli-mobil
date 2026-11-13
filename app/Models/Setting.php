<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tax_enabled' => 'boolean',
            'tax_rate' => 'float',
            'credit_rate' => 'float',
            'min_dp_percent' => 'float',
        ];
    }

    /** Profil showroom (baris tunggal). Dibuat dari config/auto18.php bila belum ada. */
    public static function current(): self
    {
        return static::query()->find(1) ?? static::create(['id' => 1] + config('auto18.store'));
    }
}
