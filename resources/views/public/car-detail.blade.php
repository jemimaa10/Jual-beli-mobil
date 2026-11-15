@extends('layouts.app')

@section('title', car_name($car, true))
@section('description', car_name($car, true) . ' · ' . rupiah($car->price) . ' · ' . km($car->mileage) . ' · ' . $car->transmission . ' · ' . $car->location . '. Inspeksi ' . $car->inspection_score . '/100.')
@if ($car->image_src)
    @section('og_image')<meta property="og:image" content="{{ $car->image_src }}">@endsection
@endif

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
    {{-- Header --}}
    <section class="a18-speedlines border-b border-white/10 bg-ink-900">
        <div class="mx-auto flex max-w-6xl flex-wrap items-end justify-between gap-4 px-4 py-7 sm:px-6 md:py-9 lg:px-8">
            <div class="min-w-0">
                <span class="a18-eyebrow"><span class="a18-stripes-red h-2.5 w-10" aria-hidden="true"></span><span class="text-brand-500">{{ $car->code }}</span> &middot; {{ $car->year }} &middot; {{ $car->location }}</span>
                <h1 class="a18-display mt-2 text-[40px] leading-[0.92] text-white md:text-[56px]">{{ $car->brand }} {{ $car->model }}</h1>
                <p class="mt-1.5 text-base text-white/65 md:text-lg">{{ $car->variant }} &middot; {{ $car->transmission }} &middot; {{ $car->fuel }}</p>
            </div>
            <a href="{{ route('cars.index') }}#daftar-mobil" class="a18-btn-skew shrink-0"><span class="a18-unskew a18-cond flex items-center gap-2 text-base font-bold uppercase tracking-wide"><x-lucide-arrow-left class="h-4 w-4" /> Semua mobil</span></a>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[1.5fr_1fr]">
            {{-- Kiri: media & spesifikasi --}}
            <div class="space-y-6">
                <div class="relative">
                    <div class="a18-cut-card relative overflow-hidden bg-ink-900 shadow-[0_30px_60px_-20px_rgba(0,0,0,.8)]">
                        @include('partials.car-image', ['car' => $car, 'class' => 'aspect-[16/10] w-full', 'imgClass' => $isSold ? 'grayscale' : ''])
                        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-black/80 to-transparent" aria-hidden="true"></div>
                        @if ($isSold)
                            <div class="pointer-events-none absolute inset-0 flex items-center justify-center">@include('partials.sold-stamp', ['size' => 'md', 'animate' => $fresh])</div>
                        @elseif ($car->status === 'booked')
                            <div class="pointer-events-none absolute inset-0 flex items-center justify-center"><span class="a18-display -rotate-6 bg-amber-400 px-8 py-2.5 text-4xl tracking-wider text-ink shadow-xl">Dipesan</span></div>
                        @endif
                        @if ($car->inspection_score > 0)
                            <div class="absolute bottom-4 right-5 text-right">
                                <p class="a18-display text-5xl leading-none text-white md:text-6xl">{{ $car->inspection_score }}</p>
                                <p class="a18-cond -mt-0.5 text-xs font-bold uppercase tracking-widest text-white/75">Skor inspeksi</p>
                            </div>
                        @endif
                    </div>
                    @if ($car->is_featured && ! $isSold)
                        <div class="a18-skew absolute -top-3 left-3 bg-white px-4 py-1.5 text-ink"><span class="a18-unskew a18-display block text-base tracking-wider">Pilihan Auto18</span></div>
                    @endif
                </div>

                @if ($photoCredit)
                    <p class="-mt-4 text-xs text-white/40">
                        Foto ilustrasi (diubah ukurannya): {{ $photoCredit['author'] }} /
                        <a href="{{ $photoCredit['sourceUrl'] }}" target="_blank" rel="noopener noreferrer" class="underline hover:text-neutral-400">Wikimedia Commons</a>
                        (<a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener noreferrer" class="underline hover:text-neutral-400">{{ $photoCredit['license'] }}</a>)
                    </p>
                @endif

                <div>
                    <h2 class="a18-display flex items-center gap-2.5 text-2xl text-white"><span class="a18-skew h-5 w-1.5 bg-brand-600" aria-hidden="true"></span>Spesifikasi</h2>
                    <dl class="mt-4 grid grid-cols-2 gap-px border border-white/10 bg-white/10 sm:grid-cols-3">
                        @foreach ($specs as [$icon, $label, $value])
                            <div class="min-w-0 bg-ink-800 p-3.5">
                                <dt class="a18-cond flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-white/50">@svg('lucide-' . $icon, 'h-4 w-4 text-brand-600') {{ $label }}</dt>
                                <dd class="mt-1 truncate text-[15px] font-semibold text-white">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                @if (count($car->features ?? []))
                    <div>
                        <h2 class="a18-display flex items-center gap-2.5 text-2xl text-white"><span class="a18-skew h-5 w-1.5 bg-brand-600" aria-hidden="true"></span>Fitur Unggulan</h2>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($car->features as $feature)
                                <span class="inline-flex items-center gap-1.5 border border-white/15 bg-ink-800 px-3 py-2 text-sm text-white/85"><x-lucide-circle-check class="h-4 w-4 text-brand-600" aria-hidden="true" /> {{ $feature }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($car->description)
                    <div>
                        <h2 class="a18-display flex items-center gap-2.5 text-2xl text-white"><span class="a18-skew h-5 w-1.5 bg-brand-600" aria-hidden="true"></span>Catatan Showroom</h2>
                        <p class="mt-3 text-[15px] leading-relaxed text-white/70">{{ $car->description }}</p>
                    </div>
                @endif

                @include('partials.inspection-report', ['car' => $car])

                <div class="relative overflow-hidden border-l-4 border-brand-600 bg-ink-800 p-5">
                    <div class="a18-checker absolute inset-y-0 right-0 w-3 opacity-60" aria-hidden="true"></div>
                    <h2 class="a18-display flex items-center gap-2 text-xl text-white"><x-lucide-shield-check class="h-5 w-5 text-brand-600" aria-hidden="true" /> Gratis Layanan Darurat 24 Jam</h2>
                    <ul class="mt-3 grid grid-cols-1 gap-2 text-sm text-white/80 sm:grid-cols-2">
                        @foreach (array_slice($benefits, 0, 4) as $benefit)
                            <li class="a18-cond flex items-center gap-2 text-base font-bold uppercase tracking-wide"><span class="a18-display text-brand-600">{{ str_pad($benefit['no'], 2, '0', STR_PAD_LEFT) }}</span> {{ $benefit['title'] }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-[13px] text-white/50">dan {{ count($benefits) - 4 }} layanan lainnya.</p>
                </div>
            </div>

            {{-- Kanan: harga & aksi --}}
            <div class="space-y-5 lg:sticky lg:top-28 lg:self-start">
                <div class="a18-card relative p-5">
                    <div class="absolute -top-px left-0 h-[3px] w-24 bg-brand-600" aria-hidden="true"></div>
                    <p class="a18-cond text-xs font-semibold uppercase tracking-widest text-white/55">Harga Cash</p>
                    <p class="a18-display text-4xl leading-none {{ $isSold ? 'text-white/50' : 'text-white' }}">{{ rupiah($car->price) }}</p>
                    @unless ($isSold)
                        <div class="-mx-5 mt-4 flex items-center justify-between gap-3 bg-white px-5 py-3 text-ink">
                            <span class="a18-cond text-xs font-semibold uppercase tracking-widest text-ink/60">Cicilan mulai</span>
                            <span class="a18-display text-2xl leading-none">{{ rupiah_short($monthly) }}/bln</span>
                        </div>
                        <p class="mt-2 text-[13px] text-white/50">DP {{ rtrim(rtrim(number_format($store->min_dp_percent, 2, ',', '.'), '0'), ',') }}% &middot; 60 bln &middot; bunga flat</p>
                    @endunless

                    @if ($isSold)
                        <div class="mt-4 border border-white/10 bg-ink p-4 text-sm">
                            <p class="a18-display text-xl text-white">Mobil ini sudah terjual</p>
                            <p class="mt-1 text-white/60">{{ $car->sold_at ? $car->sold_at->locale('id')->translatedFormat('j M Y') : '-' }}{{ $car->sold_price ? ' · ' . rupiah($car->sold_price) : '' }}</p>
                        </div>
                    @else
                        <div id="booking" class="mt-4 scroll-mt-24 space-y-2" x-data="{ booking: {{ $bookingOpen ? 'true' : 'false' }} }" x-on:open-booking.window="booking = true">
                            @if (session('booking_code'))
                                <div class="border-l-4 border-brand-600 bg-ink p-4">
                                    <p class="a18-cond flex items-center gap-1.5 text-lg font-bold uppercase tracking-wide text-white"><x-lucide-circle-check class="h-5 w-5 text-brand-600" aria-hidden="true" /> Booking test drive terkirim</p>
                                    <p class="mt-1 text-[13px] text-white/60">Kode booking <span class="a18-cond font-bold tracking-wider text-white">{{ session('booking_code') }}</span>. Tim Auto18 akan menghubungi Anda untuk konfirmasi.</p>
                                </div>
                            @else
                                <button type="button" x-on:click="booking = !booking" class="a18-btn-primary h-14 w-full text-xl">
                                    <x-lucide-calendar-check class="h-5 w-5" /> <span x-text="booking ? 'Tutup Form' : 'Booking Test Drive'">Booking Test Drive</span>
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
                                <h2 class="a18-display text-xl text-white">Jadwal Test Drive</h2>

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
                                            <label class="a18-cond flex min-h-[2.75rem] flex-1 cursor-pointer items-center justify-center gap-2 border px-3 text-[15px] font-bold uppercase tracking-wide transition-colors focus-within:ring-2 focus-within:ring-brand-500"
                                                   x-bind:class="loc === '{{ $id }}' ? 'border-brand-600 bg-brand-600 text-white' : 'border-white/15 text-white/75 hover:border-white/40'">
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
                        <p class="a18-cond text-xs font-bold uppercase tracking-[0.2em] text-white/50">Untuk Staf</p>
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
    {{-- Ponsel & tablet: harga dan tombol booking selalu terlihat (di layar lebar kartu harga sudah di sisi kanan) --}}
    @unless ($isSold)
        <div class="fixed inset-x-0 bottom-0 z-40 border-t-2 border-brand-600 bg-ink/95 p-3 backdrop-blur-sm lg:hidden">
            <div class="mx-auto flex max-w-6xl items-center gap-3">
                <div class="min-w-0 flex-1">
                    <p class="a18-cond text-xs font-semibold uppercase tracking-widest text-white/55">Harga Cash</p>
                    <p class="a18-display truncate text-2xl leading-none text-white">{{ rupiah($car->price) }}</p>
                </div>
                <button type="button" class="a18-btn-primary h-12 shrink-0 px-5 text-lg"
                        onclick="document.getElementById('booking').scrollIntoView({ behavior: 'smooth', block: 'start' }); window.dispatchEvent(new CustomEvent('open-booking'))">
                    <x-lucide-calendar-check class="h-4 w-4" /> Test Drive
                </button>
            </div>
        </div>
        <div class="h-20 lg:hidden" aria-hidden="true"></div>
    @endunless
@endsection
