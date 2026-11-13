{{-- Benefit layanan darurat 24 jam, meniru poster "BENEFIT BELI MOBIL DI AUTO 18" --}}
<section class="a18-stripes relative overflow-hidden py-14 sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="a18-heading text-2xl leading-tight sm:text-4xl">Benefit <span class="text-brand-500">Beli Mobil</span> di Auto 18</h2>
            <p class="a18-pill mx-auto mt-4 inline-flex items-center gap-2 px-4 py-2 text-xs font-bold uppercase tracking-wide sm:text-sm">
                <span class="text-brand-500" aria-hidden="true">///</span> {{ config('auto18.benefits_subtitle') }} <span class="text-brand-500" aria-hidden="true">///</span>
            </p>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach (config('auto18.benefits') as $benefit)
                <div class="flex items-center gap-3">
                    <span class="a18-number w-10 shrink-0 text-right text-3xl sm:text-4xl">{{ $benefit['no'] }}</span>
                    <div class="a18-pill flex min-w-0 flex-1 items-center gap-3 px-4 py-3">
                        @svg('lucide-' . \Illuminate\Support\Str::kebab($benefit['icon']), 'h-5 w-5 shrink-0 text-brand-500')
                        <span class="font-display text-sm font-bold uppercase leading-tight tracking-wide">{{ $benefit['title'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-white/15 pt-6 sm:flex-row">
            @if ($store->support_partner)
                <p class="text-center text-xs uppercase tracking-wide text-neutral-400 sm:text-left">Supported by <span class="font-display text-base font-black tracking-wide text-white">{{ $store->support_partner }}</span></p>
            @endif
            @if ($store->call_center)
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $store->call_center) }}" class="flex items-center gap-3 rounded-2xl bg-brand-600 px-5 py-3 transition-colors hover:bg-brand-500">
                    <x-lucide-phone class="h-5 w-5 text-white" aria-hidden="true" />
                    <span class="leading-tight text-white">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-white/80">Call Center 24 Jam</span>
                        <span class="block font-display text-lg font-black">{{ $store->call_center }}</span>
                    </span>
                </a>
            @endif
        </div>
    </div>
</section>
