<?php

namespace Database\Seeders\Concerns;

use App\Models\Car;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Membaca database/seeders/data/demo.json (dipindahkan dari legacy-react/src/data/initialData.js).
 * Semua tanggal di berkas itu dibekukan ke "hari ini" = base_date, jadi di sini dipulihkan
 * menjadi relatif terhadap hari seeding supaya dashboard & laporan selalu terlihat hidup.
 */
trait ReadsDemoData
{
    private ?array $demoCache = null;

    protected function demo(string $key): array
    {
        $this->demoCache ??= json_decode(file_get_contents(database_path('seeders/data/demo.json')), true);

        return $this->demoCache[$key];
    }

    private function baseDay(): Carbon
    {
        $this->demoCache ??= json_decode(file_get_contents(database_path('seeders/data/demo.json')), true);

        return Carbon::parse($this->demoCache['base_date'], config('app.timezone'))->startOfDay();
    }

    /** Timestamp ISO (UTC) di demo.json -> hari ini + selisih hari, jam & menit yang sama. */
    protected function when(?string $iso): ?Carbon
    {
        if (blank($iso)) {
            return null;
        }

        $local = Carbon::parse($iso)->setTimezone(config('app.timezone'));
        $days = (int) round($this->baseDay()->diffInDays($local->copy()->startOfDay(), false));

        return Carbon::today()->addDays($days)->setTime($local->hour, $local->minute);
    }

    /** Tanggal 'YYYY-MM-DD' di demo.json -> hari ini + selisih hari. */
    protected function day(?string $ymd): ?Carbon
    {
        if (blank($ymd)) {
            return null;
        }

        $days = (int) round($this->baseDay()->diffInDays(Carbon::parse($ymd, config('app.timezone'))->startOfDay(), false));

        return Carbon::today()->addDays($days);
    }

    /** Kunci camelCase -> snake_case (rekursif untuk objek bersarang). */
    protected function snake(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $out[is_string($key) ? Str::snake($key) : $key] = is_array($value) && ! array_is_list($value) ? $this->snake($value) : $value;
        }

        return $out;
    }

    /** id lama ('car-7') -> id baris cars di database, lewat kode mobil yang unik. */
    protected function carId(?string $legacyId): ?int
    {
        if (blank($legacyId)) {
            return null;
        }

        $code = collect($this->demo('cars'))->firstWhere('id', $legacyId)['code'] ?? null;

        return $code ? Car::where('code', $code)->value('id') : null;
    }
}
