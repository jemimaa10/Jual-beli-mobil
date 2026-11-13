{{-- Isi halaman error ber-layout. Params: $code, $title, $text --}}
<section class="a18-stripes-soft">
    <div class="mx-auto flex max-w-2xl flex-col items-center px-4 py-20 text-center sm:py-28">
        <p class="font-display text-7xl font-black italic text-brand-600 sm:text-8xl">{{ $code }}</p>
        <h1 class="a18-heading mt-4 text-2xl sm:text-3xl">{{ $title }}</h1>
        <p class="mt-3 max-w-md text-sm leading-relaxed text-neutral-400 sm:text-base">{{ $text }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('cars.index') }}" class="a18-btn-primary px-6 py-3">Ke Beranda</a>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('sell.create') }}" class="a18-btn-outline px-6 py-3">
                {{ url()->previous() !== url()->current() ? 'Kembali' : 'Jual Mobil' }}
            </a>
        </div>
    </div>
</section>
