@php $left = ($align ?? 'center') === 'left'; @endphp
<div class="flex flex-col {{ $left ? 'items-start text-left' : 'items-center text-center' }}">
    @if (! empty($eyebrow))
        <span class="mb-2 inline-flex items-center gap-2 text-xs font-black uppercase tracking-[0.2em] text-brand-500">
            <span class="h-px w-5 bg-brand-600" aria-hidden="true"></span> {{ $eyebrow }}
        </span>
    @endif
    <h2 class="a18-heading text-2xl sm:text-3xl lg:text-4xl">{{ $title }}</h2>
    @if (! empty($subtitle))
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-neutral-400 sm:text-base {{ $left ? '' : 'mx-auto' }}">{{ $subtitle }}</p>
    @endif
</div>
