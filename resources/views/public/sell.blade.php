@extends('layouts.app')

@section('title', 'Jual Mobil')

@php
    $stepFields = [
        1 => ['brand', 'model', 'variant', 'year', 'body_type', 'transmission', 'fuel', 'mileage', 'color', 'location', 'condition'],
        2 => ['inspection_date', 'inspection_time', 'asking_price', 'trade_in_car_id'],
        3 => ['customer_name', 'phone', 'email', 'notes'],
    ];
    $startStep = 1;
    foreach ($stepFields as $n => $fields) {
        if (collect($fields)->contains(fn ($f) => $errors->has($f))) { $startStep = $n; break; }
    }
    $values = [
        'brand' => old('brand', 'Toyota'),
        'model' => old('model', ''),
        'variant' => old('variant', ''),
        'year' => (int) old('year', now()->year - 5),
        'body_type' => old('body_type', 'MPV'),
        'transmission' => old('transmission', 'Otomatis'),
        'fuel' => old('fuel', 'Bensin'),
        'mileage' => old('mileage', ''),
        'color' => old('color', 'Putih'),
        'location' => old('location', 'Jakarta Barat'),
        'condition' => old('condition', 'Baik'),
        'asking_price' => old('asking_price', ''),
        'inspection_date' => old('inspection_date', now()->toDateString()),
        'inspection_time' => old('inspection_time', '10:00'),
        'trade_in_car_id' => old('trade_in_car_id', ''),
        'customer_name' => old('customer_name', ''),
        'phone' => old('phone', ''),
        'email' => old('email', ''),
        'notes' => old('notes', ''),
    ];
    $serverErrors = collect($errors->getMessages())->map(fn ($m) => $m[0])->all();
    $label = fn (string $t) => $t === 'trade-in' ? 'Tukar Tambah' : 'Jual Langsung';
@endphp

@section('content')
@if ($submitted)
    {{-- Layar sukses --}}
    <section class="a18-stripes py-14">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="a18-card p-6 sm:p-8">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-600 text-white"><x-lucide-circle-check class="h-6 w-6" aria-hidden="true" /></span>
                    <div>
                        <h1 class="a18-heading text-xl sm:text-2xl">Permintaan Terkirim</h1>
                        <p class="text-sm text-neutral-400">Kode permintaan <span class="font-mono font-bold text-white">{{ $submitted->code }}</span></p>
                    </div>
                </div>

                <dl class="mt-6 space-y-3 border-t border-white/10 pt-5 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-neutral-400">Mobil</dt><dd class="text-right font-semibold text-white">{{ car_name($submitted, true) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-neutral-400">Jenis</dt><dd class="font-semibold text-white">{{ $label($submitted->type) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-neutral-400">Estimasi Harga</dt><dd class="text-right font-semibold text-white">{{ rupiah($submitted->estimate_low) }} &ndash; {{ rupiah($submitted->estimate_high) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-neutral-400">Jadwal Inspeksi</dt><dd class="font-semibold text-white">{{ $submitted->inspection_date?->format('Y-m-d') }} &middot; {{ $submitted->inspection_time }}</dd></div>
                    @if ($submitted->tradeInCar)
                        <div class="flex justify-between gap-4"><dt class="text-neutral-400">Ditukar dengan</dt><dd class="text-right font-semibold text-white">{{ car_name($submitted->tradeInCar, true) }}</dd></div>
                    @endif
                </dl>

                <p class="mt-5 text-sm text-neutral-400">Tim Auto18 akan menghubungi Anda untuk konfirmasi jadwal inspeksi. Harga final ditentukan setelah pengecekan kondisi mobil.</p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('sell.create') }}" class="a18-btn-outline">Ajukan Lagi</a>
                    <a href="{{ route('cars.index') }}" class="a18-btn-primary">Lihat Mobil Dijual <x-lucide-arrow-right class="h-4 w-4" /></a>
                </div>
            </div>
        </div>
    </section>
    @include('partials.how-it-works', ['title' => 'Cara Jual Mobil di Auto18', 'subtitle' => null, 'steps' => config('auto18.how_it_works_sell')])
    @include('partials.faq-section', ['title' => 'Pertanyaan Seputar Jual Mobil', 'faqs' => config('auto18.faqs_sell')])
@else
    <div x-data="sellForm({
            type: @js(old('type', 'sell')), step: {{ $startStep }}, values: @js($values), tables: @js($tables), cars: @js($targetCars),
            today: '{{ now()->toDateString() }}', serverErrors: @js($serverErrors) })">
        {{-- Hero --}}
        <section class="a18-stripes">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-14 lg:px-8">
                <h1 class="a18-heading text-3xl leading-tight sm:text-5xl">Jual Mobil Anda dengan <span class="text-brand-500">Harga Terbaik</span></h1>
                <p class="mt-3 max-w-xl text-sm text-neutral-300 sm:text-base">Isi data mobil, dapatkan estimasi harga instan, lalu jadwalkan inspeksi. Bisa jual langsung atau tukar tambah dengan mobil di showroom.</p>

                <div class="mt-6 inline-flex rounded-2xl border border-white/15 bg-black/60 p-1">
                    @foreach ([['sell', 'Jual Langsung', 'sparkles'], ['trade-in', 'Tukar Tambah', 'arrow-left-right']] as [$id, $text, $icon])
                        <button type="button" x-on:click="type = '{{ $id }}'; errors = {}" x-bind:aria-pressed="type === '{{ $id }}'"
                                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition-colors"
                                x-bind:class="type === '{{ $id }}' ? 'bg-brand-600 text-white' : 'text-neutral-300 hover:text-white'">
                            @svg('lucide-' . $icon, 'h-4 w-4') {{ $text }}
                        </button>
                    @endforeach
                </div>
                @include('partials.social-links', ['class' => 'mt-6'])
            </div>
        </section>

        {{-- Form --}}
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
                <form method="POST" action="{{ route('sell.store') }}" x-on:submit="submit($event)" novalidate class="a18-card p-5 sm:p-6">
                    @csrf
                    <input type="hidden" name="type" x-bind:value="type">

                    {{-- Stepper --}}
                    <ol class="flex items-center gap-2">
                        @foreach ([[1, 'Data Mobil', 'clipboard-list'], [2, 'Jadwal Inspeksi', 'calendar-check'], [3, 'Data Kontak', 'user-round']] as [$no, $text, $icon])
                            <li class="flex flex-1 items-center gap-2">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-sm font-black transition-colors duration-200"
                                      x-bind:class="step === {{ $no }} ? 'border-brand-500 bg-brand-600 text-white' : (step > {{ $no }} ? 'border-white/40 bg-white/10 text-white' : 'border-white/15 text-neutral-500')">
                                    <span x-show="step <= {{ $no }}">@svg('lucide-' . $icon, 'h-4 w-4')</span>
                                    <span x-show="step > {{ $no }}" x-cloak><x-lucide-circle-check class="h-4 w-4" /></span>
                                </span>
                                <span class="hidden text-xs font-bold uppercase tracking-wide transition-colors duration-200 sm:block" x-bind:class="step < {{ $no }} ? 'text-neutral-500' : 'text-white'">{{ $text }}</span>
                                @if ($no < 3)
                                    <span class="relative h-px flex-1 bg-white/10" aria-hidden="true"><span class="absolute inset-0 origin-left bg-brand-600 transition-transform duration-300" x-bind:style="step > {{ $no }} ? 'transform: scaleX(1)' : 'transform: scaleX(0)'"></span></span>
                                @endif
                            </li>
                        @endforeach
                    </ol>

                    <div class="mt-5 space-y-4">
                        {{-- LANGKAH 1 (tetap di DOM agar ikut terkirim; hanya disembunyikan) --}}
                        <div x-show="step === 1" class="space-y-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="sc-brand" class="a18-label">Merek</label>
                                    <select id="sc-brand" name="brand" x-model="f.brand" class="a18-input">@foreach (config('auto18.brands') as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select>
                                </div>
                                <div>
                                    <label for="sc-model" class="a18-label">Model</label>
                                    <input id="sc-model" name="model" x-model="f.model" class="a18-input" placeholder="Avanza, Xpander, Brio...">
                                    <p class="mt-1 text-xs text-brand-400" x-show="err('model')" x-text="err('model')"></p>
                                </div>
                                <div>
                                    <label for="sc-variant" class="a18-label">Varian (opsional)</label>
                                    <input id="sc-variant" name="variant" x-model="f.variant" class="a18-input" placeholder="1.5 G CVT">
                                </div>
                                <div>
                                    <label for="sc-year" class="a18-label">Tahun</label>
                                    <select id="sc-year" name="year" x-model.number="f.year" class="a18-input">@foreach ($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach</select>
                                    <p class="mt-1 text-xs text-brand-400" x-show="err('year')" x-text="err('year')"></p>
                                </div>
                                <div>
                                    <label for="sc-body" class="a18-label">Tipe Bodi</label>
                                    <select id="sc-body" name="body_type" x-model="f.body_type" class="a18-input">@foreach (config('auto18.body_types') as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select>
                                </div>
                                <div>
                                    <label for="sc-trans" class="a18-label">Transmisi</label>
                                    <select id="sc-trans" name="transmission" x-model="f.transmission" class="a18-input">@foreach (config('auto18.transmissions') as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select>
                                </div>
                                <div>
                                    <label for="sc-fuel" class="a18-label">Bahan Bakar</label>
                                    <select id="sc-fuel" name="fuel" x-model="f.fuel" class="a18-input">@foreach (config('auto18.fuels') as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select>
                                </div>
                                <div>
                                    <label for="sc-km" class="a18-label">Kilometer</label>
                                    <input id="sc-km" name="mileage" type="number" min="0" step="1000" x-model="f.mileage" class="a18-input" placeholder="45000">
                                    <p class="mt-1 text-xs text-brand-400" x-show="err('mileage')" x-text="err('mileage')"></p>
                                </div>
                                <div>
                                    <label for="sc-color" class="a18-label">Warna</label>
                                    <select id="sc-color" name="color" x-model="f.color" class="a18-input">@foreach (config('auto18.colors') as $v)<option value="{{ $v['name'] }}">{{ $v['name'] }}</option>@endforeach</select>
                                </div>
                                <div>
                                    <label for="sc-location" class="a18-label">Lokasi Mobil</label>
                                    <select id="sc-location" name="location" x-model="f.location" class="a18-input">@foreach (config('auto18.locations') as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select>
                                </div>
                            </div>
                            <fieldset>
                                <legend class="a18-label">Kondisi Mobil</legend>
                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    @foreach (config('auto18.conditions') as $c)
                                        <label class="cursor-pointer rounded-xl border px-3 py-2.5 text-center text-xs font-bold transition-colors"
                                               x-bind:class="f.condition === '{{ $c }}' ? 'border-brand-500 bg-brand-600 text-white' : 'border-white/15 text-neutral-300 hover:border-white/40'">
                                            <input type="radio" name="condition" value="{{ $c }}" class="sr-only" x-model="f.condition"> {{ $c }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        </div>

                        {{-- LANGKAH 2 --}}
                        <div x-show="step === 2" x-cloak class="space-y-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="sc-date" class="a18-label">Tanggal Inspeksi</label>
                                    <input id="sc-date" name="inspection_date" type="date" x-bind:min="today" x-model="f.inspection_date" class="a18-input">
                                    <p class="mt-1 text-xs text-brand-400" x-show="err('inspection_date')" x-text="err('inspection_date')"></p>
                                </div>
                                <div>
                                    <label for="sc-asking" class="a18-label">Harga Diharapkan (opsional)</label>
                                    <input id="sc-asking" name="asking_price" type="number" min="0" step="1000000" x-model="f.asking_price" class="a18-input" x-bind:placeholder="estimate.mid">
                                    <p class="mt-1 text-xs text-brand-400" x-show="err('asking_price')" x-text="err('asking_price')"></p>
                                </div>
                            </div>

                            <fieldset>
                                <legend class="a18-label">Jam Inspeksi</legend>
                                <input type="hidden" name="inspection_time" x-bind:value="f.inspection_time">
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($timeSlots as $slot)
                                        <button type="button" x-on:click="f.inspection_time = '{{ $slot }}'" x-bind:aria-pressed="f.inspection_time === '{{ $slot }}'"
                                                class="rounded-xl border px-3 py-1.5 text-xs font-bold transition-colors"
                                                x-bind:class="f.inspection_time === '{{ $slot }}' ? 'border-brand-500 bg-brand-600 text-white' : 'border-white/15 text-neutral-300 hover:border-white/40 hover:text-white'">{{ $slot }}</button>
                                    @endforeach
                                </div>
                                <p class="mt-1 text-xs text-brand-400" x-show="err('inspection_time')" x-text="err('inspection_time')"></p>
                            </fieldset>

                            <div x-show="type === 'trade-in'" x-cloak class="a18-card p-4">
                                <label for="sc-target" class="a18-label">Mobil Auto18 yang Diinginkan</label>
                                <select id="sc-target" name="trade_in_car_id" x-model="f.trade_in_car_id" x-bind:disabled="type !== 'trade-in'" class="a18-input">
                                    <option value="">&mdash; Pilih mobil &mdash;</option>
                                    <template x-for="c in cars" :key="c.id"><option x-bind:value="c.id" x-text="c.name + ' · ' + rupiah(c.price)" x-bind:selected="String(c.id) === String(f.trade_in_car_id)"></option></template>
                                </select>
                                <p class="mt-1 text-xs text-brand-400" x-show="err('trade_in_car_id')" x-text="err('trade_in_car_id')"></p>

                                <div x-show="target" x-cloak class="mt-3 flex items-center gap-3 rounded-xl border border-white/10 bg-ink-900 p-3">
                                    <img x-show="target && target.image" x-bind:src="target && target.image" alt="" class="h-16 w-24 shrink-0 rounded-lg object-cover">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-white" x-text="target && target.name"></p>
                                        <p class="text-xs text-neutral-400" x-text="target && rupiah(target.price)"></p>
                                        <p class="mt-1 text-xs text-brand-400">Perkiraan tambahan bayar: <span class="font-bold" x-text="rupiah(gap)"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- LANGKAH 3 --}}
                        <div x-show="step === 3" x-cloak class="space-y-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="sc-name" class="a18-label">Nama Lengkap</label>
                                    <input id="sc-name" name="customer_name" x-model="f.customer_name" class="a18-input" placeholder="Nama sesuai STNK">
                                    <p class="mt-1 text-xs text-brand-400" x-show="err('customer_name')" x-text="err('customer_name')"></p>
                                </div>
                                <div>
                                    <label for="sc-phone" class="a18-label">Nomor HP</label>
                                    <input id="sc-phone" name="phone" type="tel" x-model="f.phone" class="a18-input" placeholder="0812-3456-7890">
                                    <p class="mt-1 text-xs text-brand-400" x-show="err('phone')" x-text="err('phone')"></p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="sc-email" class="a18-label">Email (opsional)</label>
                                    <input id="sc-email" name="email" type="email" x-model="f.email" class="a18-input" placeholder="nama@email.com">
                                    <p class="mt-1 text-xs text-brand-400" x-show="err('email')" x-text="err('email')"></p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="sc-notes" class="a18-label">Catatan (opsional)</label>
                                    <textarea id="sc-notes" name="notes" rows="3" x-model="f.notes" class="a18-input" placeholder="Misal: pajak baru diperpanjang, ada baret di bumper"></textarea>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-ink-900 p-4">
                                <h2 class="font-display text-sm font-black uppercase tracking-wide text-white">Ringkasan</h2>
                                <dl class="mt-3 space-y-2 text-sm">
                                    <div class="flex justify-between gap-4"><dt class="text-neutral-400">Mobil</dt><dd class="text-right font-semibold text-white" x-text="[f.brand, f.model, f.variant, f.year].filter(Boolean).join(' ')"></dd></div>
                                    <div class="flex justify-between gap-4"><dt class="text-neutral-400">Kilometer</dt><dd class="font-semibold text-white" x-text="km + ' km'"></dd></div>
                                    <div class="flex justify-between gap-4"><dt class="text-neutral-400">Jenis</dt><dd class="font-semibold text-white" x-text="type === 'trade-in' ? 'Tukar Tambah' : 'Jual Langsung'"></dd></div>
                                    <div class="flex justify-between gap-4"><dt class="text-neutral-400">Inspeksi</dt><dd class="font-semibold text-white" x-text="f.inspection_date + ' · ' + f.inspection_time"></dd></div>
                                </dl>
                            </div>
                        </div>
                    </div>

                    {{-- Navigasi langkah --}}
                    <div class="mt-5 flex items-center justify-between gap-3 border-t border-white/10 pt-5">
                        <button type="button" x-on:click="back()" x-bind:disabled="step === 1" class="a18-btn-ghost"><x-lucide-arrow-left class="h-4 w-4" /> Kembali</button>
                        <button type="button" x-show="step < 3" x-on:click="next()" class="a18-btn-primary">Lanjut <x-lucide-arrow-right class="h-4 w-4" /></button>
                        <button type="submit" x-show="step === 3" x-cloak class="a18-btn-primary"><x-lucide-circle-check class="h-4 w-4" /> Kirim Permintaan</button>
                    </div>
                </form>

                {{-- Panel estimasi --}}
                <aside class="lg:sticky lg:top-28 lg:self-start">
                    <div class="a18-card p-5">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-brand-500">Estimasi Harga</p>
                        <p class="mt-1 font-display text-2xl font-black leading-tight text-white"><span x-text="rupiah(estimate.low)"></span><span class="text-neutral-500"> &ndash; </span><span x-text="rupiah(estimate.high)"></span></p>
                        <p class="mt-2 text-xs text-neutral-400" x-text="f.brand + ' ' + (f.model || '(model belum diisi)') + ' ' + f.year + ' · ' + km + ' km · ' + f.condition"></p>

                        <p class="mt-4 flex items-start gap-1.5 rounded-xl border border-white/10 bg-ink-900 p-3 text-xs leading-relaxed text-neutral-400">
                            <x-lucide-info class="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand-500" aria-hidden="true" />
                            Angka ini perkiraan otomatis dari merek, tipe, tahun, kilometer, dan kondisi. Harga final ditentukan setelah inspeksi langsung dan tidak mengikat.
                        </p>

                        <div x-show="type === 'trade-in' && target" x-cloak class="mt-4 border-t border-white/10 pt-4">
                            <p class="a18-label">Perkiraan Tambahan Bayar</p>
                            <p class="font-display text-xl font-black text-brand-500" x-text="rupiah(gap)"></p>
                            <p class="mt-1 text-xs text-neutral-500"><span x-text="target && target.name"></span> dikurangi estimasi mobil Anda.</p>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        @include('partials.how-it-works', ['title' => 'Cara Jual Mobil di Auto18', 'subtitle' => 'Dari isi data sampai dana cair, prosesnya transparan.', 'steps' => config('auto18.how_it_works_sell')])
        @include('partials.faq-section', ['title' => 'Pertanyaan Seputar Jual Mobil', 'faqs' => config('auto18.faqs_sell')])
    </div>
@endif
@endsection
