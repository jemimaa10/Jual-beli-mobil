{{-- Benefit layanan darurat 24 jam (poster "BENEFIT BELI MOBIL DI AUTO 18") --}}
<section class="relative overflow-hidden border-t border-white/10 bg-ink-900">
    <div class="a18-checker absolute inset-x-0 top-0 h-3 opacity-80" aria-hidden="true"></div>
    <div class="mx-auto max-w-7xl px-4 pb-14 pt-16 sm:px-6 md:pb-20 md:pt-20 lg:px-8">
        <div class="grid items-end gap-6 lg:grid-cols-[1fr_auto]">
            <div>
                <span class="a18-eyebrow text-brand-500">{{ config('auto18.benefits_subtitle') }}</span>
                <h2 class="a18-heading mt-3 text-[40px] leading-[0.92] md:text-[56px]">Benefit <span class="text-brand-600">Beli Mobil</span> di Auto 18</h2>
            </div>
            @if ($store->call_center)
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $store->call_center) }}" class="group flex h-14 items-center self-start focus:outline-none focus-visible:ring-2 focus-visible:ring-white lg:self-end">
                    <span class="a18-skew flex h-14 items-center bg-brand-600 px-5 transition-colors group-hover:bg-brand-500">
                        <span class="a18-unskew flex items-center gap-3 text-white">
                            <x-lucide-phone class="h-5 w-5" aria-hidden="true" />
                            <span class="leading-none">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-wider text-white/80">Call Center 24 Jam</span>
                                <span class="a18-display block text-2xl">{{ $store->call_center }}</span>
                            </span>
                        </span>
                    </span>
                </a>
            @endif
        </div>

        <ol class="mt-10 grid grid-cols-1 gap-x-8 border-t border-white/10 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (config('auto18.benefits') as $benefit)
                <li class="flex min-h-[3.5rem] items-center gap-4 border-b border-white/10 py-2">
                    <span class="a18-display w-8 shrink-0 text-[22px] text-brand-600">{{ str_pad($benefit['no'], 2, '0', STR_PAD_LEFT) }}</span>
                    @svg('lucide-' . \Illuminate\Support\Str::kebab($benefit['icon']), 'h-5 w-5 shrink-0 text-white/45')
                    <span class="a18-cond text-[17px] font-bold uppercase leading-tight tracking-wide text-white">{{ $benefit['title'] }}</span>
                </li>
            @endforeach
        </ol>

        @if ($store->support_partner)
            <p class="mt-6 flex items-center gap-2 text-[13px] uppercase tracking-wide text-white/50">
                <x-lucide-shield-check class="h-4 w-4 text-brand-600" aria-hidden="true" />
                Supported by <span class="a18-display text-lg tracking-wide text-white">{{ $store->support_partner }}</span>
            </p>
        @endif
    </div>
</section>
