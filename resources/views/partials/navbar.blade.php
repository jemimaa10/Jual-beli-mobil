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
    $iconBtn = 'relative inline-flex h-10 w-10 shrink-0 items-center justify-center transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500';
    $subLink = 'a18-cond relative flex h-11 flex-1 items-center justify-center text-[15px] font-bold uppercase tracking-wide focus:outline-none focus-visible:bg-white/5';
@endphp
<header class="sticky top-0 z-30 border-b border-white/10 bg-ink/95 backdrop-blur supports-[backdrop-filter]:bg-ink/90">
    <div class="a18-racing-strip" aria-hidden="true"></div>

    {{-- Baris 1: logo, menu publik, aksi --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center gap-3 md:h-[72px] md:gap-8">
            <a href="{{ route('cars.index') }}" title="Beranda Auto18" aria-label="Beranda Auto18"
               class="flex h-11 shrink-0 items-center focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                @include('partials.logo', ['size' => 'sm', 'showTagline' => false, 'class' => 'md:hidden'])
                @include('partials.logo', ['size' => 'md', 'showTagline' => false, 'class' => 'hidden md:inline-flex lg:hidden'])
                @include('partials.logo', ['size' => 'md', 'class' => 'hidden lg:inline-flex'])
            </a>

            <nav class="hidden items-stretch self-stretch md:flex" aria-label="Menu utama">
                @foreach ($publicTabs as $tab)
                    @php $active = request()->routeIs($tab['match']); @endphp
                    <a href="{{ route($tab['route']) }}" @if ($active) aria-current="page" @endif
                       class="a18-cond relative flex items-center whitespace-nowrap px-4 text-[17px] font-bold uppercase tracking-wide transition-colors focus:outline-none focus-visible:bg-white/5 {{ $active ? 'text-white' : 'text-white/55 hover:text-white' }}">
                        {{ $tab['label'] }}
                        @if ($active)<span class="a18-skew absolute inset-x-3 bottom-0 h-1 bg-brand-600" aria-hidden="true"></span>@endif
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
                @if ($store->call_center)
                    <a href="{{ $tel($store->call_center) }}" title="Hubungi Call Center 24 Jam" aria-label="Call Center 24 Jam {{ $store->call_center }}"
                       class="group mr-1 flex h-11 items-center focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                        <span class="a18-skew flex h-11 items-center bg-brand-600 px-3 transition-colors group-hover:bg-brand-500 md:px-4">
                            <span class="a18-unskew flex items-center gap-2.5">
                                <x-lucide-phone class="h-[18px] w-[18px]" aria-hidden="true" />
                                <span class="hidden leading-none min-[400px]:block">
                                    <span class="mb-0.5 hidden text-xs font-semibold uppercase tracking-wider text-white/80 lg:block">Call Center 24 Jam</span>
                                    <span class="a18-cond text-[17px] font-extrabold tracking-wide md:text-[19px]">{{ $store->call_center }}</span>
                                </span>
                            </span>
                        </span>
                    </a>
                @endif

                {{-- Favorit disimpan di browser (localStorage), jadi badge diisi oleh Alpine --}}
                <a href="{{ route('cars.index', ['favorit' => 1]) }}" title="Mobil Favorit" aria-label="Mobil favorit"
                   x-data
                   class="{{ $iconBtn }} text-neutral-300 hover:bg-white/10 hover:text-white">
                    <span x-bind:class="$store.favorites.count > 0 ? 'text-brand-500 [&_svg]:fill-brand-600' : ''"><x-lucide-heart class="h-5 w-5" /></span>
                    <span x-show="$store.favorites.count > 0" x-cloak x-text="$store.favorites.count > 99 ? '99+' : $store.favorites.count"
                          class="a18-cond absolute right-0 top-0 flex h-[18px] min-w-[18px] items-center justify-center bg-brand-600 px-1 text-xs font-bold leading-none text-white"></span>
                </a>

                @auth
                    @if (Route::has('showroom.settings'))
                        <a href="{{ route('showroom.settings') }}" title="Pengaturan Showroom" aria-label="Pengaturan Showroom"
                           class="{{ $iconBtn }} text-neutral-300 hover:bg-white/10 hover:text-white"><x-lucide-settings class="h-5 w-5" /></a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Keluar" aria-label="Keluar"
                                class="{{ $iconBtn }} text-neutral-400 hover:bg-brand-600/15 hover:text-brand-400"><x-lucide-log-out class="h-5 w-5" /></button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="a18-btn-skew hidden md:inline-flex"><span class="a18-unskew a18-cond text-base font-bold uppercase tracking-wide">Login</span></a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Ponsel: menu publik di baris sendiri agar tombolnya besar --}}
    <nav aria-label="Menu ponsel" class="flex border-t border-white/10 md:hidden">
        @foreach ($publicTabs as $i => $tab)
            @php $active = request()->routeIs($tab['match']); @endphp
            <a href="{{ route($tab['route']) }}" @if ($active) aria-current="page" @endif
               class="{{ $subLink }} {{ $i > 0 ? 'border-l border-white/10' : '' }} {{ $active ? 'text-white' : 'text-white/55' }}">
                {{ $tab['label'] }}
                @if ($active)<span class="a18-skew absolute inset-x-6 bottom-0 h-[3px] bg-brand-600" aria-hidden="true"></span>@endif
            </a>
        @endforeach
        @guest
            <a href="{{ route('login') }}" class="{{ $subLink }} border-l border-white/10 {{ request()->routeIs('login') ? 'text-white' : 'text-white/55' }}">Login</a>
        @endguest
    </nav>

    {{-- Baris 2: panel showroom, hanya untuk staf yang sudah login --}}
    @auth
        <div class="relative border-t border-white/10 bg-ink-800">
            <div class="mx-auto max-w-7xl">
                <nav aria-label="Panel Showroom" class="isolate flex items-center gap-2 overflow-x-auto px-4 py-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:px-6 lg:px-8">
                    <span class="a18-cond flex shrink-0 items-center gap-2 pr-1 text-xs font-bold uppercase tracking-[0.2em] text-neutral-500 sm:pr-2">
                        <span class="a18-skew h-3.5 w-1.5 bg-brand-600" aria-hidden="true"></span>Panel Showroom</span>
                    @foreach ($adminTabs as $tab)
                        @php $active = request()->routeIs($tab['match']); @endphp
                        <a href="{{ Route::has($tab['route']) ? route($tab['route']) : '#' }}" @if ($active) aria-current="page" @endif
                           class="a18-cond relative inline-flex h-10 shrink-0 items-center gap-1.5 whitespace-nowrap border px-3 text-[15px] font-bold uppercase tracking-wide transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $active ? 'border-brand-600 bg-brand-600 text-white' : 'border-white/10 text-neutral-300 hover:border-white/30 hover:bg-white/5 hover:text-white' }}">
                            @svg('lucide-' . $tab['icon'], 'h-4 w-4 ' . ($active ? 'text-white' : 'text-neutral-400'))
                            <span>{{ $tab['label'] }}</span>
                            @if (! empty($navBadges[$tab['route']] ?? 0))
                                <span class="flex h-[18px] min-w-[18px] items-center justify-center px-1 text-xs font-black leading-none {{ $active ? 'bg-white text-brand-700' : 'bg-brand-600 text-white' }}">{{ $navBadges[$tab['route']] > 99 ? '99+' : $navBadges[$tab['route']] }}</span>
                            @endif
                        </a>
                    @endforeach
                    <span class="w-6 shrink-0 sm:hidden" aria-hidden="true"></span>
                </nav>
            </div>
            {{-- Petunjuk geser di ponsel --}}
            <div class="pointer-events-none absolute inset-y-0 right-0 w-10 bg-gradient-to-l from-ink-800 to-transparent lg:hidden" aria-hidden="true"></div>
        </div>
    @endauth
</header>
