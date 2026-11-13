{{-- Foto mobil; bila belum ada foto, tampil blok warna bodi dengan ikon mobil. Params: $car, $class, $imgClass --}}
@php
    $src = $car->image_src;
    $hex = $car->color_hex ?: (collect(config('auto18.colors'))->firstWhere('name', $car->color)['hex'] ?? '#c3c7cc');
@endphp
<div class="relative overflow-hidden bg-ink-900 {{ $class ?? '' }}">
    @if ($src)
        <img src="{{ $src }}" alt="{{ car_name($car, true) }}" loading="lazy" decoding="async"
             class="absolute inset-0 h-full w-full object-cover {{ $imgClass ?? '' }}">
    @else
        <div class="absolute inset-0 flex items-center justify-center" style="background: radial-gradient(ellipse at 50% 40%, {{ $hex }}55 0%, #0b0b0b 70%);" role="img" aria-label="Ilustrasi {{ $car->brand }} {{ $car->model }}">
            <x-lucide-car-front class="h-16 w-16 opacity-80" style="color: {{ $hex }}" />
        </div>
    @endif
</div>
