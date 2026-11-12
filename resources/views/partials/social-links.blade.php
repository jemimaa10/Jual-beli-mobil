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
                   class="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 text-neutral-300 transition-colors hover:border-brand-500 hover:bg-brand-600 hover:text-white">
                    @svg('lucide-' . $l['icon'], 'h-4 w-4')
                </a>
            @endforeach
        </div>
    @else
        <div class="flex flex-wrap items-center gap-2 {{ $class ?? '' }}">
            @foreach ($links as $l)
                <a href="{{ $l['href'] }}" target="_blank" rel="noopener noreferrer" title="{{ $l['title'] }}"
                   class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-black/40 px-3 py-1.5 text-xs font-semibold text-neutral-200 transition-colors hover:border-brand-500 hover:text-white">
                    @svg('lucide-' . $l['icon'], 'h-3.5 w-3.5 text-brand-500')
                    {{ $l['label'] }}
                </a>
            @endforeach
        </div>
    @endif
@endif
