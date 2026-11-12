@php
    $buyLinks = ['Cari Mobil Bekas', 'Simulasi Kredit', 'Booking Test Drive', 'Mobil Favorit', 'Tips Membeli Mobil'];
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
<footer class="mt-12 bg-black text-neutral-300">
    <div class="h-1 bg-brand-600" aria-hidden="true"></div>
    <div class="h-2.5 opacity-90" style="background-image: repeating-linear-gradient(-55deg, #e41b1b 0 18px, #070707 18px 30px);" aria-hidden="true"></div>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-12 lg:gap-x-8">
            {{-- Brand --}}
            <div class="col-span-2 lg:col-span-3">
                <a href="{{ route('cars.index') }}" aria-label="Beranda Auto18" class="inline-block rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    @include('partials.logo', ['size' => 'lg'])
                </a>
                @if ($store->tagline)
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-neutral-400">{{ $store->tagline }}</p>
                @endif
                <ul class="mt-4 space-y-2 text-sm text-neutral-400">
                    @if ($store->phone)
                        <li class="flex items-start gap-2"><x-lucide-phone class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" /><a href="{{ $tel($store->phone) }}" class="hover:text-white">{{ $store->phone }}</a></li>
                    @endif
                    @if ($store->email)
                        <li class="flex items-start gap-2"><x-lucide-mail class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" /><a href="mailto:{{ $store->email }}" class="break-all hover:text-white">{{ $store->email }}</a></li>
                    @endif
                </ul>
                @include('partials.social-links', ['variant' => 'icon', 'class' => 'mt-5'])
            </div>

            {{-- Beli Mobil --}}
            <div class="col-span-1 lg:col-span-2">
                @include('partials.footer-heading', ['text' => 'Beli Mobil'])
                <ul class="space-y-2.5">
                    @foreach ($buyLinks as $label)
                        <li><a href="{{ route('cars.index') }}" class="group inline-flex items-start gap-1 text-left text-sm text-neutral-400 transition-colors hover:text-white focus:outline-none focus-visible:text-white"><x-lucide-chevron-right class="mt-[3px] h-3.5 w-3.5 shrink-0 text-brand-600 transition-transform group-hover:translate-x-0.5" /><span>{{ $label }}</span></a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Jual Mobil --}}
            <div class="col-span-1 lg:col-span-2">
                @include('partials.footer-heading', ['text' => 'Jual Mobil'])
                <ul class="space-y-2.5">
                    @foreach ($sellLinks as $label)
                        <li><a href="{{ route('sell.create') }}" class="group inline-flex items-start gap-1 text-left text-sm text-neutral-400 transition-colors hover:text-white focus:outline-none focus-visible:text-white"><x-lucide-chevron-right class="mt-[3px] h-3.5 w-3.5 shrink-0 text-brand-600 transition-transform group-hover:translate-x-0.5" /><span>{{ $label }}</span></a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Layanan darurat --}}
            <div class="col-span-2 sm:col-span-1 lg:col-span-3">
                @include('partials.footer-heading', ['text' => 'Layanan Darurat 24 Jam'])
                <p class="text-sm leading-relaxed text-neutral-400">{{ config('auto18.benefits_subtitle') }}. {{ count($benefits) }} benefit untuk setiap pembeli mobil Auto18.</p>

                @if ($store->call_center)
                    <a href="{{ $tel($store->call_center) }}" class="a18-pill mt-4 flex w-full max-w-xs items-center gap-3 px-3 py-2.5 transition-colors hover:border-brand-500 hover:bg-brand-600/10">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white"><x-lucide-phone class="h-5 w-5" /></span>
                        <span class="min-w-0 leading-tight">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-neutral-400">Call Center</span>
                            <span class="block truncate font-display text-xl font-black text-white">{{ $store->call_center }}</span>
                        </span>
                    </a>
                @endif

                @if ($store->support_partner)
                    <p class="mt-3 flex items-center gap-1.5 text-xs text-neutral-500">
                        <x-lucide-shield-check class="h-3.5 w-3.5 text-brand-500" />
                        <span>Supported by <span class="font-display font-black uppercase tracking-wide text-neutral-200">{{ $store->support_partner }}</span></span>
                    </p>
                @endif
            </div>

            {{-- Panel showroom --}}
            <div class="col-span-2 sm:col-span-1 lg:col-span-2">
                @include('partials.footer-heading', ['text' => 'Panel Showroom'])
                <ul class="grid grid-cols-2 gap-x-4 gap-y-2.5 sm:grid-cols-1">
                    @foreach ($adminLinks as [$label, $name])
                        <li><a href="{{ Route::has($name) ? route($name) : route('login') }}" class="group inline-flex items-start gap-1 text-left text-sm text-neutral-400 transition-colors hover:text-white focus:outline-none focus-visible:text-white"><x-lucide-chevron-right class="mt-[3px] h-3.5 w-3.5 shrink-0 text-brand-600 transition-transform group-hover:translate-x-0.5" /><span>{{ $label }}</span></a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-5 text-xs text-neutral-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p class="leading-relaxed">
                &copy; {{ date('Y') }} <span class="font-bold text-neutral-300">Auto18</span> &mdash; Auto Eighteen
                @if ($store->address)<span> &middot; {{ $store->address }}</span>@endif
                <br class="sm:hidden">
                <span class="block sm:inline"> &middot; Foto mobil:
                    <a href="https://commons.wikimedia.org" target="_blank" rel="noopener noreferrer" class="underline hover:text-white">Wikimedia Commons</a>, lisensi
                    <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener noreferrer" class="underline hover:text-white">CC BY-SA 4.0</a>
                </span>
            </p>
            <button type="button" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
                    class="inline-flex items-center gap-1.5 self-start rounded-full border border-white/15 px-3 py-1.5 font-semibold text-neutral-300 transition-colors hover:border-brand-500 hover:text-white sm:self-auto">
                <x-lucide-arrow-up class="h-3.5 w-3.5" /> Kembali ke Atas
            </button>
        </div>
    </div>
</footer>
