@php
    $sizes = [
        'sm' => ['word' => 'text-xl', 'tagline' => 'text-[7px]', 'line' => 'w-2.5'],
        'md' => ['word' => 'text-2xl', 'tagline' => 'text-[8px]', 'line' => 'w-3'],
        'lg' => ['word' => 'text-4xl', 'tagline' => 'text-[11px]', 'line' => 'w-5'],
        'xl' => ['word' => 'text-6xl', 'tagline' => 'text-sm', 'line' => 'w-8'],
    ];
    $s = $sizes[$size ?? 'md'] ?? $sizes['md'];
    $showTagline = $showTagline ?? true;
@endphp
<div class="inline-flex select-none flex-col items-center leading-none {{ $class ?? '' }}" aria-label="Auto18 - Auto Eighteen">
    <div class="font-display font-black italic tracking-tighter {{ $s['word'] }}">
        <span class="bg-gradient-to-b from-white via-neutral-200 to-neutral-500 bg-clip-text pr-0.5 text-transparent drop-shadow-[0_2px_0_rgba(0,0,0,0.9)]">AUTO</span><span class="bg-gradient-to-b from-brand-400 via-brand-600 to-brand-800 bg-clip-text pr-1 text-transparent drop-shadow-[0_2px_0_rgba(0,0,0,0.9)]">18</span>
    </div>
    @if ($showTagline)
        <div class="mt-1 flex items-center gap-1 font-display font-bold uppercase tracking-[0.3em] text-neutral-300 {{ $s['tagline'] }}">
            <span class="h-px bg-neutral-400 {{ $s['line'] }}"></span>
            <span>Auto Eighteen</span>
            <span class="h-px bg-neutral-400 {{ $s['line'] }}"></span>
        </div>
    @endif
</div>
