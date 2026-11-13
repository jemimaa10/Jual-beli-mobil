@extends('layouts.app')

@section('title', $isEdit ? 'Edit ' . $car->brand . ' ' . $car->model : 'Tambah Mobil')

@php
    $v = fn (string $field, $default = '') => old($field, $car->{$field} ?? $default);
    $inspection = old('inspection', $car->inspection ?? []);
    $features = old('features', is_array($car->features) ? implode(', ', $car->features) : ($car->features ?? ''));
    $err = fn (string $f) => $errors->first($f);
    $locked = $isEdit && $car->status === 'sold';
@endphp

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="a18-heading text-2xl">{{ $isEdit ? 'Edit ' . $car->brand . ' ' . $car->model : 'Tambah Mobil ke Stok' }}</h1>
        <a href="{{ route('showroom.cars.index') }}" class="a18-btn-ghost"><x-lucide-arrow-left class="h-4 w-4" /> Kembali</a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('showroom.cars.update', $car) : route('showroom.cars.store') }}" class="a18-card space-y-6 p-5"
          x-data="{ price: {{ (int) $v('price', 0) }}, buy: {{ (int) $v('buy_price', 0) }}, rupiah: window.auto18.rupiah,
                    get margin() { return (Number(this.price) || 0) - (Number(this.buy) || 0) },
                    get pct() { return Number(this.buy) > 0 ? (this.margin / Number(this.buy) * 100) : 0 } }">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        @if ($errors->any())
            <p class="rounded-lg border border-brand-600/40 bg-brand-600/10 px-3 py-2 text-sm text-brand-300" role="alert">Ada isian yang perlu diperbaiki. Periksa pesan di bawah kolom yang bertanda merah.</p>
        @endif

        {{-- Info dasar --}}
        <section>
            <h2 class="a18-label">Info Dasar</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div><label for="cf-brand" class="a18-label">Merek</label>
                    <select id="cf-brand" name="brand" class="a18-input">@foreach (config('auto18.brands') as $o)<option value="{{ $o }}" @selected($v('brand') === $o)>{{ $o }}</option>@endforeach</select>
                    @if ($err('brand'))<p class="mt-1 text-xs text-brand-400">{{ $err('brand') }}</p>@endif</div>
                <div><label for="cf-model" class="a18-label">Model</label>
                    <input id="cf-model" name="model" value="{{ $v('model') }}" class="a18-input" placeholder="Avanza">
                    @if ($err('model'))<p class="mt-1 text-xs text-brand-400">{{ $err('model') }}</p>@endif</div>
                <div><label for="cf-variant" class="a18-label">Varian</label>
                    <input id="cf-variant" name="variant" value="{{ $v('variant') }}" class="a18-input" placeholder="1.5 G CVT"></div>
                <div><label for="cf-year" class="a18-label">Tahun</label>
                    <input id="cf-year" name="year" type="number" value="{{ $v('year') }}" class="a18-input">
                    @if ($err('year'))<p class="mt-1 text-xs text-brand-400">{{ $err('year') }}</p>@endif</div>
                <div><label for="cf-body" class="a18-label">Tipe Bodi</label>
                    <select id="cf-body" name="body_type" class="a18-input">@foreach (config('auto18.body_types') as $o)<option value="{{ $o }}" @selected($v('body_type') === $o)>{{ $o }}</option>@endforeach</select></div>
                <div><label for="cf-trans" class="a18-label">Transmisi</label>
                    <select id="cf-trans" name="transmission" class="a18-input">@foreach (config('auto18.transmissions') as $o)<option value="{{ $o }}" @selected($v('transmission') === $o)>{{ $o }}</option>@endforeach</select></div>
                <div><label for="cf-fuel" class="a18-label">Bahan Bakar</label>
                    <select id="cf-fuel" name="fuel" class="a18-input">@foreach (config('auto18.fuels') as $o)<option value="{{ $o }}" @selected($v('fuel') === $o)>{{ $o }}</option>@endforeach</select></div>
                <div><label for="cf-cc" class="a18-label">Mesin (cc)</label>
                    <input id="cf-cc" name="engine_cc" type="number" min="0" value="{{ $v('engine_cc') }}" class="a18-input">
                    @if ($err('engine_cc'))<p class="mt-1 text-xs text-brand-400">{{ $err('engine_cc') }}</p>@endif</div>
                <div><label for="cf-seats" class="a18-label">Kursi</label>
                    <input id="cf-seats" name="seats" type="number" min="1" value="{{ $v('seats') }}" class="a18-input"></div>
                <div><label for="cf-km" class="a18-label">Kilometer</label>
                    <input id="cf-km" name="mileage" type="number" min="0" value="{{ $v('mileage') }}" class="a18-input">
                    @if ($err('mileage'))<p class="mt-1 text-xs text-brand-400">{{ $err('mileage') }}</p>@endif</div>
                <div><label for="cf-color" class="a18-label">Warna</label>
                    <select id="cf-color" name="color" class="a18-input">@foreach (config('auto18.colors') as $o)<option value="{{ $o['name'] }}" @selected($v('color') === $o['name'])>{{ $o['name'] }}</option>@endforeach</select></div>
                <div><label for="cf-plate" class="a18-label">Plat Nomor</label>
                    <input id="cf-plate" name="plate" value="{{ $v('plate') }}" class="a18-input" placeholder="B 1234 ABC"></div>
                <div><label for="cf-loc" class="a18-label">Lokasi</label>
                    <select id="cf-loc" name="location" class="a18-input">@foreach (config('auto18.locations') as $o)<option value="{{ $o }}" @selected($v('location') === $o)>{{ $o }}</option>@endforeach</select></div>
                <div><label for="cf-owner" class="a18-label">Tangan ke-</label>
                    <input id="cf-owner" name="owner_count" type="number" min="1" value="{{ $v('owner_count') }}" class="a18-input"></div>
                <div><label for="cf-tax" class="a18-label">Pajak s/d (TTTT-BB)</label>
                    <input id="cf-tax" name="tax_valid_until" value="{{ $v('tax_valid_until') }}" placeholder="2027-05" class="a18-input">
                    @if ($err('tax_valid_until'))<p class="mt-1 text-xs text-brand-400">{{ $err('tax_valid_until') }}</p>@endif</div>
            </div>
        </section>

        {{-- Harga & status --}}
        <section>
            <h2 class="a18-label">Harga &amp; Status</h2>
            <div class="grid gap-3 sm:grid-cols-3">
                <div><label for="cf-price" class="a18-label">Harga Jual (Rp)</label>
                    <input id="cf-price" name="price" type="number" min="0" step="1000000" x-model="price" class="a18-input">
                    @if ($err('price'))<p class="mt-1 text-xs text-brand-400">{{ $err('price') }}</p>@endif</div>
                <div><label for="cf-buy" class="a18-label">Modal (Rp)</label>
                    <input id="cf-buy" name="buy_price" type="number" min="0" step="1000000" x-model="buy" class="a18-input">
                    @if ($err('buy_price'))<p class="mt-1 text-xs text-brand-400">{{ $err('buy_price') }}</p>@endif</div>
                <div><label for="cf-status" class="a18-label">Status</label>
                    @if ($locked)
                        <p class="a18-input">Terjual</p><input type="hidden" name="status" value="available">
                    @else
                        <select id="cf-status" name="status" class="a18-input">
                            <option value="available" @selected($v('status') === 'available')>Tersedia</option>
                            <option value="booked" @selected($v('status') === 'booked')>Dipesan</option>
                        </select>
                    @endif
                    @if ($err('status'))<p class="mt-1 text-xs text-brand-400">{{ $err('status') }}</p>@endif</div>
            </div>
            <p class="mt-2 text-sm text-neutral-400">Margin: <span class="font-bold" x-bind:class="margin >= 0 ? 'text-emerald-400' : 'text-brand-400'" x-text="rupiah(margin) + ' (' + pct.toFixed(1) + '%)'"></span></p>
            <div class="mt-3 flex flex-wrap gap-5">
                <label class="flex items-center gap-2 text-sm text-neutral-300"><input type="hidden" name="service_record" value="0"><input type="checkbox" name="service_record" value="1" @checked((bool) $v('service_record')) class="h-4 w-4 accent-brand-600"> Riwayat servis ada</label>
                <label class="flex items-center gap-2 text-sm text-neutral-300"><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1" @checked((bool) $v('is_featured')) class="h-4 w-4 accent-brand-600"> Pilihan Auto18</label>
            </div>
        </section>

        {{-- Foto --}}
        <section>
            <h2 class="a18-label">Foto</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div><label for="cf-img" class="a18-label">Foto Bawaan</label>
                    <select id="cf-img" name="image_key" class="a18-input">
                        <option value="">&mdash; Tanpa foto bawaan &mdash;</option>
                        @foreach ($photoKeys as $key)<option value="{{ $key }}" @selected($v('image_key') === $key)>{{ $key }}</option>@endforeach
                    </select></div>
                <div><label for="cf-url" class="a18-label">atau URL Foto Kustom</label>
                    <input id="cf-url" name="image_url" type="url" value="{{ $v('image_url') }}" placeholder="https://..." class="a18-input">
                    @if ($err('image_url'))<p class="mt-1 text-xs text-brand-400">{{ $err('image_url') }}</p>@endif</div>
            </div>
        </section>

        {{-- Fitur & catatan --}}
        <section class="space-y-3">
            <div><label for="cf-features" class="a18-label">Fitur Unggulan (pisahkan dengan koma)</label>
                <input id="cf-features" name="features" value="{{ $features }}" class="a18-input" placeholder="Sunroof, Kamera 360, Cruise Control"></div>
            <div><label for="cf-desc" class="a18-label">Catatan Showroom</label>
                <textarea id="cf-desc" name="description" rows="3" class="a18-input">{{ $v('description') }}</textarea></div>
        </section>

        {{-- Inspeksi --}}
        <section>
            <h2 class="a18-label">Skor Inspeksi (0&ndash;100)</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach (config('auto18.inspection_categories') as $c)
                    <div x-data="{ s: {{ (int) ($inspection[$c['key']] ?? 92) }} }">
                        <label for="insp-{{ $c['key'] }}" class="flex justify-between text-xs text-neutral-300"><span>{{ $c['label'] }}</span><span class="font-bold text-white" x-text="s"></span></label>
                        <input id="insp-{{ $c['key'] }}" type="range" min="0" max="100" name="inspection[{{ $c['key'] }}]" x-model.number="s" class="w-full accent-brand-600">
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex justify-end gap-3 border-t border-white/10 pt-4">
            <a href="{{ route('showroom.cars.index') }}" class="a18-btn-ghost">Batal</a>
            <button type="submit" class="a18-btn-primary"><x-lucide-save class="h-4 w-4" /> Simpan</button>
        </div>
    </form>
</div>
@endsection
