<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'service_record' => 'boolean',
            'features' => 'array',
            'inspection' => 'array',
            'sold_at' => 'datetime',
        ];
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function scopeNotSold(Builder $query): Builder
    {
        return $query->where('status', '!=', 'sold');
    }

    public function testDrives(): HasMany
    {
        return $this->hasMany(TestDrive::class);
    }

    public function soldTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'sold_transaction_id');
    }

    /** Foto yang dipakai kartu & detail: URL kustom, atau berkas bawaan di public/images/cars. */
    public function getImageSrcAttribute(): ?string
    {
        if (filled($this->image_url)) {
            return $this->image_url;
        }

        return filled($this->image_key) ? asset('images/cars/' . $this->image_key . '.jpg') : null;
    }

    public function getNameAttribute(): string
    {
        return car_name($this, true);
    }
}
