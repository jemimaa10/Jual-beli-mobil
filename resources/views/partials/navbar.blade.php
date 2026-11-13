@php
    $publicTabs = [
        ['route' => 'cars.index', 'label' => 'Beli Mobil', 'match' => 'cars.*'],
        ['route' => 'sell.create', 'label' => 'Jual Mobil', 'match' => 'sell.*'],
    ];
    $adminTabs = [
        ['route' => 'showroom.dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'match' => 'showroom.dashboard'],
        ['route' => 'showroom.cars.index', 'label' => 'Stok Mobil', 'icon' => 'car-front', 'match' => 'showroom.cars.*'],
        ['route' => 'showroom.pos', 'label' => 'Kasir Aksesoris', 'icon' => 'shopping-bag', 'match' => 'showroom.pos*'],
        ['route' => 'showroom.products.index', 'label' => 'Produk & Stok', 'icon' => 'package', 'match' => 'showroom.products.*'],
        ['route' => 'showroom.suppliers.index', 'label' => 'Suplier', 'icon' => 'truck', 'match' => 'showroom.suppliers.*'],
        ['route' => 'showroom.reports', 'label' => 'Laporan', 'icon' => 'file-bar-chart', 'match' => 'showroom.reports*'],
    ];
    $tel = fn ($number) => 'tel:' . preg_replace('/[^\d+]/', '', (string) $number);
    $iconBtn = 'relative inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 sm:h-9 sm:w-9';
@endphp
<header class="sticky top-0 z-30 bg-black/95 backdrop-blur supports-[backdrop-filter]:bg-black/85">
    <div class="h-0.5 bg-gradient-to-r from-brand-900 via-brand-600 to-brand-900" aria-hidden="true"></div>

    {{-- Baris 1: logo, menu publik, aksi --}}
    <div class="border-b border-white/10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 items-center justify-between gap-2 sm:h-16 sm:gap-4">
                <div class="flex min-w-0 items-center gap-2 sm:gap-6">
                    <a href="{{ route('cars.index') }}" title="Beranda Auto18" aria-label="Beranda Auto18"
                       class="shrink-0 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        @include('partials.logo', ['size' => 'sm', 'showTagline' => false, 'class' => 'min-[400px]:hidden'])
                        @include('partials.logo', ['size' => 'sm', 'class' => 'hidden min-[400px]:inline-flex sm:hidden'])
                        @include('partials.logo', ['size' => 'md', 'class' => 'hidden sm:inline-flex'])
                    </a>

                    <nav class="flex items-stretch self-stretch" aria-label="Menu utama">
                        @foreach ($publicTabs as $tab)
                            @php
                                $active = request()->routeIs($tab['match']);
                                [$first, $rest] = array_pad(explode(' ', $tab['label'], 2), 2, '');
                            @endphp
                            <a href="{{ route($tab['route']) }}" @if ($active) aria-current="page" @endif
                               class="relative flex items-center whitespace-nowrap px-2.5 font-display text-xs font-black uppercase tracking-wide transition-colors focus:outline-none focus-visible:bg-white/5 focus-visible:text-white sm:px-4 sm:text-sm {{ $active ? 'text-white' : 'text-neutral-400 hover:text-white' }}">
                                <span>{{ $first }}</span>
                                @if ($rest !== '')<span class="hidden sm:inline">&nbsp;{{ $rest }}</span>@endif
                                @if ($active)<span class="absolute inset-x-1.5 bottom-0 h-1 rounded-t-sm bg-brand-600 sm:inset-x-3" aria-hidden="true"></span>@endif
                            </a>
                        @endforeach
                    </nav>
                </div>

                <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                    @if ($store->call_center)
                        <a href="{{ $tel($store->call_center) }}" title="Hubungi Call Center 24 Jam"
                           class="mr-1 hidden items-center gap-2.5 rounded-full border border-white/20 py-1 pl-1 pr-4 transition-colors hover:border-brand-500 hover:bg-white/5 lg:inline-flex">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-white"><x-lucide-phone class="h-3.5 w-3.5" /></span>
                            <span class="leading-tight">
                                <span class="block text-[10px] font-semibold uppercase tracking-wider text-neutral-400">Call Center 24 Jam</span>
                                <span class="block font-display text-sm font-black text-white">{{ $store->call_center }}</span>
                            </span>
                        </a>
                    @endif

                    {{-- Favorit disimpan di browser (localStorage), jadi badge diisi oleh Alpine --}}
                    <a href="{{ route('cars.index', ['favorit' => 1]) }}" title="Mobil Favorit" aria-label="Mobil favorit"
                       x-data
                       class="{{ $iconBtn }} text-neutral-300 hover:bg-white/10 hover:text-white">
                        <span x-bind:class="$store.favorites.count > 0 ? 'text-brand-500 [&_svg]:fill-brand-600' : ''"><x-lucide-heart class="h-5 w-5" /></span>
                        <span x-show="$store.favorites.count > 0" x-cloak x-text="$store.favorites.count > 99 ? '99+' : $store.favorites.count"
                              class="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-brand-600 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-black"></span>
                    </a>

                    @auth
                        @if (Route::has('showroom.settings'))
                            <a href="{{ route('showroom.settings') }}" title="Pengaturan Showroom" aria-label="Pengaturan Showroom"
                               class="{{ $iconBtn }} text-neutral-300 hover:bg-white/10 hover:text-white"><x-lucide-settings class="h-5 w-5" /></a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="Keluar" aria-label="Keluar"
                                    class="{{ $iconBtn }} text-neutral-500 hover:bg-brand-600/15 hover:text-brand-400"><x-lucide-log-out class="h-4 w-4" /></button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="a18-btn-ghost !px-2.5 !py-1.5 text-xs"><x-lucide-log-in class="h-4 w-4" /> <span class="hidden sm:inline">Login</span></a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    {{-- Baris 2: panel showroom, hanya untuk staf yang sudah login --}}
    @auth
        <div class="border-b border-white/10 bg-ink-800">
            <div class="mx-auto max-w-7xl">
                <nav aria-label="Panel Showroom" class="isolate flex items-center gap-2 overflow-x-auto px-4 py-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:px-6 lg:px-8">
                    <span class="flex shrink-0 items-center gap-2 pr-1 font-display text-[10px] font-black uppercase tracking-[0.2em] text-neutral-500 sm:pr-2">
                        <span class="h-3 w-1.5 -skew-x-12 bg-brand-600" aria-hidden="true"></span>
                        Panel Showroom
                    </span>
                    @foreach ($adminTabs as $tab)
                        @php $active = request()->routeIs($tab['match']); @endphp
                        <a href="{{ Route::has($tab['route']) ? route($tab['route']) : '#' }}" @if ($active) aria-current="page" @endif
                           class="relative inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1.5 text-xs font-bold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $active ? 'border-transparent bg-brand-600 text-white shadow-md shadow-brand-600/30' : 'border-white/10 text-neutral-300 hover:border-white/30 hover:bg-white/5 hover:text-white' }}">
                            @svg('lucide-' . $tab['icon'], 'h-3.5 w-3.5 ' . ($active ? 'text-white' : 'text-neutral-400'))
                            <span>{{ $tab['label'] }}</span>
                            @if (! empty($navBadges[$tab['route']] ?? 0))
                                <span class="flex h-[18px] min-w-[18px] items-center justify-center rounded-full px-1 text-[10px] font-black leading-none {{ $active ? 'bg-white text-brand-700' : 'bg-brand-600 text-white' }}">{{ $navBadges[$tab['route']] > 99 ? '99+' : $navBadges[$tab['route']] }}</span>
                            @endif
                        </a>
                    @endforeach
                    <span class="w-1 shrink-0 sm:hidden" aria-hidden="true"></span>
                </nav>
            </div>
        </div>
    @endauth
</header>
