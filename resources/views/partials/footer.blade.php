@php
    $buyLinks = ['Cari Mobil Bekas' => route('cars.index') . '#daftar-mobil', 'Simulasi Kredit' => route('cars.index') . '#simulasi', 'Booking Test Drive' => route('cars.index') . '#daftar-mobil', 'Mobil Favorit' => route('cars.index', ['favorit' => 1]) . '#daftar-mobil', 'Tips Membeli Mobil' => route('cars.index') . '#tips'];
    $sellLinks = ['Jual Langsung', 'Tukar Tambah', 'Estimasi Harga Instan', 'Jadwalkan Inspeksi'];
    $adminLinks = [
        ['Dashboard', 'showroom.dashboard'],
        ['Stok Mobil', 'showroom.cars.index'],
        ['Kasir Aksesoris', 'showroom.pos'],
        ['Produk & Stok', 'showroom.products.index'],
        ['Suplier', 'showroom.suppliers.index'],
        ['Laporan', 'showroom.reports'],
    ];
    $tel = fn ($number) => 'tel:' . preg_replace('/[^\d+]/', '', (string) $number);
    $benefits = config('auto18.benefits');
@endphp
<footer class="mt-12 border-t border-white/10 bg-ink text-neutral-300">
    <div class="a18-checker h-3 opacity-80" aria-hidden="true"></div>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-12 lg:gap-x-8">
            {{-- Brand --}}
            <div class="col-span-2 lg:col-span-3">
                <a href="{{ route('cars.index') }}" aria-label="Beranda Auto18" class="inline-block focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    @include('partials.logo', ['size' => 'lg'])
                </a>
                @if ($store->tagline)
                    <p class="mt-4 max-w-xs text-[15px] leading-relaxed text-white/60">{{ $store->tagline }}</p>
                @endif
                <ul class="mt-3 space-y-1 text-[15px] text-white/60">
                    @if ($store->phone)
                        <li class="flex items-center gap-2"><x-lucide-phone class="h-4 w-4 shrink-0 text-brand-600" /><a href="{{ $tel($store->phone) }}" class="inline-flex min-h-[2.5rem] items-center hover:text-white">{{ $store->phone }}</a></li>
                    @endif
                    @if ($store->email)
                        <li class="flex items-center gap-2"><x-lucide-mail class="h-4 w-4 shrink-0 text-brand-600" /><a href="mailto:{{ $store->email }}" class="inline-flex min-h-[2.5rem] items-center break-all hover:text-white">{{ $store->email }}</a></li>
                    @endif
                </ul>
                @include('partials.social-links', ['variant' => 'icon', 'class' => 'mt-5'])
            </div>

            {{-- Beli Mobil --}}
            <div class="col-span-1 lg:col-span-2">
                @include('partials.footer-heading', ['text' => 'Beli Mobil'])
                <ul>
                    @foreach ($buyLinks as $label => $href)
                        <li><a href="{{ $href }}" class="group inline-flex min-h-[2.5rem] items-center gap-2 text-left text-[15px] text-white/70 transition-colors hover:text-white focus:outline-none focus-visible:text-white"><span class="a18-skew h-3 w-1 shrink-0 bg-brand-600 transition-transform group-hover:translate-x-0.5" aria-hidden="true"></span><span>{{ $label }}</span></a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Jual Mobil --}}
            <div class="col-span-1 lg:col-span-2">
                @include('partials.footer-heading', ['text' => 'Jual Mobil'])
                <ul>
                    @foreach ($sellLinks as $label)
                        <li><a href="{{ route('sell.create') }}" class="group inline-flex min-h-[2.5rem] items-center gap-2 text-left text-[15px] text-white/70 transition-colors hover:text-white focus:outline-none focus-visible:text-white"><span class="a18-skew h-3 w-1 shrink-0 bg-brand-600 transition-transform group-hover:translate-x-0.5" aria-hidden="true"></span><span>{{ $label }}</span></a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Layanan darurat --}}
            <div class="col-span-2 sm:col-span-1 lg:col-span-3">
                @include('partials.footer-heading', ['text' => 'Layanan Darurat 24 Jam'])
                <p class="text-[15px] leading-relaxed text-white/60">{{ config('auto18.benefits_subtitle') }}. {{ count($benefits) }} benefit untuk setiap pembeli mobil Auto18.</p>

                @if ($store->call_center)
                    <a href="{{ $tel($store->call_center) }}" class="group mt-4 block w-full max-w-xs focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        <span class="a18-cond block text-xs font-bold uppercase tracking-[0.2em] text-white/50">Call Center 24 Jam</span>
                        <span class="a18-display mt-1 block truncate text-[34px] leading-none text-white transition-colors group-hover:text-brand-500">{{ $store->call_center }}</span>
                    </a>
                @endif

                @if ($store->support_partner)
                    <p class="mt-3 flex items-center gap-1.5 text-[13px] text-white/50">
                        <x-lucide-shield-check class="h-4 w-4 text-brand-600" />
                        <span>Supported by <span class="a18-cond font-bold uppercase tracking-wide text-white/85">{{ $store->support_partner }}</span></span>
                    </p>
                @endif
            </div>

            {{-- Pintasan staf (judul "Panel Showroom" hanya dipakai di header staf) --}}
            <div class="col-span-2 sm:col-span-1 lg:col-span-2">
                @include('partials.footer-heading', ['text' => 'Untuk Staf'])
                <ul class="grid grid-cols-2 gap-x-4 sm:grid-cols-1">
                    @foreach ($adminLinks as [$label, $name])
                        <li><a href="{{ Route::has($name) ? route($name) : route('login') }}" class="group inline-flex min-h-[2.5rem] items-center gap-2 text-left text-[15px] text-white/70 transition-colors hover:text-white focus:outline-none focus-visible:text-white"><span class="a18-skew h-3 w-1 shrink-0 bg-brand-600 transition-transform group-hover:translate-x-0.5" aria-hidden="true"></span><span>{{ $label }}</span></a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-5 text-[13px] text-white/45 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p class="leading-relaxed">
                &copy; {{ date('Y') }} <span class="font-bold text-white/80">Auto18</span> &mdash; Auto Eighteen
                @if ($store->address)<span> &middot; {{ $store->address }}</span>@endif
                <br class="sm:hidden">
                <span class="block sm:inline"> &middot; Foto mobil:
                    <a href="https://commons.wikimedia.org" target="_blank" rel="noopener noreferrer" class="underline hover:text-white">Wikimedia Commons</a>, lisensi
                    <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener noreferrer" class="underline hover:text-white">CC BY-SA 4.0</a>
                </span>
            </p>
            <button type="button" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
                    class="a18-cond inline-flex min-h-[2.5rem] items-center gap-1.5 self-start border border-white/15 px-3 text-sm font-bold uppercase tracking-wide text-white/75 transition-colors hover:border-white/60 hover:text-white sm:self-auto">
                <x-lucide-arrow-up class="h-3.5 w-3.5" /> Kembali ke Atas
            </button>
        </div>
    </div>
</footer>
