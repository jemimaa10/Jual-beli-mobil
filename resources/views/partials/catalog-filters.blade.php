{{-- Panel filter katalog (GET). Dipakai dua kali (sidebar & drawer ponsel), jadi id diberi $suffix. --}}
<form method="GET" action="{{ route('cars.index') }}#daftar-mobil" class="space-y-5" onchange="this.requestSubmit()">
    <input type="hidden" name="sort" value="{{ $filters['sort'] }}">

    <div>
        <label for="f-q-{{ $suffix }}" class="a18-label">Cari</label>
        <input id="f-q-{{ $suffix }}" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Merek, model, atau kode" class="a18-input">
    </div>

    <fieldset>
        <legend class="a18-label">Merek</legend>
        <div class="max-h-44 space-y-1.5 overflow-y-auto pr-1">
            @foreach (config('auto18.brands') as $brand)
                @if (($brandCounts[$brand] ?? 0) || in_array($brand, $filters['brand'], true))
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-neutral-300 hover:text-white">
                        <input type="checkbox" name="brand[]" value="{{ $brand }}" @checked(in_array($brand, $filters['brand'], true)) class="h-4 w-4 accent-brand-600">
                        <span class="flex-1">{{ $brand }}</span>
                        <span class="text-xs text-neutral-500">{{ $brandCounts[$brand] ?? 0 }}</span>
                    </label>
                @endif
            @endforeach
        </div>
    </fieldset>

    <fieldset>
        <legend class="a18-label">Harga</legend>
        <div class="space-y-1.5">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-neutral-300 hover:text-white">
                <input type="radio" name="price" value="" @checked($filters['price'] === '') class="h-4 w-4 accent-brand-600"> Semua harga
            </label>
            @foreach (config('auto18.price_ranges') as $range)
                <label class="flex cursor-pointer items-center gap-2 text-sm text-neutral-300 hover:text-white">
                    <input type="radio" name="price" value="{{ $range['id'] }}" @checked($filters['price'] === $range['id']) class="h-4 w-4 accent-brand-600"> {{ $range['label'] }}
                </label>
            @endforeach
        </div>
    </fieldset>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label for="f-body-{{ $suffix }}" class="a18-label">Tipe Bodi</label>
            <select id="f-body-{{ $suffix }}" name="body" class="a18-input">
                <option value="">Semua</option>
                @foreach (config('auto18.body_types') as $v)<option value="{{ $v }}" @selected($filters['body'] === $v)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="f-trans-{{ $suffix }}" class="a18-label">Transmisi</label>
            <select id="f-trans-{{ $suffix }}" name="transmission" class="a18-input">
                <option value="">Semua</option>
                @foreach (config('auto18.transmissions') as $v)<option value="{{ $v }}" @selected($filters['transmission'] === $v)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="f-fuel-{{ $suffix }}" class="a18-label">Bahan Bakar</label>
            <select id="f-fuel-{{ $suffix }}" name="fuel" class="a18-input">
                <option value="">Semua</option>
                @foreach (config('auto18.fuels') as $v)<option value="{{ $v }}" @selected($filters['fuel'] === $v)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="f-km-{{ $suffix }}" class="a18-label">Kilometer</label>
            <select id="f-km-{{ $suffix }}" name="km_max" class="a18-input">
                <option value="">Semua</option>
                @foreach ($mileageOptions as $o)<option value="{{ $o['id'] }}" @selected((string) $filters['km_max'] === $o['id'])>{{ $o['label'] }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="f-yf-{{ $suffix }}" class="a18-label">Tahun Dari</label>
            <input id="f-yf-{{ $suffix }}" type="number" inputmode="numeric" name="year_min" value="{{ $filters['year_min'] }}" placeholder="2015" class="a18-input">
        </div>
        <div>
            <label for="f-yt-{{ $suffix }}" class="a18-label">Tahun Sampai</label>
            <input id="f-yt-{{ $suffix }}" type="number" inputmode="numeric" name="year_max" value="{{ $filters['year_max'] }}" placeholder="2023" class="a18-input">
        </div>
    </div>

    <div>
        <label for="f-loc-{{ $suffix }}" class="a18-label">Lokasi</label>
        <select id="f-loc-{{ $suffix }}" name="location" class="a18-input">
            <option value="">Semua lokasi</option>
            @foreach (config('auto18.locations') as $v)<option value="{{ $v }}" @selected($filters['location'] === $v)>{{ $v }}</option>@endforeach
        </select>
    </div>

    <div class="space-y-2 border-t border-white/10 pt-4">
        <label class="flex cursor-pointer items-center gap-2 text-sm text-neutral-300 hover:text-white">
            <input type="checkbox" name="favorit" value="1" @checked($filters['favorit']) class="h-4 w-4 accent-brand-600">
            Hanya favorit saya (<span x-data x-text="$store.favorites.count">0</span>)
        </label>
        <label class="flex cursor-pointer items-center gap-2 text-sm text-neutral-300 hover:text-white">
            <input type="checkbox" name="sold" value="1" @checked($filters['sold']) class="h-4 w-4 accent-brand-600"> Tampilkan mobil terjual
        </label>
    </div>

    <div class="grid grid-cols-2 gap-2">
        <button type="submit" class="a18-btn-primary">Terapkan</button>
        <a href="{{ route('cars.index') }}#daftar-mobil" class="a18-btn-outline"><x-lucide-rotate-ccw class="h-4 w-4" /> Reset</a>
    </div>
</form>
