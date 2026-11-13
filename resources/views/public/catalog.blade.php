@extends('layouts.app')

@section('title', 'Beli Mobil Second')

@php
    use App\Http\Controllers\Public\CarCatalogController as Catalog;

    $toggleBrand = fn (string $brand) => Catalog::queryFor(array_merge($filters, [
        'brand' => in_array($brand, $filters['brand'], true) ? array_values(array_diff($filters['brand'], [$brand])) : [...$filters['brand'], $brand],
    ]));
    $toggleBody = fn (string $type) => Catalog::queryFor(array_merge($filters, ['body' => $filters['body'] === $type ? '' : $type]));
    $resetUrl = route('cars.index') . '#daftar-mobil';
@endphp

@section('content')
    {{-- HERO --}}
    <section class="a18-stripes relative overflow-hidden">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-black/50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.2em] text-white">
                        <x-lucide-shield-check class="h-3.5 w-3.5 text-brand-500" aria-hidden="true" /> Mobil Second Bergaransi Inspeksi
                    </span>
                    <h1 class="a18-heading mt-4 text-3xl leading-[1.05] sm:text-5xl lg:text-6xl">Jual / Beli <span class="text-brand-500">Mobil Second</span> Berkualitas Hanya di Auto 18</h1>
                    <p class="mt-4 max-w-xl text-sm leading-relaxed text-neutral-300 sm:text-base">Pilihan mobil bekas terawat, harga transparan, bisa tukar tambah dan kredit. Setiap pembelian dilengkapi layanan darurat 24 jam di seluruh Indonesia.</p>

                    {{-- Pencarian cepat --}}
                    <form method="GET" action="{{ route('cars.index') }}#daftar-mobil" class="mt-6 rounded-2xl border border-white/15 bg-black/70 p-3 backdrop-blur">
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="relative sm:col-span-2">
                                <x-lucide-search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" aria-hidden="true" />
                                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari Xpander, Fortuner..." aria-label="Cari merek atau model mobil" class="a18-input pl-9">
                            </div>
                            <select name="body" aria-label="Tipe bodi" class="a18-input">
                                <option value="">Semua Tipe</option>
                                @foreach (config('auto18.body_types') as $type)<option value="{{ $type }}" @selected($filters['body'] === $type)>{{ $type }}</option>@endforeach
                            </select>
                            <select name="price" aria-label="Rentang harga" class="a18-input">
                                <option value="">Semua Harga</option>
                                @foreach (config('auto18.price_ranges') as $range)<option value="{{ $range['id'] }}" @selected($filters['price'] === $range['id'])>{{ $range['label'] }}</option>@endforeach
                            </select>
                            <button type="submit" class="a18-btn-primary sm:col-span-2"><x-lucide-search class="h-4 w-4" /> Cari Mobil</button>
                        </div>
                    </form>

                    <div class="mt-6 flex flex-wrap gap-x-8 gap-y-3">
                        <div><p class="font-display text-2xl font-black text-white">{{ $unsoldCount }}</p><p class="text-[11px] uppercase tracking-wider text-neutral-400">Mobil Tersedia</p></div>
                        <div><p class="font-display text-2xl font-black text-white">{{ count($brandCounts) }}</p><p class="text-[11px] uppercase tracking-wider text-neutral-400">Merek Pilihan</p></div>
                        <div><p class="font-display text-2xl font-black text-brand-500">24 Jam</p><p class="text-[11px] uppercase tracking-wider text-neutral-400">Layanan Darurat</p></div>
                    </div>

                    @include('partials.social-links', ['class' => 'mt-6'])
                </div>

                @if ($featuredCar)
                    <div class="hidden lg:block">
                        <div class="a18-card overflow-hidden">
                            @include('partials.car-image', ['car' => $featuredCar, 'class' => 'aspect-[16/10] w-full'])
                            <div class="flex items-center justify-between gap-4 p-5">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black uppercase tracking-wider text-brand-500">Pilihan Auto18</p>
                                    <p class="truncate font-display text-lg font-black uppercase text-white">{{ car_name($featuredCar) }}</p>
                                    <p class="text-xs text-neutral-400">{{ $featuredCar->year }} &middot; {{ $featuredCar->location }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-display text-xl font-black text-white">{{ rupiah_short($featuredCar->price) }}</p>
                                    <a href="{{ route('cars.show', $featuredCar) }}" class="mt-1 inline-block text-xs font-bold text-brand-400 hover:text-brand-300">Lihat detail &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- PINTASAN MEREK & TIPE --}}
    <section class="border-y border-white/10 bg-ink-900">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <h2 class="font-display text-sm font-black uppercase tracking-wide text-white">Cari Berdasarkan Merek</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach (config('auto18.brands') as $brand)
                    @if ($brandCounts[$brand] ?? 0)
                        @php $on = in_array($brand, $filters['brand'], true); @endphp
                        <a href="{{ $toggleBrand($brand) }}" aria-pressed="{{ $on ? 'true' : 'false' }}"
                           class="rounded-xl border px-3.5 py-2 text-sm font-bold transition-colors {{ $on ? 'border-brand-500 bg-brand-600 text-white' : 'border-white/15 text-neutral-300 hover:border-white/40 hover:text-white' }}">
                            {{ $brand }} <span class="text-xs font-normal opacity-70">({{ $brandCounts[$brand] }})</span>
                        </a>
                    @endif
                @endforeach
            </div>

            <h2 class="mt-6 font-display text-sm font-black uppercase tracking-wide text-white">Cari Berdasarkan Tipe</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach (config('auto18.body_types') as $type)
                    @if ($bodyCounts[$type] ?? 0)
                        @php $on = $filters['body'] === $type; @endphp
                        <a href="{{ $toggleBody($type) }}" aria-pressed="{{ $on ? 'true' : 'false' }}"
                           class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-sm font-bold transition-colors {{ $on ? 'border-brand-500 bg-brand-600 text-white' : 'border-white/15 text-neutral-300 hover:border-white/40 hover:text-white' }}">
                            <x-lucide-car class="h-4 w-4" aria-hidden="true" /> {{ $type }} <span class="text-xs font-normal opacity-70">({{ $bodyCounts[$type] }})</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- DAFTAR MOBIL --}}
    <section id="daftar-mobil" class="mx-auto max-w-7xl scroll-mt-28 px-4 py-10 sm:px-6 lg:px-8"
             x-data="{ filterOpen: false, favOnly: {{ $filters['favorit'] ? 'true' : 'false' }}, ids: @js($cars->pluck('id')->all()),
                       get shown() { return this.favOnly ? this.ids.filter(i => $store.favorites.has(i)).length : this.ids.length } }"
             x-on:keydown.escape.window="filterOpen = false">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="a18-heading text-2xl sm:text-3xl">Daftar Mobil</h2>
                <p class="mt-1 text-sm text-neutral-400"><span class="font-bold text-white" x-text="shown">{{ $cars->count() }}</span> mobil ditemukan</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" x-on:click="filterOpen = true" class="a18-btn-outline lg:hidden"><x-lucide-sliders-horizontal class="h-4 w-4" /> Filter</button>
                <div>
                    <label for="sort" class="sr-only">Urutkan</label>
                    <select id="sort" class="a18-input w-auto" onchange="const u = new URL(location.href); u.searchParams.set('sort', this.value); u.hash = 'daftar-mobil'; location.href = u.toString()">
                        @foreach ($sortOptions as $id => $label)<option value="{{ $id }}" @selected($filters['sort'] === $id)>{{ $label }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        @if (count($activeChips))
            <div class="mt-4 flex flex-wrap items-center gap-2">
                @foreach ($activeChips as $chip)
                    <a href="{{ $chip['url'] }}" class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-ink-800 px-3 py-1 text-xs text-neutral-200 hover:border-brand-500" aria-label="Hapus filter {{ $chip['label'] }}">
                        {{ $chip['label'] }} <x-lucide-x class="h-3 w-3 text-neutral-500" />
                    </a>
                @endforeach
                <a href="{{ $resetUrl }}" class="text-xs font-bold text-brand-400 hover:text-brand-300">Reset semua</a>
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-[280px_1fr]">
            <aside class="hidden lg:block">
                <div class="a18-card sticky top-28 p-5">
                    <h3 class="mb-4 flex items-center gap-2 font-display text-sm font-black uppercase tracking-wide text-white"><x-lucide-sliders-horizontal class="h-4 w-4 text-brand-500" aria-hidden="true" /> Filter</h3>
                    @include('partials.catalog-filters', ['suffix' => 'd'])
                </div>
            </aside>

            <div>
                @if ($cars->isEmpty())
                    <div class="a18-card flex flex-col items-center justify-center p-12 text-center">
                        <x-lucide-car class="h-10 w-10 text-neutral-600" aria-hidden="true" />
                        <p class="mt-4 font-display text-lg font-black uppercase text-white">Mobil Tidak Ditemukan</p>
                        <p class="mt-1 max-w-sm text-sm text-neutral-400">Coba ubah kata kunci atau longgarkan filter pencarian Anda.</p>
                        <a href="{{ $resetUrl }}" class="a18-btn-primary mt-5"><x-lucide-rotate-ccw class="h-4 w-4" /> Reset Filter</a>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($cars as $car)
                            <div class="grid" x-show="!favOnly || $store.favorites.has({{ $car->id }})" @if ($filters['favorit']) x-cloak @endif>
                                @include('partials.car-card', ['car' => $car])
                            </div>
                        @endforeach
                    </div>
                    <div x-show="favOnly && shown === 0" x-cloak class="a18-card mt-5 p-8 text-center text-sm text-neutral-400">
                        Belum ada mobil favorit. Tekan ikon hati pada kartu mobil untuk menyimpannya.
                    </div>
                @endif
            </div>
        </div>

        {{-- Drawer filter (ponsel) --}}
        <div x-show="filterOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Filter mobil">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" x-on:click="filterOpen = false" aria-hidden="true"></div>
            <div class="absolute inset-y-0 right-0 flex w-[88%] max-w-sm flex-col border-l border-white/10 bg-ink-800">
                <div class="flex items-center justify-between border-b border-white/10 p-4">
                    <h3 class="font-display text-sm font-black uppercase tracking-wide text-white">Filter Mobil</h3>
                    <button type="button" x-on:click="filterOpen = false" aria-label="Tutup filter" class="a18-btn-ghost px-2"><x-lucide-x class="h-5 w-5" /></button>
                </div>
                <div class="flex-1 overflow-y-auto p-4">@include('partials.catalog-filters', ['suffix' => 'm'])</div>
            </div>
        </div>
    </section>

    {{-- SIMULASI KREDIT --}}
    <section class="bg-ink-900 py-14">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @include('partials.section-heading', ['eyebrow' => 'Hitung Dulu', 'title' => 'Simulasi Cicilan Mobil', 'subtitle' => 'Geser harga mobil dan uang muka untuk memperkirakan cicilan bulanan Anda.'])
            <div class="mt-8 grid gap-6 md:grid-cols-2" x-data="{ price: {{ (int) $medianPrice }}, rupiah: window.auto18.rupiah, setPrice(p) { this.price = p; this.$dispatch('sim-price', p) } }">
                <div class="a18-card p-5 sm:p-6">
                    <label for="sim-price" class="a18-label">Harga Mobil</label>
                    <p class="font-display text-2xl font-black text-white" x-text="rupiah(price)">{{ rupiah($medianPrice) }}</p>
                    <input id="sim-price" type="range" min="50000000" max="800000000" step="5000000" x-bind:value="price" x-on:input="setPrice(Number($event.target.value))" class="mt-3 w-full accent-brand-600">
                    <div class="flex justify-between text-[10px] text-neutral-500"><span>Rp 50 jt</span><span>Rp 800 jt</span></div>

                    <div class="mt-5 border-t border-white/10 pt-4">
                        <p class="a18-label">Atau pilih dari stok</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($simulationCars as $c)
                                <button type="button" x-on:click="setPrice({{ (int) $c->price }})" class="inline-flex items-center gap-1.5 rounded-xl border border-white/15 px-3 py-1.5 text-xs font-semibold text-neutral-300 transition-colors hover:border-brand-500 hover:text-white">
                                    <x-lucide-tag class="h-3 w-3 text-brand-500" aria-hidden="true" /> {{ $c->model }} &middot; {{ rupiah_short($c->price) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                @include('partials.credit-simulator', ['price' => $medianPrice, 'tenor' => 48, 'priceEvent' => 'sim-price', 'uid' => 'home'])
            </div>
        </div>
    </section>

    @include('partials.how-it-works', ['title' => 'Cara Beli Mobil di Auto18', 'subtitle' => 'Empat langkah sederhana dari pilih mobil sampai bawa pulang.', 'steps' => config('auto18.how_it_works_buy')])
    @include('partials.benefits-section')
    @include('partials.tips-section')

    {{-- CTA JUAL MOBIL --}}
    <section class="a18-stripes py-14">
        <div class="mx-auto max-w-5xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="a18-heading text-2xl sm:text-4xl">Mau Jual atau <span class="text-brand-500">Tukar Tambah</span> Mobil Anda?</h2>
            <p class="mx-auto mt-3 max-w-xl text-sm text-neutral-300 sm:text-base">Dapatkan estimasi harga instan, jadwalkan inspeksi, dan terima pembayaran cepat setelah dokumen beres.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('sell.create') }}" class="a18-btn-primary px-6 py-3 text-base">Jual Mobil Sekarang <x-lucide-arrow-right class="h-4 w-4" /></a>
                <a href="{{ route('cars.index', ['favorit' => 1]) }}#daftar-mobil" class="a18-btn-outline px-6 py-3 text-base"><x-lucide-heart class="h-4 w-4" /> Lihat Favorit Saya</a>
            </div>
        </div>
    </section>

    @include('partials.faq-section', ['title' => 'Pertanyaan Seputar Beli Mobil', 'faqs' => config('auto18.faqs_buy')])
@endsection
