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
    @php
        $featuredMonthly = $featuredCar ? \App\Services\CarPricing::installment($featuredCar->price, $store->min_dp_percent, 60, $store->credit_rate)['monthly'] : 0;
        $stats = [
            [$unsoldCount, "Mobil siap\ndibawa pulang", true],
            [count($brandCounts), "Merek\npilihan", false],
            [count(config('auto18.inspection_categories')), "Kategori\ninspeksi", false],
            [24, "Jam layanan\ndarurat", false],
        ];
        $chip = 'a18-skew flex h-11 shrink-0 items-stretch transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500';
    @endphp

    {{-- HERO --}}
    <section class="a18-speedlines relative overflow-hidden bg-ink">
        {{-- Lempeng diagonal putih / aspal / merah (layar lebar) --}}
        <div class="a18-cut-hero absolute inset-y-0 right-0 hidden w-[calc(48%+20px)] bg-white lg:block" aria-hidden="true"></div>
        <div class="a18-cut-hero absolute inset-y-0 right-0 hidden w-[calc(48%+8px)] bg-ink lg:block" aria-hidden="true"></div>
        <div class="a18-cut-hero absolute inset-y-0 right-0 hidden w-[48%] bg-brand-600 lg:block" aria-hidden="true"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-8 px-4 pb-10 pt-8 sm:px-6 md:pt-14 lg:grid-cols-[1fr_1.05fr] lg:gap-6 lg:px-8 lg:pb-20 lg:pt-16">
            <div class="relative z-10 min-w-0">
                <span class="a18-eyebrow"><span class="a18-stripes-red h-3 w-10" aria-hidden="true"></span>Mobil Second Bergaransi Inspeksi</span>
                <h1 class="a18-display mt-5 text-[44px] leading-[0.9] text-white sm:text-6xl lg:text-[68px] xl:text-[76px]">Jual / Beli <span class="text-brand-600">Mobil Second</span> Berkualitas Hanya di Auto 18</h1>
                <p class="mt-5 max-w-[520px] text-base leading-relaxed text-white/70 md:text-[17px]">Pilihan mobil bekas terawat, harga transparan, bisa tukar tambah dan kredit. Setiap pembelian dilengkapi layanan darurat 24 jam di seluruh Indonesia.</p>

                {{-- Pencarian cepat --}}
                <form method="GET" action="{{ route('cars.index') }}#daftar-mobil" class="relative mt-7 border border-white/10 bg-ink-800 p-3 md:p-4 lg:mr-6">
                    <div class="absolute -top-[3px] left-0 h-[3px] w-28 bg-brand-600" aria-hidden="true"></div>
                    <div class="relative">
                        <x-lucide-search class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-white/50" aria-hidden="true" />
                        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari Xpander, Fortuner..." aria-label="Cari merek atau model mobil" class="a18-input h-12 pl-12">
                    </div>
                    <div class="mt-2.5 grid grid-cols-2 gap-2.5 sm:grid-cols-[1fr_1fr_auto]">
                        <select name="body" aria-label="Tipe bodi" class="a18-input h-12">
                            <option value="">Semua Tipe</option>
                            @foreach (config('auto18.body_types') as $type)<option value="{{ $type }}" @selected($filters['body'] === $type)>{{ $type }}</option>@endforeach
                        </select>
                        <select name="price" aria-label="Rentang harga" class="a18-input h-12">
                            <option value="">Semua Harga</option>
                            @foreach (config('auto18.price_ranges') as $range)<option value="{{ $range['id'] }}" @selected($filters['price'] === $range['id'])>{{ $range['label'] }}</option>@endforeach
                        </select>
                        <button type="submit" class="a18-btn-primary col-span-2 h-12 text-xl sm:col-span-1">Cari Mobil <x-lucide-arrow-right class="h-5 w-5" /></button>
                    </div>
                </form>

                @include('partials.social-links', ['class' => 'mt-6'])
            </div>

            @if ($featuredCar)
                {{-- Mobil pilihan --}}
                <div class="relative z-10 min-w-0 lg:pl-10">
                    <div class="relative">
                        <div class="a18-skew absolute -top-3 left-2 z-20 bg-white px-4 py-1.5 text-ink lg:-left-5"><span class="a18-unskew a18-display block text-base tracking-wider">Pilihan Auto18</span></div>
                        <a href="{{ route('cars.show', $featuredCar) }}" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Lihat detail {{ car_name($featuredCar, true) }}">
                            <div class="a18-cut-card relative overflow-hidden bg-ink-900 shadow-[0_30px_60px_-20px_rgba(0,0,0,.8)]">
                                @include('partials.car-image', ['car' => $featuredCar, 'class' => 'aspect-[16/10] w-full', 'imgClass' => 'transition-transform duration-500 group-hover:scale-[1.03]'])
                                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/85 to-transparent" aria-hidden="true"></div>
                                <div class="absolute inset-x-5 bottom-4 flex items-end justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="a18-cond text-xs font-bold uppercase tracking-[0.2em] text-white/70">{{ $featuredCar->year }} &middot; {{ $featuredCar->location }}</p>
                                        <p class="a18-display mt-1 truncate text-3xl leading-none text-white md:text-[38px]">{{ $featuredCar->brand }} {{ $featuredCar->model }}</p>
                                        <p class="mt-1 truncate text-sm text-white/75">{{ $featuredCar->variant }} &middot; {{ $featuredCar->fuel }}</p>
                                    </div>
                                    @if ($featuredCar->inspection_score > 0)
                                        <div class="shrink-0 text-right">
                                            <p class="a18-display text-[44px] leading-none text-white md:text-[56px]">{{ $featuredCar->inspection_score }}</p>
                                            <p class="a18-cond -mt-0.5 text-xs font-bold uppercase tracking-widest text-white/70">Skor inspeksi</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="-mt-px flex items-stretch">
                                <div class="min-w-0 flex-1 border border-t-0 border-white/10 bg-ink px-5 py-3">
                                    <p class="a18-cond text-xs font-semibold uppercase tracking-widest text-white/55">Harga</p>
                                    <p class="a18-display truncate text-2xl leading-none text-white sm:text-3xl">{{ rupiah($featuredCar->price) }}</p>
                                </div>
                                <div class="flex min-w-[130px] flex-col justify-center bg-white px-5 text-ink sm:min-w-[150px]">
                                    <span class="a18-cond text-xs font-semibold uppercase tracking-widest text-ink/60">Cicilan mulai</span>
                                    <span class="a18-display text-xl leading-none sm:text-[22px]">{{ rupiah_short($featuredMonthly) }}/bln</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Angka besar --}}
        <div class="relative border-t border-white/10 bg-ink">
            <dl class="mx-auto grid max-w-7xl grid-cols-2 px-4 sm:px-6 lg:grid-cols-4 lg:px-8">
                @foreach ($stats as $i => [$value, $label, $red])
                    <div class="flex items-center gap-4 border-white/10 py-5 lg:py-6 {{ $i % 2 === 0 ? 'border-r pr-4' : 'pl-4' }} {{ $i < 2 ? 'border-b lg:border-b-0' : '' }} {{ $i > 0 ? 'lg:px-6' : '' }} {{ $i === 1 ? 'lg:border-r' : '' }}">
                        <dd class="a18-display order-first text-5xl leading-none lg:text-[64px] {{ $red ? 'text-brand-600' : 'text-white' }}">{{ $value }}</dd>
                        <dt class="a18-cond whitespace-pre-line text-sm font-bold uppercase leading-tight tracking-wide text-white/80 lg:text-base">{{ $label }}</dt>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- PINTASAN MEREK & TIPE --}}
    <section class="border-y border-white/10 bg-ink-900">
        <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 md:py-7 lg:px-8">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-8">
                <h2 class="flex shrink-0 items-center gap-3 md:w-44"><span class="a18-skew h-7 w-1.5 bg-brand-600" aria-hidden="true"></span><span class="a18-display text-2xl leading-none text-white">Cari Merek</span></h2>
                <div class="a18-no-scrollbar -mx-4 flex gap-2.5 overflow-x-auto px-4 py-1 md:mx-0 md:flex-wrap md:px-1">
                    @foreach (config('auto18.brands') as $brand)
                        @if ($brandCounts[$brand] ?? 0)
                            @php $on = in_array($brand, $filters['brand'], true); @endphp
                            <a href="{{ $toggleBrand($brand) }}" aria-pressed="{{ $on ? 'true' : 'false' }}"
                               class="{{ $chip }} {{ $on ? 'bg-white text-ink' : 'border border-white/15 bg-ink-800 text-white hover:border-white/50' }}">
                                <span class="a18-unskew a18-cond flex items-center px-4 text-base font-bold uppercase tracking-wide">{{ $brand }}</span>
                                <span class="flex items-center px-3 {{ $on ? 'bg-brand-600 text-white' : 'bg-ink-600 text-white' }}"><span class="a18-unskew a18-display text-lg">{{ $brandCounts[$brand] }}</span></span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-8">
                <h2 class="flex shrink-0 items-center gap-3 md:w-44"><span class="a18-skew h-7 w-1.5 bg-brand-600" aria-hidden="true"></span><span class="a18-display text-2xl leading-none text-white">Cari Tipe</span></h2>
                <div class="a18-no-scrollbar -mx-4 flex gap-2.5 overflow-x-auto px-4 py-1 md:mx-0 md:flex-wrap md:px-1">
                    @foreach (config('auto18.body_types') as $type)
                        @if ($bodyCounts[$type] ?? 0)
                            @php $on = $filters['body'] === $type; @endphp
                            <a href="{{ $toggleBody($type) }}" aria-pressed="{{ $on ? 'true' : 'false' }}"
                               class="{{ $chip }} {{ $on ? 'bg-white text-ink' : 'border border-white/15 bg-ink-800 text-white hover:border-white/50' }}">
                                <span class="a18-unskew a18-cond flex items-center gap-2 px-4 text-base font-bold uppercase tracking-wide"><x-lucide-car class="h-4 w-4" aria-hidden="true" /> {{ $type }}</span>
                                <span class="flex items-center px-3 {{ $on ? 'bg-brand-600 text-white' : 'bg-ink-600 text-white' }}"><span class="a18-unskew a18-display text-lg">{{ $bodyCounts[$type] }}</span></span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- DAFTAR MOBIL --}}
    <section id="daftar-mobil" class="mx-auto max-w-7xl scroll-mt-28 px-4 py-12 sm:px-6 md:py-16 lg:px-8"
             x-data="{ filterOpen: false, favOnly: {{ $filters['favorit'] ? 'true' : 'false' }}, ids: @js($cars->pluck('id')->all()),
                       get shown() { return this.favOnly ? this.ids.filter(i => $store.favorites.has(i)).length : this.ids.length } }"
             x-on:keydown.escape.window="filterOpen = false">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <span class="a18-eyebrow"><span class="a18-stripes-red h-2.5 w-14" aria-hidden="true"></span><span><span class="text-white" x-text="shown">{{ $cars->count() }}</span> mobil ditemukan</span></span>
                <h2 class="a18-display mt-2 text-[44px] leading-[0.9] text-white md:text-[64px]">Daftar Mobil</h2>
            </div>
            <div class="flex w-full items-center gap-2 sm:w-auto">
                <button type="button" x-on:click="filterOpen = true" class="a18-btn-outline flex-1 sm:flex-none lg:hidden"><x-lucide-sliders-horizontal class="h-4 w-4" /> Filter</button>
                <div class="flex-1 sm:flex-none">
                    <label for="sort" class="sr-only">Urutkan</label>
                    <select id="sort" name="sort" class="a18-input sm:w-56" onchange="const u = new window.URL(window.location.href); u.searchParams.set('sort', this.value); u.hash = 'daftar-mobil'; window.location.href = u.toString()">
                        @foreach ($sortOptions as $id => $label)<option value="{{ $id }}" @selected($filters['sort'] === $id)>{{ $label }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        @if (count($activeChips))
            <div class="mt-5 flex flex-wrap items-center gap-2">
                @foreach ($activeChips as $chip)
                    <a href="{{ $chip['url'] }}" class="a18-cond inline-flex min-h-[2.5rem] items-center gap-1.5 border border-white/15 bg-ink-800 px-3 text-sm font-bold uppercase tracking-wide text-white/85 hover:border-brand-600" aria-label="Hapus filter {{ $chip['label'] }}">
                        {{ $chip['label'] }} <x-lucide-x class="h-4 w-4 text-white/50" />
                    </a>
                @endforeach
                <a href="{{ $resetUrl }}" class="a18-cond inline-flex min-h-[2.5rem] items-center px-2 text-sm font-bold uppercase tracking-wide text-brand-500 hover:text-brand-400">Reset semua</a>
            </div>
        @endif

        <div class="mt-8 grid gap-6 lg:grid-cols-[280px_1fr]">
            <aside class="hidden lg:block">
                <div class="a18-card sticky top-28 p-5">
                    <div class="absolute -top-px left-0 h-[3px] w-20 bg-brand-600" aria-hidden="true"></div>
                    <h3 class="a18-display mb-4 flex items-center gap-2 text-xl text-white"><x-lucide-sliders-horizontal class="h-5 w-5 text-brand-600" aria-hidden="true" /> Filter</h3>
                    @include('partials.catalog-filters', ['suffix' => 'd'])
                </div>
            </aside>

            <div>
                @if ($cars->isEmpty())
                    <div class="a18-card flex flex-col items-center justify-center p-12 text-center">
                        <x-lucide-car class="h-10 w-10 text-white/30" aria-hidden="true" />
                        <p class="a18-display mt-4 text-3xl text-white">Mobil Tidak Ditemukan</p>
                        <p class="mt-1 max-w-sm text-sm text-neutral-400">Coba ubah kata kunci atau longgarkan filter pencarian Anda.</p>
                        <a href="{{ $resetUrl }}" class="a18-btn-primary mt-5"><x-lucide-rotate-ccw class="h-4 w-4" /> Reset Filter</a>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 md:gap-6 xl:grid-cols-3">
                        @foreach ($cars as $car)
                            <div class="grid" x-show="!favOnly || $store.favorites.has({{ $car->id }})" @if ($filters['favorit']) x-cloak @endif>
                                @include('partials.car-card', ['car' => $car, 'gridNo' => $loop->iteration])
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
            <div class="absolute inset-y-0 right-0 flex w-[88%] max-w-sm flex-col border-l-4 border-brand-600 bg-ink-800">
                <div class="flex items-center justify-between border-b border-white/10 p-4">
                    <h3 class="a18-display text-2xl text-white">Filter Mobil</h3>
                    <button type="button" x-on:click="filterOpen = false" aria-label="Tutup filter" class="a18-btn-ghost h-11 w-11 px-0"><x-lucide-x class="h-5 w-5" /></button>
                </div>
                <div class="flex-1 overflow-y-auto p-4">@include('partials.catalog-filters', ['suffix' => 'm'])</div>
            </div>
        </div>
    </section>

    {{-- SIMULASI KREDIT --}}
    <section id="simulasi" class="scroll-mt-20 border-t border-white/10 bg-ink-900 py-14 md:py-20">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @include('partials.section-heading', ['eyebrow' => 'Hitung Dulu', 'title' => 'Simulasi Cicilan Mobil', 'subtitle' => 'Geser harga mobil dan uang muka untuk memperkirakan cicilan bulanan Anda.'])
            <div class="mt-8 grid gap-6 md:grid-cols-2" x-data="{ price: {{ (int) $medianPrice }}, rupiah: window.auto18.rupiah, setPrice(p) { this.price = p; this.$dispatch('sim-price', p) } }">
                <div class="a18-card p-5 sm:p-6">
                    <label for="sim-price" class="a18-label">Harga Mobil</label>
                    <p class="a18-display text-4xl leading-none text-white" x-text="rupiah(price)">{{ rupiah($medianPrice) }}</p>
                    <input id="sim-price" type="range" min="50000000" max="800000000" step="5000000" x-bind:value="price" x-on:input="setPrice(Number($event.target.value))" class="mt-4 h-2 w-full accent-brand-600">
                    <div class="mt-1 flex justify-between text-xs text-white/50"><span>Rp 50 jt</span><span>Rp 800 jt</span></div>

                    <div class="mt-5 border-t border-white/10 pt-4">
                        <p class="a18-label">Atau pilih dari stok</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($simulationCars as $c)
                                <button type="button" x-on:click="setPrice({{ (int) $c->price }})" class="a18-cond inline-flex min-h-[2.5rem] items-center gap-1.5 border border-white/15 px-3 text-sm font-bold uppercase tracking-wide text-white/80 transition-colors hover:border-brand-600 hover:text-white">
                                    <x-lucide-tag class="h-3.5 w-3.5 text-brand-600" aria-hidden="true" /> {{ $c->model }} &middot; {{ rupiah_short($c->price) }}
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
    <div id="tips" class="scroll-mt-20">@include('partials.tips-section')</div>

    {{-- CTA JUAL MOBIL --}}
    <section class="relative overflow-hidden bg-ink py-10 md:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden bg-brand-600">
                <div class="a18-stripes-white absolute inset-y-0 right-0 hidden w-1/3 opacity-[0.12] md:block" aria-hidden="true"></div>
                <div class="a18-skew absolute -right-10 inset-y-0 hidden w-40 bg-ink md:block" aria-hidden="true"></div>
                <div class="a18-skew absolute inset-y-0 right-24 hidden w-4 bg-white md:block" aria-hidden="true"></div>
                <div class="relative grid items-center gap-8 px-6 py-10 md:grid-cols-[1fr_auto] md:px-12 md:py-14 md:pr-60">
                    <div>
                        <span class="a18-eyebrow text-white/80">Jual / Tukar Tambah</span>
                        <h2 class="a18-display mt-3 text-[40px] leading-[0.9] text-white md:text-6xl">Mau Jual atau <span class="text-ink">Tukar Tambah</span> Mobil Anda?</h2>
                        <p class="mt-4 max-w-[560px] text-base leading-relaxed text-white/90">Dapatkan estimasi harga instan, jadwalkan inspeksi, dan terima pembayaran cepat setelah dokumen beres.</p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row md:flex-col">
                        <a href="{{ route('sell.create') }}" class="a18-cut-tag a18-display flex h-14 items-center justify-center gap-3 bg-ink px-7 text-[22px] text-white transition-colors hover:bg-ink-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">Jual Mobil Sekarang <x-lucide-arrow-right class="h-5 w-5" /></a>
                        <a href="{{ route('cars.index', ['favorit' => 1]) }}#daftar-mobil" class="a18-cut-tag a18-display flex h-14 items-center justify-center gap-3 bg-white px-7 text-[22px] text-ink transition-colors hover:bg-neutral-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-ink"><x-lucide-heart class="h-5 w-5" /> Lihat Favorit Saya</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.faq-section', ['title' => 'Pertanyaan Seputar Beli Mobil', 'faqs' => config('auto18.faqs_buy')])
@endsection
