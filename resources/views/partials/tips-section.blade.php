{{-- Tips membeli mobil second, meniru poster "TIPS MEMBELI MOBIL SECOND" --}}
<section class="border-t border-white/10 bg-ink py-14 md:py-20">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        @include('partials.section-heading', ['eyebrow' => 'Panduan Auto18', 'title' => 'Tips Membeli Mobil Second', 'subtitle' => 'Lima hal yang wajib dicek sebelum memutuskan membeli mobil bekas.', 'align' => 'left'])
        <ol class="mt-10 border-t border-white/10">
            @foreach (config('auto18.buying_tips') as $i => $tip)
                <li class="flex items-start gap-4 border-b border-white/10 py-6 sm:gap-8">
                    <span class="a18-display a18-outline-num w-16 shrink-0 text-6xl leading-none sm:w-24 sm:text-[80px]">{{ str_pad($tip['no'], 2, '0', STR_PAD_LEFT) }}</span>
                    <div class="min-w-0">
                        <h3 class="a18-display text-2xl leading-none text-white sm:text-[28px]">{{ $tip['title'] }}</h3>
                        <p class="mt-2 text-[15px] leading-relaxed text-white/65">{{ $tip['description'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
