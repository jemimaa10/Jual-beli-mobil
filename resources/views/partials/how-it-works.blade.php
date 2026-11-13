{{-- Langkah-langkah proses. Params: $title, $subtitle, $steps --}}
<section class="py-14 sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @include('partials.section-heading', ['eyebrow' => 'Prosesnya Mudah', 'title' => $title, 'subtitle' => $subtitle])
        <div class="relative mt-10 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="pointer-events-none absolute left-0 right-0 top-12 hidden h-px bg-gradient-to-r from-transparent via-brand-600/50 to-transparent lg:block" aria-hidden="true"></div>
            @foreach ($steps as $step)
                <div class="a18-card relative z-10 p-5">
                    <div class="flex items-center justify-between">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-600 text-white">@svg('lucide-' . \Illuminate\Support\Str::kebab($step['icon']), 'h-5 w-5')</span>
                        <span class="font-display text-4xl font-black leading-none text-white/10">{{ str_pad($step['no'], 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <h3 class="mt-4 font-display text-base font-black uppercase tracking-wide text-white">{{ $step['title'] }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-neutral-400">{{ $step['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
