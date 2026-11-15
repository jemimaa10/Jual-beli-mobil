@php
    $variant = $variant ?? 'pill';
    $links = array_values(array_filter([
        $store->instagram ? ['label' => '@' . $store->instagram, 'title' => 'Instagram Auto18', 'href' => 'https://www.instagram.com/' . $store->instagram, 'icon' => 'instagram'] : null,
        $store->tiktok ? ['label' => '@' . $store->tiktok, 'title' => 'TikTok Auto18', 'href' => 'https://www.tiktok.com/@' . $store->tiktok, 'icon' => 'music-2'] : null,
        $store->youtube ? ['label' => $store->youtube, 'title' => 'YouTube Auto18', 'href' => 'https://www.youtube.com/results?search_query=' . urlencode($store->youtube), 'icon' => 'youtube'] : null,
    ]));
@endphp
@if (count($links))
    @if ($variant === 'icon')
        <div class="flex items-center gap-2 {{ $class ?? '' }}">
            @foreach ($links as $l)
                <a href="{{ $l['href'] }}" target="_blank" rel="noopener noreferrer" title="{{ $l['title'] }}" aria-label="{{ $l['title'] }}"
                   class="flex h-10 w-10 items-center justify-center border border-white/15 text-neutral-300 transition-colors hover:border-brand-600 hover:bg-brand-600 hover:text-white">
                    @svg('lucide-' . $l['icon'], 'h-4 w-4')
                </a>
            @endforeach
        </div>
    @else
        <div class="flex flex-wrap items-center gap-2 {{ $class ?? '' }}">
            @foreach ($links as $l)
                <a href="{{ $l['href'] }}" target="_blank" rel="noopener noreferrer" title="{{ $l['title'] }}"
                   class="a18-cond inline-flex min-h-[2.5rem] items-center gap-2 border border-white/20 bg-ink/60 px-3 text-sm font-bold uppercase tracking-wide text-neutral-200 transition-colors hover:border-white/60 hover:text-white">
                    @svg('lucide-' . $l['icon'], 'h-4 w-4 text-brand-600')
                    {{ $l['label'] }}
                </a>
            @endforeach
        </div>
    @endif
@endif
