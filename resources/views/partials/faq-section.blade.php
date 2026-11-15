{{-- Akordeon FAQ. Params: $title, $faqs --}}
<section class="border-t border-white/10 bg-ink-900 py-14 md:py-20">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8" x-data="{ open: 0 }">
        @include('partials.section-heading', ['eyebrow' => 'FAQ', 'title' => $title, 'align' => 'left'])
        <div class="mt-8 border-t border-white/10">
            @foreach ($faqs as $i => $faq)
                <div class="border-b border-white/10">
                    <h3>
                        <button type="button" x-on:click="open = open === {{ $i }} ? -1 : {{ $i }}" x-bind:aria-expanded="open === {{ $i }}" aria-controls="faq-panel-{{ $i }}"
                                class="flex min-h-[3.5rem] w-full items-center justify-between gap-4 py-4 text-left transition-colors hover:text-brand-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                            <span class="a18-cond text-lg font-bold uppercase leading-tight tracking-wide text-white">{{ $faq['q'] }}</span>
                            <span class="a18-cut-tag flex h-8 w-10 shrink-0 items-center justify-center" x-bind:class="open === {{ $i }} ? 'bg-brand-600 text-white' : 'bg-white/10 text-white/80'">
                                <x-lucide-minus class="h-4 w-4" x-show="open === {{ $i }}" /><x-lucide-plus class="h-4 w-4" x-show="open !== {{ $i }}" />
                            </span>
                        </button>
                    </h3>
                    <div id="faq-panel-{{ $i }}" role="region" x-show="open === {{ $i }}" @if ($i !== 0) x-cloak @endif class="pb-5 pr-12 text-[15px] leading-relaxed text-white/65">{{ $faq['a'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>
