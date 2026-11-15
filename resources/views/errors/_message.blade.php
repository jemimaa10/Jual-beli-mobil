{{-- Isi halaman error ber-layout. Params: $code, $title, $text --}}
<section class="a18-stripes relative overflow-hidden">
    <div class="relative mx-auto flex max-w-3xl flex-col items-start px-4 py-20 sm:px-6 sm:py-28">
        <span class="a18-eyebrow"><span class="a18-stripes-red h-3 w-10" aria-hidden="true"></span>Error {{ $code }}</span>
        <p class="a18-display a18-outline-num mt-2 text-[120px] leading-[0.85] sm:text-[180px]" aria-hidden="true">{{ $code }}</p>
        <h1 class="a18-heading -mt-4 text-4xl leading-none sm:-mt-8 sm:text-6xl">{{ $title }}</h1>
        <p class="mt-4 max-w-md text-base leading-relaxed text-white/65">{{ $text }}</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('cars.index') }}" class="a18-btn-primary">Ke Beranda <x-lucide-arrow-right class="h-4 w-4" /></a>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('sell.create') }}" class="a18-btn-outline">
                {{ url()->previous() !== url()->current() ? 'Kembali' : 'Jual Mobil' }}
            </a>
        </div>
    </div>
</section>
