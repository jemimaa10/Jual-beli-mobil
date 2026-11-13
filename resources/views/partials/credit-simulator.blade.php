{{-- Simulasi kredit bunga flat. Params: $price (Rp), $compact, $tenor (awal), $priceBinding (opsional: ekspresi Alpine untuk harga dinamis) --}}
@php
    $compact = $compact ?? false;
    $tenor = $tenor ?? 36;
@endphp
<div class="a18-card {{ $compact ? 'p-4' : 'p-5 sm:p-6' }}"
     x-data="creditSimulator({ price: {{ (int) $price }}, minDp: {{ (float) $store->min_dp_percent }}, rate: {{ (float) $store->credit_rate }}, tenor: {{ (int) $tenor }} })"
     @if (! empty($priceEvent)) x-on:{{ $priceEvent }}.window="price = $event.detail" @endif>
    <div class="flex items-center gap-2">
        <x-lucide-calculator class="h-4 w-4 text-brand-500" aria-hidden="true" />
        <h3 class="font-display text-sm font-black uppercase tracking-wide text-white">Simulasi Kredit</h3>
    </div>

    <p class="mt-1 text-xs text-neutral-500">Harga kendaraan <span class="font-semibold text-neutral-300" x-text="rupiah(price)">{{ rupiah($price) }}</span></p>

    <div class="mt-4 space-y-4">
        <div>
            <div class="flex items-center justify-between">
                <label for="sim-dp-{{ $uid ?? 'x' }}" class="a18-label mb-0">Uang Muka</label>
                <span class="text-xs font-bold text-white"><span x-text="dp"></span>% &middot; <span x-text="rupiah(result.dpAmount)"></span></span>
            </div>
            <input id="sim-dp-{{ $uid ?? 'x' }}" type="range" x-bind:min="minDp" x-bind:max="sliderMax" step="5" x-model.number="dp" class="mt-2 w-full accent-brand-600">
            <div class="flex justify-between text-[10px] text-neutral-500"><span x-text="minDp + '%'"></span><span x-text="sliderMax + '%'"></span></div>
        </div>

        <div>
            <span class="a18-label">Tenor</span>
            <div class="flex flex-wrap gap-2">
                <template x-for="t in tenors" :key="t">
                    <button type="button" x-on:click="tenor = t" x-bind:aria-pressed="tenor === t"
                            class="rounded-xl border px-3 py-1.5 text-xs font-bold transition-colors"
                            x-bind:class="tenor === t ? 'border-brand-500 bg-brand-600 text-white' : 'border-white/15 text-neutral-300 hover:border-white/40 hover:text-white'">
                        <span x-text="t + ' bln'"></span>
                    </button>
                </template>
            </div>
        </div>

        <div>
            <label for="sim-rate-{{ $uid ?? 'x' }}" class="a18-label">Bunga Flat (% / tahun)</label>
            <input id="sim-rate-{{ $uid ?? 'x' }}" type="number" min="0" max="30" step="0.1" x-bind:value="rate" x-on:input="setRate($event.target.value)" class="a18-input">
        </div>
    </div>

    <div class="mt-5 rounded-2xl border border-brand-600/40 bg-brand-950/40 p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-brand-300">Cicilan per Bulan</p>
        <p class="font-display text-2xl font-black text-white sm:text-3xl" x-text="rupiah(result.monthly)"></p>
        <p class="mt-0.5 text-xs text-neutral-400">selama <span x-text="tenor"></span> bulan</p>
    </div>

    <dl class="mt-4 space-y-2">
        <div class="flex items-center justify-between gap-3 text-sm"><dt class="text-neutral-400">Uang Muka (DP)</dt><dd class="font-semibold text-white" x-text="rupiah(result.dpAmount)"></dd></div>
        <div class="flex items-center justify-between gap-3 text-sm"><dt class="text-neutral-400">Pokok Pinjaman</dt><dd class="font-semibold text-white" x-text="rupiah(result.principal)"></dd></div>
        <div class="flex items-center justify-between gap-3 text-sm"><dt class="text-neutral-400">Total Bunga</dt><dd class="font-semibold text-white" x-text="rupiah(result.totalInterest)"></dd></div>
        <div class="flex items-center justify-between gap-3 text-sm"><dt class="text-neutral-400">Total Pembayaran</dt><dd class="font-semibold text-white" x-text="rupiah(result.totalPayment)"></dd></div>
    </dl>

    <p class="mt-4 flex items-start gap-1.5 text-[11px] leading-relaxed text-neutral-500">
        <x-lucide-info class="mt-0.5 h-3 w-3 shrink-0" aria-hidden="true" />
        Estimasi memakai bunga flat. Angka final, biaya admin, dan asuransi mengikuti ketentuan leasing.
    </p>
</div>
