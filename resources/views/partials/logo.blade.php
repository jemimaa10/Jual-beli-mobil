@php
    // Wordmark AUTO18 gaya Motorsport: putih polos + "18" merah, miring tebal.
    // showTagline: label samping "Auto / Eighteen" (gaya header) atau di bawah (ukuran lg/xl).
    $sizes = [
        'sm' => ['word' => 'text-[26px]', 'tagline' => 'text-[10px]'],
        'md' => ['word' => 'text-[32px]', 'tagline' => 'text-[11px]'],
        'lg' => ['word' => 'text-[44px]', 'tagline' => 'text-xs'],
        'xl' => ['word' => 'text-6xl', 'tagline' => 'text-sm'],
    ];
    $key = $size ?? 'md';
    $s = $sizes[$key] ?? $sizes['md'];
    $showTagline = $showTagline ?? true;
    $stacked = in_array($key, ['lg', 'xl'], true);
@endphp
<div class="inline-flex select-none leading-none {{ $stacked ? 'flex-col items-start' : 'items-center' }} {{ $class ?? '' }}" aria-label="Auto18 - Auto Eighteen">
    <span class="a18-display pr-0.5 tracking-tight text-white {{ $s['word'] }}">AUTO<span class="text-brand-600">18</span></span>
    @if ($showTagline)
        @if ($stacked)
            <span class="a18-cond mt-2 flex items-center gap-2 font-semibold uppercase tracking-[0.3em] text-white/50 {{ $s['tagline'] }}">
                <span class="h-1.5 w-6 bg-brand-600" aria-hidden="true"></span>Auto Eighteen
            </span>
        @else
            <span class="a18-cond ml-3 border-l border-white/15 pl-3 font-semibold uppercase leading-tight tracking-[0.3em] text-white/50 {{ $s['tagline'] }}">Auto<br>Eighteen</span>
        @endif
    @endif
</div>
