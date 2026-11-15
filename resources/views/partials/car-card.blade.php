{{-- Kartu mobil gaya Motorsport. Params: $car, $gridNo (opsional: nomor "grid start" 01, 02, … sesuai urutan dari server) --}}
@php
    $isSold = $car->status === 'sold';
    $isBooked = $car->status === 'booked';
    $monthly = \App\Services\CarPricing::installment($car->price, $store->min_dp_percent, 60, $store->credit_rate)['monthly'];
    $fresh = $isSold && $car->sold_at && $car->sold_at->gt(now()->subSeconds(30));
    $gridNo = $gridNo ?? null;
    $specs = [
        ['calendar', $car->year],
        ['gauge', km($car->mileage)],
        ['settings-2', $car->transmission],
        ['fuel', $car->fuel],
    ];
@endphp
{{-- Potongan sudut (clip-path) hanya pada lapisan latar & foto, bukan pada <article>, agar ring fokus & glow tidak terpotong --}}
<article class="group relative flex flex-col transition-shadow hover:shadow-[0_18px_40px_-18px_rgba(228,27,27,0.55)] focus-within:ring-2 focus-within:ring-brand-500 focus-within:ring-offset-2 focus-within:ring-offset-ink">
    <div class="a18-cut-card absolute inset-0 border border-white/[0.06] bg-ink-800 transition-colors group-hover:bg-ink-700" aria-hidden="true"></div>

    {{-- Foto --}}
    <div class="relative">
        <div class="a18-cut-card"><div class="a18-cut-img relative overflow-hidden">
            @include('partials.car-image', ['car' => $car, 'class' => 'aspect-[4/3] w-full', 'imgClass' => 'transition-transform duration-500 group-hover:scale-[1.04] ' . ($isSold || $isBooked ? 'grayscale opacity-60' : '')])
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-black/55 via-transparent to-transparent" aria-hidden="true"></div>
            @if ($isSold)
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    @include('partials.sold-stamp', ['size' => 'sm', 'animate' => $fresh])
                </div>
            @elseif ($isBooked)
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <span class="a18-display -rotate-6 bg-amber-400 px-6 py-2 text-2xl tracking-wider text-ink shadow-xl">Dipesan</span>
                </div>
            @endif
        </div></div>

        <div class="pointer-events-none absolute left-3 top-3 flex items-center gap-2">
            @if ($gridNo)
                <span class="a18-display text-[26px] leading-none text-white/90" data-grid-no="{{ str_pad($gridNo, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($gridNo, 2, '0', STR_PAD_LEFT) }}</span>
            @endif
            @if ($car->is_featured && ! $isSold)
                <span class="a18-skew bg-brand-600 px-2.5 py-1"><span class="a18-unskew a18-cond block text-xs font-bold uppercase tracking-wider text-white">Pilihan Auto18</span></span>
            @endif
        </div>

        <button type="button" x-data x-on:click.stop="$store.favorites.toggle({{ $car->id }})"
                x-bind:aria-pressed="$store.favorites.has({{ $car->id }})"
                x-bind:aria-label="$store.favorites.has({{ $car->id }}) ? 'Hapus dari favorit' : 'Simpan ke favorit'"
                x-bind:title="$store.favorites.has({{ $car->id }}) ? 'Hapus dari favorit' : 'Simpan ke favorit'"
                class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center border border-white/20 bg-black/60 text-white backdrop-blur transition-colors hover:border-brand-600 hover:bg-brand-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
            <span class="flex transition-transform duration-300" x-bind:class="$store.favorites.has({{ $car->id }}) ? 'scale-110 text-brand-500 [&_svg]:fill-brand-500' : ''"><x-lucide-heart class="h-[18px] w-[18px]" /></span>
        </button>
    </div>

    {{-- Info --}}
    <div class="relative -mt-6 flex flex-1 flex-col px-4 pb-4">
        <div class="flex items-end justify-between gap-3">
            @if ($car->inspection_score > 0)
                <span class="flex items-baseline gap-1.5 border border-white/10 bg-ink px-2.5 py-1.5" title="Skor inspeksi {{ $car->inspection_score }}/100">
                    <span class="a18-display text-[26px] leading-none {{ $car->inspection_score >= 95 ? 'text-brand-500' : 'text-white' }}">{{ $car->inspection_score }}</span>
                    <span class="a18-cond text-xs font-bold uppercase leading-none tracking-wider text-white/55">Skor<br>inspeksi</span>
                </span>
            @else
                <span></span>
            @endif
            <span class="a18-cond pb-1 text-xs font-semibold uppercase tracking-widest text-white/50">{{ $car->body_type }}</span>
        </div>

        <h3 class="a18-display mt-3 text-[26px] leading-[0.95] text-white">
            <a href="{{ route('cars.show', $car) }}" class="after:absolute after:inset-0 after:z-[1] focus:outline-none" aria-label="Lihat detail {{ car_name($car, true) }}">{{ $car->brand }} {{ $car->model }}</a>
        </h3>
        <p class="mt-0.5 truncate text-sm text-white/60">{{ $car->variant }}</p>

        <div class="mt-3 grid grid-cols-2 gap-x-3 gap-y-1.5 text-[13px] text-white/75">
            @foreach ($specs as [$icon, $label])
                <span class="flex min-w-0 items-center gap-1.5">@svg('lucide-' . $icon, 'h-4 w-4 shrink-0 text-white/45') <span class="truncate">{{ $label }}</span></span>
            @endforeach
            <span class="col-span-2 flex min-w-0 items-center gap-1.5 text-white/55"><x-lucide-map-pin class="h-4 w-4 shrink-0 text-white/45" aria-hidden="true" /> <span class="truncate">{{ $car->location }}</span></span>
        </div>

        <div class="-mx-4 -mb-4 mt-auto flex items-stretch border-t border-white/10 pt-0">
            <div class="min-w-0 flex-1 px-4 py-3">
                <p class="a18-display truncate text-2xl leading-none {{ $isSold ? 'text-white/50 line-through decoration-2' : 'text-white' }}">{{ rupiah($car->price) }}</p>
                @if (! $isSold)
                    <p class="mt-1 text-[13px] text-white/70">Cicilan mulai <b class="font-semibold text-white">{{ rupiah_short($monthly) }}</b>/bln</p>
                @elseif ($car->sold_price > 0)
                    <p class="mt-1 text-[13px] text-white/55">Terjual {{ rupiah($car->sold_price) }}</p>
                @endif
            </div>
            <div class="a18-cut-tag -mr-px flex w-12 shrink-0 items-center justify-center transition-colors {{ $isSold ? 'bg-ink-600' : 'bg-brand-600 group-hover:bg-brand-500' }}" aria-hidden="true">
                <x-lucide-arrow-right class="h-5 w-5 text-white" />
            </div>
        </div>
    </div>
</article>
