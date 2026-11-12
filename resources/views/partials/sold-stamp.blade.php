{{-- Stempel tinta karet. Params: $label (TERJUAL), $size (sm|md|lg), $animate (hentakan sekali untuk penjualan baru) --}}
@php
    $label = $label ?? 'TERJUAL';
    $size = $size ?? 'md';
    $animate = $animate ?? false;
    $cfg = ['sm' => ['warp' => 2.5, 'grain' => 0.6], 'md' => ['warp' => 4, 'grain' => 0.42], 'lg' => ['warp' => 3, 'grain' => 0.5]][$size] ?? ['warp' => 4, 'grain' => 0.42];
    $fid = 'a18-ink-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $isLong = mb_strlen($label) > 8;
@endphp
<span role="img" aria-label="Stempel {{ $label }}" data-stamp-mode="{{ $animate ? 'slam' : 'static' }}" class="a18-stamp-root {{ $class ?? '' }}">
    <svg class="a18-stamp-defs" width="0" height="0" aria-hidden="true" focusable="false">
        <filter id="{{ $fid }}" x="-10%" y="-20%" width="120%" height="140%" color-interpolation-filters="sRGB">
            <feTurbulence type="fractalNoise" baseFrequency="0.045" numOctaves="3" seed="4" result="warp" />
            <feDisplacementMap in="SourceGraphic" in2="warp" scale="{{ $cfg['warp'] }}" xChannelSelector="R" yChannelSelector="G" result="rough" />
            <feColorMatrix in="warp" type="matrix" values="0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 -1.8 0 1.85" result="density" />
            <feTurbulence type="fractalNoise" baseFrequency="{{ $cfg['grain'] }}" numOctaves="2" seed="9" result="grain" />
            <feColorMatrix in="grain" type="matrix" values="0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  -9 0 0 0 6.4" result="speckle" />
            <feComposite in="rough" in2="speckle" operator="in" result="speckled" />
            <feComposite in="speckled" in2="density" operator="in" />
        </filter>
    </svg>
    <span class="a18-stamp-press">
        <span class="a18-stamp a18-stamp--{{ $size }}{{ $isLong ? ' a18-stamp--long' : '' }}" style="filter: url(#{{ $fid }})">
            <span class="a18-stamp__rule"><span class="a18-stamp__label">{{ $label }}</span></span>
        </span>
    </span>
</span>
