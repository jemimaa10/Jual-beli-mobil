@php $left = ($align ?? 'center') === 'left'; @endphp
<div class="flex flex-col {{ $left ? 'items-start text-left' : 'items-center text-center' }}">
    @if (! empty($eyebrow))
        <span class="a18-eyebrow mb-3"><span class="a18-stripes-red h-2.5 w-10" aria-hidden="true"></span>{{ $eyebrow }}</span>
    @endif
    <h2 class="a18-heading text-[40px] leading-[0.92] md:text-[56px]">{{ $title }}</h2>
    @if (! empty($subtitle))
        <p class="mt-4 max-w-2xl text-base leading-relaxed text-white/65 {{ $left ? '' : 'mx-auto' }}">{{ $subtitle }}</p>
    @endif
</div>
