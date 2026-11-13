{{-- Notifikasi toast: dipicu oleh flash session (success / error / info) atau JS: window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } })) --}}
@php
    $flash = collect([
        ['type' => 'success', 'message' => session('success')],
        ['type' => 'error', 'message' => session('error')],
        ['type' => 'info', 'message' => session('info')],
    ])->filter(fn ($t) => filled($t['message']))->values();
@endphp
<div x-data="toaster(@js($flash))" x-on:toast.window="add($event.detail.type, $event.detail.message)"
     class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4" aria-live="polite">
    <template x-for="t in items" :key="t.id">
        <div x-show="t.open" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-3"
             x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0"
             x-on:mouseenter="hold(t)" x-on:mouseleave="release(t)"
             class="pointer-events-auto relative flex w-full max-w-md items-start gap-3 overflow-hidden rounded-xl border border-white/10 bg-ink-700 py-3 pl-5 pr-3 text-sm text-white shadow-2xl shadow-black/60"
             role="status">
            <span class="absolute inset-y-0 left-0 w-1.5" :class="{ 'bg-emerald-500': t.type === 'success', 'bg-brand-600': t.type === 'error', 'bg-neutral-400': t.type === 'info' }"></span>
            <span class="mt-0.5 shrink-0" :class="{ 'text-emerald-400': t.type === 'success', 'text-brand-500': t.type === 'error', 'text-neutral-300': t.type === 'info' }">
                <x-lucide-circle-check class="h-5 w-5" x-show="t.type === 'success'" />
                <x-lucide-circle-alert class="h-5 w-5" x-show="t.type === 'error'" />
                <x-lucide-info class="h-5 w-5" x-show="t.type === 'info'" />
            </span>
            <p class="min-w-0 flex-1 leading-snug" x-text="t.message"></p>
            <button type="button" x-on:click="dismiss(t)" aria-label="Tutup" class="shrink-0 rounded-md p-1 text-neutral-400 hover:bg-white/10 hover:text-white"><x-lucide-x class="h-4 w-4" /></button>
        </div>
    </template>
</div>
