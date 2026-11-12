{{-- Tips membeli mobil second, meniru poster "TIPS MEMBELI MOBIL SECOND" --}}
<section class="bg-ink-900 py-14 sm:py-16">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        @include('partials.section-heading', ['eyebrow' => 'Panduan Auto18', 'title' => 'Tips Membeli Mobil Second', 'subtitle' => 'Lima hal yang wajib dicek sebelum memutuskan membeli mobil bekas.'])
        <ol class="mt-10">
            @foreach (config('auto18.buying_tips') as $i => $tip)
                <li class="flex items-start gap-4 py-5 sm:gap-6 {{ $i === 0 ? '' : 'border-t border-white/15' }}">
                    <span class="a18-number shrink-0 text-3xl text-brand-500 sm:text-5xl">{{ $tip['no'] }}</span>
                    <div class="min-w-0">
                        <h3 class="font-display text-base font-black uppercase leading-snug tracking-wide text-white sm:text-lg">{{ $tip['title'] }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-neutral-400">{{ $tip['description'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
