@extends('layouts.app')

@section('title', car_name($car, true))

@php
    $isSold = $car->status === 'sold';
    $fresh = $isSold && $car->sold_at && $car->sold_at->gt(now()->subSeconds(30));
    $monthly = \App\Services\CarPricing::installment($car->price, $store->min_dp_percent, 60, $store->credit_rate)['monthly'];
    $benefits = config('auto18.benefits');
    $plate = $car->plate ? 'Plat ' . explode(' ', trim($car->plate))[0] : '-';
    $taxUntil = '-';
    if ($car->tax_valid_until && preg_match('/^(\d{4})-(\d{2})$/', $car->tax_valid_until, $m)) {
        $taxUntil = \Illuminate\Support\Carbon::create((int) $m[1], (int) $m[2], 1)->locale('id')->translatedFormat('M Y');
    }
    $specs = [
        ['calendar', 'Tahun', $car->year],
        ['gauge', 'Kilometer', km($car->mileage)],
        ['settings-2', 'Transmisi', $car->transmission],
        ['fuel', 'Bahan Bakar', $car->fuel],
        ['cog', 'Mesin', $car->engine_cc ? $car->engine_cc . ' cc' : '-'],
        ['users', 'Kapasitas', $car->seats . ' kursi'],
        ['palette', 'Warna', $car->color ?: '-'],
        ['map-pin', 'Lokasi', $car->location ?: '-'],
        ['file-text', 'Plat', $plate],
        ['users', 'Kepemilikan', 'Tangan ke-' . ($car->owner_count ?: 1)],
        ['calendar', 'Pajak s/d', $taxUntil],
        ['wrench', 'Riwayat Servis', $car->service_record ? 'Ada' : 'Tidak ada'],
    ];
    $waDigits = preg_replace('/\D/', '', (string) $store->whatsapp);
    $waHref = $waDigits ? 'https://wa.me/' . $waDigits . '?text=' . rawurlencode('Halo Auto18, saya tertarik dengan ' . car_name($car, true) . ' (' . $car->code . ')') : '';
    $bookingOpen = $errors->any() && old('customer_name') !== null || $errors->has('phone') || $errors->has('date');
@endphp

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 rounded-t-3xl border border-white/10 bg-ink-800 px-5 py-3.5">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-brand-500">{{ $car->code }}</p>
                <h1 class="truncate font-display text-lg font-black uppercase tracking-wide text-white">{{ car_name($car, true) }}</h1>
            </div>
            <a href="{{ route('cars.index') }}#daftar-mobil" class="a18-btn-ghost shrink-0"><x-lucide-arrow-left class="h-4 w-4" /> <span class="hidden sm:inline">Semua mobil</span></a>
        </div>

        <div class="grid gap-6 rounded-b-3xl border border-t-0 border-white/10 bg-ink-800 p-5 lg:grid-cols-[1.5fr_1fr]">
            {{-- Kiri: media & spesifikasi --}}
            <div class="space-y-6">
                <div class="relative">
                    <div class="relative overflow-hidden rounded-2xl">
                        @include('partials.car-image', ['car' => $car, 'class' => 'aspect-[16/10] w-full rounded-2xl', 'imgClass' => $isSold ? 'grayscale' : ''])
                        @if ($isSold)
                            <div class="pointer-events-none absolute inset-0 flex items-center justify-center">@include('partials.sold-stamp', ['size' => 'md', 'animate' => $fresh])</div>
                        @endif
                    </div>
                    <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
                        @if ($car->is_featured && ! $isSold)
                            <span class="rounded-full bg-brand-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-white">Pilihan Auto18</span>
                        @endif
                        @if ($car->status === 'booked')
                            <span class="rounded-full border border-amber-500/30 bg-amber-500/15 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-300">Dipesan</span>
                        @endif
                    </div>
                </div>

                @if ($photoCredit)
                    <p class="-mt-4 text-[10px] text-neutral-600">
                        Foto ilustrasi (diubah ukurannya): {{ $photoCredit['author'] }} /
                        <a href="{{ $photoCredit['sourceUrl'] }}" target="_blank" rel="noopener noreferrer" class="underline hover:text-neutral-400">Wikimedia Commons</a>
                        (<a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener noreferrer" class="underline hover:text-neutral-400">{{ $photoCredit['license'] }}</a>)
                    </p>
                @endif

                <div>
                    <h2 class="font-display text-sm font-black uppercase tracking-wide text-white">Spesifikasi</h2>
                    <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($specs as [$icon, $label, $value])
                            <div class="rounded-xl border border-white/10 bg-ink-900 p-3">
                                <dt class="flex items-center gap-1.5 text-[10px] uppercase tracking-wider text-neutral-500">@svg('lucide-' . $icon, 'h-3.5 w-3.5') {{ $label }}</dt>
                                <dd class="mt-1 truncate text-sm font-semibold text-white">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                @if (count($car->features ?? []))
                    <div>
                        <h2 class="font-display text-sm font-black uppercase tracking-wide text-white">Fitur Unggulan</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($car->features as $feature)
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-ink-900 px-3 py-1.5 text-xs text-neutral-200"><x-lucide-circle-check class="h-3.5 w-3.5 text-brand-500" aria-hidden="true" /> {{ $feature }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($car->description)
                    <div>
                        <h2 class="font-display text-sm font-black uppercase tracking-wide text-white">Catatan Showroom</h2>
                        <p class="mt-2 text-sm leading-relaxed text-neutral-400">{{ $car->description }}</p>
                    </div>
                @endif

                @include('partials.inspection-report', ['car' => $car])

                <div class="rounded-2xl border border-brand-600/40 bg-brand-950/30 p-5">
                    <h2 class="flex items-center gap-1.5 font-display text-sm font-black uppercase tracking-wide text-white"><x-lucide-shield-check class="h-4 w-4 text-brand-500" aria-hidden="true" /> Gratis Layanan Darurat 24 Jam</h2>
                    <ul class="mt-3 grid grid-cols-1 gap-1.5 text-xs text-neutral-300 sm:grid-cols-2">
                        @foreach (array_slice($benefits, 0, 4) as $benefit)
                            <li class="flex items-center gap-1.5"><x-lucide-circle-check class="h-3.5 w-3.5 shrink-0 text-brand-500" aria-hidden="true" /> {{ $benefit['title'] }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-neutral-500">dan {{ count($benefits) - 4 }} layanan lainnya.</p>
                </div>
            </div>

            {{-- Kanan: harga & aksi --}}
            <div class="space-y-4 lg:self-start">
                <div class="a18-card p-5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-500">Harga Cash</p>
                    <p class="font-display text-3xl font-black text-white">{{ rupiah($car->price) }}</p>
                    @unless ($isSold)
                        <p class="mt-1 text-sm text-brand-400">Cicilan mulai {{ rupiah_short($monthly) }}/bln <span class="text-neutral-500">&middot; DP {{ rtrim(rtrim(number_format($store->min_dp_percent, 2, ',', '.'), '0'), ',') }}% &middot; 60 bln</span></p>
                    @endunless

                    @if ($isSold)
                        <div class="mt-4 rounded-xl border border-white/10 bg-ink-900 p-4 text-sm">
                            <p class="font-bold text-white">Mobil ini sudah terjual</p>
                            <p class="mt-1 text-neutral-400">{{ $car->sold_at ? $car->sold_at->locale('id')->translatedFormat('j M Y') : '-' }}{{ $car->sold_price ? ' · ' . rupiah($car->sold_price) : '' }}</p>
                        </div>
                    @else
                        <div class="mt-4 space-y-2" x-data="{ booking: {{ $bookingOpen ? 'true' : 'false' }} }">
                            @if (session('booking_code'))
                                <div class="rounded-xl border border-white/15 bg-ink-900 p-4">
                                    <p class="flex items-center gap-1.5 text-sm font-bold text-white"><x-lucide-circle-check class="h-4 w-4 text-brand-500" aria-hidden="true" /> Booking test drive terkirim</p>
                                    <p class="mt-1 text-xs text-neutral-400">Kode booking <span class="font-mono font-bold text-white">{{ session('booking_code') }}</span>. Tim Auto18 akan menghubungi Anda untuk konfirmasi.</p>
                                </div>
                            @else
                                <button type="button" x-on:click="booking = !booking" class="a18-btn-primary w-full py-3">
                                    <x-lucide-calendar-check class="h-4 w-4" /> <span x-text="booking ? 'Tutup Form' : 'Booking Test Drive'">Booking Test Drive</span>
                                </button>
                            @endif

                            @if ($waHref)
                                <a href="{{ $waHref }}" target="_blank" rel="noopener noreferrer" class="a18-btn-outline w-full"><x-lucide-message-circle class="h-4 w-4" /> Chat WhatsApp</a>
                            @else
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', (string) $store->call_center) }}" class="a18-btn-outline w-full"><x-lucide-phone class="h-4 w-4" /> Telepon {{ $store->call_center }}</a>
                            @endif

                            {{-- Form booking test drive --}}
                            <form x-show="booking" @unless ($bookingOpen) x-cloak @endunless method="POST" action="{{ route('cars.test-drive', $car) }}" class="a18-card mt-3 space-y-3 p-5" novalidate>
                                @csrf
                                <h2 class="font-display text-sm font-black uppercase tracking-wide text-white">Jadwal Test Drive</h2>

                                <div>
                                    <label for="td-name" class="a18-label">Nama Lengkap</label>
                                    <input id="td-name" name="customer_name" value="{{ old('customer_name') }}" class="a18-input" placeholder="Nama Anda" required>
                                    @error('customer_name')<p class="mt-1 text-xs text-brand-400">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="td-phone" class="a18-label">Nomor HP</label>
                                    <input id="td-phone" name="phone" type="tel" value="{{ old('phone') }}" class="a18-input" placeholder="0812-3456-7890" required>
                                    @error('phone')<p class="mt-1 text-xs text-brand-400">{{ $message }}</p>@enderror
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="td-date" class="a18-label">Tanggal</label>
                                        <input id="td-date" name="date" type="date" min="{{ now()->toDateString() }}" value="{{ old('date', now()->toDateString()) }}" class="a18-input" required>
                                        @error('date')<p class="mt-1 text-xs text-brand-400">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="td-time" class="a18-label">Jam</label>
                                        <select id="td-time" name="time" class="a18-input">
                                            @foreach (\App\Http\Requests\StoreTestDriveRequest::TIME_SLOTS as $slot)<option value="{{ $slot }}" @selected(old('time', '10:00') === $slot)>{{ $slot }}</option>@endforeach
                                        </select>
                                    </div>
                                </div>

                                <fieldset x-data="{ loc: '{{ old('location', 'showroom') }}' }">
                                    <legend class="a18-label">Lokasi</legend>
                                    <div class="flex gap-2">
                                        @foreach ([['showroom', 'Di Showroom'], ['rumah', 'Di Rumah Saya']] as [$id, $label])
                                            <label class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold transition-colors"
                                                   x-bind:class="loc === '{{ $id }}' ? 'border-brand-500 bg-brand-600 text-white' : 'border-white/15 text-neutral-300 hover:border-white/40'">
                                                <input type="radio" name="location" value="{{ $id }}" class="sr-only" x-model="loc"> {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <div class="mt-3" x-show="loc === 'rumah'" @if (old('location') !== 'rumah') x-cloak @endif>
                                        <label for="td-address" class="a18-label">Alamat</label>
                                        <input id="td-address" name="address" value="{{ old('address') }}" class="a18-input" placeholder="Kecamatan, kota">
                                        @error('address')<p class="mt-1 text-xs text-brand-400">{{ $message }}</p>@enderror
                                    </div>
                                </fieldset>

                                <div>
                                    <label for="td-notes" class="a18-label">Catatan (opsional)</label>
                                    <textarea id="td-notes" name="notes" rows="2" class="a18-input" placeholder="Misal: ingin tanya simulasi kredit">{{ old('notes') }}</textarea>
                                </div>
                                <button type="submit" class="a18-btn-primary w-full">Kirim Booking</button>
                            </form>
                        </div>
                    @endif

                    <button type="button" x-data x-on:click="$store.favorites.toggle({{ $car->id }})" x-bind:aria-pressed="$store.favorites.has({{ $car->id }})" class="a18-btn-ghost mt-2 w-full justify-center">
                        <span x-bind:class="$store.favorites.has({{ $car->id }}) ? 'text-brand-500 [&_svg]:fill-brand-500' : ''" class="inline-flex"><x-lucide-heart class="h-4 w-4" /></span>
                        <span x-text="$store.favorites.has({{ $car->id }}) ? 'Tersimpan di Favorit' : 'Simpan ke Favorit'">Simpan ke Favorit</span>
                    </button>
                </div>

                @unless ($isSold)
                    @include('partials.credit-simulator', ['price' => $car->price, 'compact' => true, 'uid' => 'car'])
                @endunless

                @auth
                    <div class="a18-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-500">Panel Showroom</p>
                        <div class="mt-2 space-y-2">
                            @if (Route::has('showroom.cars.sale.create') && ! $isSold)
                                <a href="{{ route('showroom.cars.sale.create', $car) }}" class="a18-btn-outline w-full"><x-lucide-receipt class="h-4 w-4" /> Proses Penjualan</a>
                            @endif
                            @if (Route::has('showroom.cars.edit'))
                                <a href="{{ route('showroom.cars.edit', $car) }}" class="a18-btn-ghost w-full border border-white/10"><x-lucide-pencil class="h-4 w-4" /> Edit Data Mobil</a>
                            @endif
                            @if ($isSold && $car->sold_transaction_id && Route::has('showroom.receipt'))
                                <a href="{{ route('showroom.receipt', $car->sold_transaction_id) }}" class="a18-btn-outline w-full"><x-lucide-receipt class="h-4 w-4" /> Lihat Nota</a>
                            @endif
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>
@endsection
