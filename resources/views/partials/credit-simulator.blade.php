{{-- Simulasi kredit bunga flat. Params: $price (Rp), $compact, $tenor (awal), $priceBinding (opsional: ekspresi Alpine untuk harga dinamis) --}}
@php
    $compact = $compact ?? false;
    $tenor = $tenor ?? 36;
@endphp
<div class="a18-card relative {{ $compact ? 'p-4' : 'p-5 sm:p-6' }}"
     x-data="creditSimulator({ price: {{ (int) $price }}, minDp: {{ (float) $store->min_dp_percent }}, rate: {{ (float) $store->credit_rate }}, tenor: {{ (int) $tenor }} })"
     @if (! empty($priceEvent)) x-on:{{ $priceEvent }}.window="price = $event.detail" @endif>
    <div class="absolute -top-px left-0 h-[3px] w-20 bg-brand-600" aria-hidden="true"></div>
    <div class="flex items-center gap-2">
        <x-lucide-calculator class="h-5 w-5 text-brand-600" aria-hidden="true" />
        <h3 class="a18-display text-xl text-white">Simulasi Kredit</h3>
    </div>

    <p class="mt-1 text-[13px] text-white/55">Harga kendaraan <span class="font-semibold text-white/85" x-text="rupiah(price)">{{ rupiah($price) }}</span></p>

    <div class="mt-4 space-y-4">
        <div>
            <div class="flex items-center justify-between">
                <label for="sim-dp-{{ $uid ?? 'x' }}" class="a18-label mb-0">Uang Muka</label>
                <span class="text-[13px] font-bold text-white"><span x-text="dp"></span>% &middot; <span x-text="rupiah(result.dpAmount)"></span></span>
            </div>
            <input id="sim-dp-{{ $uid ?? 'x' }}" type="range" x-bind:min="minDp" x-bind:max="sliderMax" step="5" x-model.number="dp" class="mt-3 h-2 w-full accent-brand-600">
            <div class="mt-1 flex justify-between text-xs text-white/50"><span x-text="minDp + '%'"></span><span x-text="sliderMax + '%'"></span></div>
        </div>

        <div>
            <span class="a18-label">Tenor</span>
            <div class="flex flex-wrap gap-2">
                <template x-for="t in tenors" :key="t">
                    <button type="button" x-on:click="tenor = t" x-bind:aria-pressed="tenor === t"
                            class="a18-cond min-h-[2.5rem] border px-3 text-[15px] font-bold uppercase tracking-wide transition-colors"
                            x-bind:class="tenor === t ? 'border-brand-600 bg-brand-600 text-white' : 'border-white/15 text-white/75 hover:border-white/40 hover:text-white'">
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

    <div class="a18-cut-tag mt-5 bg-brand-600 px-5 py-4">
        <p class="a18-cond text-xs font-bold uppercase tracking-widest text-white/80">Cicilan per Bulan</p>
        <p class="a18-display text-3xl leading-none text-white sm:text-4xl" x-text="rupiah(result.monthly)"></p>
        <p class="mt-1 text-[13px] text-white/85">selama <span x-text="tenor"></span> bulan</p>
    </div>

    <dl class="mt-4 space-y-2">
        <div class="flex items-center justify-between gap-3 text-sm"><dt class="text-white/55">Uang Muka (DP)</dt><dd class="font-semibold text-white" x-text="rupiah(result.dpAmount)"></dd></div>
        <div class="flex items-center justify-between gap-3 text-sm"><dt class="text-white/55">Pokok Pinjaman</dt><dd class="font-semibold text-white" x-text="rupiah(result.principal)"></dd></div>
        <div class="flex items-center justify-between gap-3 text-sm"><dt class="text-white/55">Total Bunga</dt><dd class="font-semibold text-white" x-text="rupiah(result.totalInterest)"></dd></div>
        <div class="flex items-center justify-between gap-3 text-sm"><dt class="text-white/55">Total Pembayaran</dt><dd class="font-semibold text-white" x-text="rupiah(result.totalPayment)"></dd></div>
    </dl>

    <p class="mt-4 flex items-start gap-1.5 text-xs leading-relaxed text-white/45">
        <x-lucide-info class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
        Estimasi memakai bunga flat. Angka final, biaya admin, dan asuransi mengikuti ketentuan leasing.
    </p>
</div>
