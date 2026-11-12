{{-- Akordeon FAQ. Params: $title, $faqs --}}
<section class="bg-ink-900 py-14 sm:py-16">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8" x-data="{ open: 0 }">
        @include('partials.section-heading', ['eyebrow' => 'FAQ', 'title' => $title])
        <div class="mt-8 space-y-3">
            @foreach ($faqs as $i => $faq)
                <div class="a18-card overflow-hidden">
                    <h3>
                        <button type="button" x-on:click="open = open === {{ $i }} ? -1 : {{ $i }}" x-bind:aria-expanded="open === {{ $i }}" aria-controls="faq-panel-{{ $i }}"
                                class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-white/5">
                            <span class="text-sm font-bold text-white sm:text-base">{{ $faq['q'] }}</span>
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full" x-bind:class="open === {{ $i }} ? 'bg-brand-600 text-white' : 'bg-white/10 text-neutral-300'">
                                <x-lucide-minus class="h-4 w-4" x-show="open === {{ $i }}" /><x-lucide-plus class="h-4 w-4" x-show="open !== {{ $i }}" />
                            </span>
                        </button>
                    </h3>
                    <div id="faq-panel-{{ $i }}" role="region" x-show="open === {{ $i }}" @if ($i !== 0) x-cloak @endif class="border-t border-white/10 px-5 py-4 text-sm leading-relaxed text-neutral-400">{{ $faq['a'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>
