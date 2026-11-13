@php
    $isSold = $car->status === 'sold';
    $monthly = \App\Services\CarPricing::installment($car->price, $store->min_dp_percent, 60, $store->credit_rate)['monthly'];
    $fresh = $isSold && $car->sold_at && $car->sold_at->gt(now()->subSeconds(30));
    $specs = [
        ['calendar', $car->year],
        ['gauge', km($car->mileage)],
        ['settings-2', $car->transmission],
        ['fuel', $car->fuel],
    ];
@endphp
<article class="group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-ink-800 transition-all hover:border-brand-600 hover:shadow-lg hover:shadow-brand-900/30 focus-within:ring-2 focus-within:ring-brand-500">
    {{-- Foto --}}
    <div class="relative">
        <div class="relative overflow-hidden">
            @include('partials.car-image', ['car' => $car, 'class' => 'aspect-[16/10] w-full', 'imgClass' => 'transition-transform duration-500 group-hover:scale-105 ' . ($isSold ? 'grayscale' : '')])
            @if ($isSold)
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    @include('partials.sold-stamp', ['size' => 'sm', 'animate' => $fresh])
                </div>
            @endif
        </div>

        <div class="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between gap-2 p-3">
            <div class="flex flex-wrap gap-1.5">
                @if ($car->is_featured && ! $isSold)
                    <span class="rounded-full bg-brand-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-white">Pilihan Auto18</span>
                @endif
                @if ($car->status === 'booked')
                    <span class="rounded-full border border-amber-500/30 bg-amber-500/15 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-300">Dipesan</span>
                @endif
            </div>
        </div>

        <button type="button" x-data x-on:click.stop="$store.favorites.toggle({{ $car->id }})"
                x-bind:aria-pressed="$store.favorites.has({{ $car->id }})"
                x-bind:aria-label="$store.favorites.has({{ $car->id }}) ? 'Hapus dari favorit' : 'Simpan ke favorit'"
                x-bind:title="$store.favorites.has({{ $car->id }}) ? 'Hapus dari favorit' : 'Simpan ke favorit'"
                class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full border border-white/20 bg-black/60 text-white backdrop-blur transition-colors hover:border-brand-500 hover:bg-brand-600">
            <span class="flex transition-transform duration-300" x-bind:class="$store.favorites.has({{ $car->id }}) ? 'scale-110 text-brand-500 [&_svg]:fill-brand-500' : ''"><x-lucide-heart class="h-4 w-4" /></span>
        </button>

        @if ($car->inspection_score > 0)
            <span class="absolute bottom-3 left-3 inline-flex items-center gap-1 rounded-full bg-black/75 px-2.5 py-1 text-[10px] font-bold text-white backdrop-blur">
                <x-lucide-badge-check class="h-3.5 w-3.5 text-brand-500" aria-hidden="true" />
                Inspeksi {{ $car->inspection_score }}/100
            </span>
        @endif
    </div>

    {{-- Info --}}
    <div class="flex flex-1 flex-col p-4 {{ $isSold ? 'opacity-70' : '' }}">
        <h3 class="font-display text-base font-black uppercase leading-tight tracking-wide text-white">
            <a href="{{ route('cars.show', $car) }}" class="after:absolute after:inset-0 after:z-[1] focus:outline-none" aria-label="Lihat detail {{ car_name($car, true) }}">{{ $car->brand }} {{ $car->model }}</a>
        </h3>
        <p class="mt-0.5 truncate text-xs text-neutral-400">{{ $car->variant }} &middot; {{ $car->year }}</p>

        <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1.5 text-[11px] text-neutral-400">
            @foreach ($specs as [$icon, $label])
                <span class="inline-flex items-center gap-1">@svg('lucide-' . $icon, 'h-3.5 w-3.5 text-neutral-500') {{ $label }}</span>
            @endforeach
        </div>

        <p class="mt-2 inline-flex items-center gap-1 text-[11px] text-neutral-500"><x-lucide-map-pin class="h-3.5 w-3.5" aria-hidden="true" /> {{ $car->location }}</p>

        <div class="mt-auto border-t border-white/10 pt-3">
            <p class="font-display text-xl font-black text-white">{{ rupiah($car->price) }}</p>
            @if (! $isSold)
                <p class="mt-0.5 text-xs text-brand-400">Cicilan mulai {{ rupiah_short($monthly) }}/bln</p>
            @elseif ($car->sold_price > 0)
                <p class="mt-0.5 text-xs text-neutral-500">Terjual {{ rupiah($car->sold_price) }}</p>
            @endif
        </div>
    </div>
</article>
