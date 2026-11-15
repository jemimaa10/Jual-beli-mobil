{{-- Langkah-langkah proses. Params: $title, $subtitle, $steps --}}
<section class="bg-ink py-14 md:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @include('partials.section-heading', ['eyebrow' => 'Prosesnya Mudah', 'title' => $title, 'subtitle' => $subtitle, 'align' => 'left'])
        <ol class="mt-10 grid grid-cols-1 gap-px border border-white/10 bg-white/10 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($steps as $step)
                @php $last = $loop->last; @endphp
                <li class="relative overflow-hidden p-6 {{ $last ? 'bg-brand-600' : 'bg-ink-900' }}">
                    <span class="a18-display absolute right-3 top-1 text-[96px] leading-none {{ $last ? 'text-white/15' : 'a18-outline-num' }}" aria-hidden="true">{{ str_pad($step['no'], 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="relative">@svg('lucide-' . \Illuminate\Support\Str::kebab($step['icon']), 'h-8 w-8 ' . ($last ? 'text-white' : 'text-brand-600'))</span>
                    <h3 class="a18-display relative mt-10 text-[26px] leading-none text-white"><span class="sr-only">Langkah {{ $step['no'] }}: </span>{{ $step['title'] }}</h3>
                    <p class="relative mt-3 text-[15px] leading-relaxed {{ $last ? 'text-white/90' : 'text-white/65' }}">{{ $step['description'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
