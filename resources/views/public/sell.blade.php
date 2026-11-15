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
    <section class="a18-stripes py-14 md:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="a18-card relative p-6 sm:p-8">
                <div class="absolute -top-px left-0 h-[3px] w-28 bg-brand-600" aria-hidden="true"></div>
                <div class="flex items-center gap-4">
                    <span class="a18-cut-tag flex h-14 w-16 shrink-0 items-center justify-center bg-brand-600 text-white"><x-lucide-circle-check class="h-7 w-7" aria-hidden="true" /></span>
                    <div>
                        <h1 class="a18-heading text-4xl leading-none sm:text-5xl">Permintaan Terkirim</h1>
                        <p class="mt-1 text-sm text-white/60">Kode permintaan <span class="a18-cond font-bold tracking-wider text-white">{{ $submitted->code }}</span></p>
                    </div>
                </div>

                <dl class="mt-6 space-y-3 border-t border-white/10 pt-5 text-[15px]">
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
        <section class="a18-speedlines relative overflow-hidden bg-ink">
            <div class="a18-cut-hero absolute inset-y-0 right-0 hidden w-[calc(34%+16px)] bg-white lg:block" aria-hidden="true"></div>
            <div class="a18-cut-hero absolute inset-y-0 right-0 hidden w-[calc(34%+6px)] bg-ink lg:block" aria-hidden="true"></div>
            <div class="a18-cut-hero absolute inset-y-0 right-0 hidden w-[34%] bg-brand-600 lg:block" aria-hidden="true">
                <div class="a18-stripes-white absolute inset-0 opacity-[0.12]"></div>
            </div>
            <div class="relative mx-auto max-w-7xl px-4 pb-12 pt-10 sm:px-6 md:pb-16 md:pt-14 lg:px-8">
                <span class="a18-eyebrow"><span class="a18-stripes-red h-3 w-10" aria-hidden="true"></span>Jual / Tukar Tambah</span>
                <h1 class="a18-heading mt-4 max-w-3xl text-[44px] leading-[0.9] sm:text-6xl lg:text-[72px]">Jual Mobil Anda dengan <span class="text-brand-600">Harga Terbaik</span></h1>
                <p class="mt-5 max-w-xl text-base leading-relaxed text-white/70 md:text-[17px]">Isi data mobil, dapatkan estimasi harga instan, lalu jadwalkan inspeksi. Bisa jual langsung atau tukar tambah dengan mobil di showroom.</p>

                <div class="mt-7 inline-flex gap-2">
                    @foreach ([['sell', 'Jual Langsung', 'sparkles'], ['trade-in', 'Tukar Tambah', 'arrow-left-right']] as [$id, $text, $icon])
                        <button type="button" x-on:click="type = '{{ $id }}'; errors = {}" x-bind:aria-pressed="type === '{{ $id }}'"
                                class="a18-skew inline-flex h-12 items-center border px-5 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                                x-bind:class="type === '{{ $id }}' ? 'border-white bg-white text-ink' : 'border-white/20 bg-ink-800 text-white/75 hover:border-white/60 hover:text-white'">
                            <span class="a18-unskew a18-cond flex items-center gap-2 text-lg font-bold uppercase tracking-wide">@svg('lucide-' . $icon, 'h-4 w-4') {{ $text }}</span>
                        </button>
                    @endforeach
                </div>
                @include('partials.social-links', ['class' => 'mt-6'])
            </div>
        </section>

        {{-- Form --}}
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
                <form method="POST" action="{{ route('sell.store') }}" x-on:submit="submit($event)" novalidate class="a18-card relative p-5 sm:p-6">
                    <div class="absolute -top-px left-0 h-[3px] w-28 bg-brand-600" aria-hidden="true"></div>
                    @csrf
                    <input type="hidden" name="type" x-bind:value="type">

                    {{-- Stepper --}}
                    <ol class="flex items-center gap-2" aria-label="Langkah pengisian">
                        @foreach ([[1, 'Data Mobil', 'clipboard-list'], [2, 'Jadwal Inspeksi', 'calendar-check'], [3, 'Data Kontak', 'user-round']] as [$no, $text, $icon])
                            <li class="flex flex-1 items-center gap-2" x-bind:aria-current="step === {{ $no }} ? 'step' : null">
                                <span data-step="{{ str_pad($no, 2, '0', STR_PAD_LEFT) }}" class="a18-skew flex h-10 w-12 shrink-0 items-center justify-center border transition-colors duration-200"
                                      x-bind:class="step === {{ $no }} ? 'border-brand-600 bg-brand-600 text-white' : (step > {{ $no }} ? 'border-white bg-white text-ink' : 'border-white/20 text-white/50')">
                                    <span class="a18-unskew a18-display text-xl leading-none" x-show="step <= {{ $no }}">{{ str_pad($no, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="a18-unskew" x-show="step > {{ $no }}" x-cloak><x-lucide-check class="h-5 w-5" /></span>
                                </span>
                                <span class="a18-cond hidden text-base font-bold uppercase tracking-wide transition-colors duration-200 sm:block" x-bind:class="step < {{ $no }} ? 'text-white/60' : 'text-white'">{{ $text }}</span>
                                @if ($no < 3)
                                    <span class="relative h-[3px] flex-1 bg-white/10" aria-hidden="true"><span class="absolute inset-0 origin-left bg-brand-600 transition-transform duration-300" x-bind:style="step > {{ $no }} ? 'transform: scaleX(1)' : 'transform: scaleX(0)'"></span></span>
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
                                        <label class="a18-cond flex min-h-[2.75rem] cursor-pointer items-center justify-center border px-3 text-center text-[15px] font-bold uppercase tracking-wide transition-colors focus-within:ring-2 focus-within:ring-brand-500"
                                               x-bind:class="f.condition === '{{ $c }}' ? 'border-brand-600 bg-brand-600 text-white' : 'border-white/15 text-white/75 hover:border-white/40'">
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
                                                class="a18-cond min-h-[2.5rem] border px-3 text-[15px] font-bold tracking-wide transition-colors"
                                                x-bind:class="f.inspection_time === '{{ $slot }}' ? 'border-brand-600 bg-brand-600 text-white' : 'border-white/15 text-white/75 hover:border-white/40 hover:text-white'">{{ $slot }}</button>
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

                                <div x-show="target" x-cloak class="mt-3 flex items-center gap-3 border border-white/10 bg-ink p-3">
                                    <img x-show="target && target.image" x-bind:src="target && target.image" alt="" class="a18-cut-img h-16 w-24 shrink-0 object-cover">
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

                            <div class="border-l-4 border-brand-600 bg-ink p-4">
                                <h2 class="a18-display text-xl text-white">Ringkasan</h2>
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
                    <div class="mt-6 flex items-center justify-between gap-3 border-t border-white/10 pt-5">
                        <button type="button" x-on:click="back()" x-bind:disabled="step === 1" class="a18-btn-ghost"><x-lucide-arrow-left class="h-4 w-4" /> Kembali</button>
                        <button type="button" x-show="step < 3" x-on:click="next()" class="a18-btn-primary">Lanjut <x-lucide-arrow-right class="h-4 w-4" /></button>
                        <button type="submit" x-show="step === 3" x-cloak class="a18-btn-primary"><x-lucide-circle-check class="h-4 w-4" /> Kirim Permintaan</button>
                    </div>
                </form>

                {{-- Panel estimasi --}}
                <aside class="lg:sticky lg:top-28 lg:self-start">
                    <div class="a18-card relative overflow-hidden p-5">
                        <div class="absolute inset-x-0 top-0 h-[3px] bg-brand-600" aria-hidden="true"></div>
                        <span class="a18-eyebrow text-brand-500">Estimasi Harga</span>
                        <p class="a18-display mt-2 text-[32px] leading-[0.95] text-white"><span x-text="rupiah(estimate.low)"></span><span class="text-white/40"> &ndash; </span><br class="hidden lg:block"><span x-text="rupiah(estimate.high)"></span></p>
                        <p class="mt-3 text-[13px] text-white/60" x-text="f.brand + ' ' + (f.model || '(model belum diisi)') + ' ' + f.year + ' · ' + km + ' km · ' + f.condition"></p>

                        <p class="mt-4 flex items-start gap-2 border border-white/10 bg-ink p-3 text-[13px] leading-relaxed text-white/60">
                            <x-lucide-info class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" aria-hidden="true" />
                            Angka ini perkiraan otomatis dari merek, tipe, tahun, kilometer, dan kondisi. Harga final ditentukan setelah inspeksi langsung dan tidak mengikat.
                        </p>

                        <div x-show="type === 'trade-in' && target" x-cloak class="mt-4 border-t border-white/10 pt-4">
                            <p class="a18-label">Perkiraan Tambahan Bayar</p>
                            <p class="a18-display text-3xl leading-none text-brand-500" x-text="rupiah(gap)"></p>
                            <p class="mt-1 text-[13px] text-white/50"><span x-text="target && target.name"></span> dikurangi estimasi mobil Anda.</p>
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
