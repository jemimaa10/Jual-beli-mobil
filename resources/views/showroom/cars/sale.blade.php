@extends('layouts.app')

@section('title', 'Proses Penjualan ' . $car->code)

@php
    $old = [
        'payment_method' => old('payment_method', 'cash'),
        'discount' => old('discount', 0),
        'paid_amount' => old('paid_amount', ''),
        'leasing' => old('leasing', $leasing[0]),
        'dp_percent' => old('dp_percent', ''),
        'tenor_months' => (int) old('tenor_months', 36),
        'annual_rate' => old('annual_rate', ''),
        'use_trade_in' => (bool) old('use_trade_in', false),
        'trade_in_request_id' => old('trade_in_request_id', ''),
        'trade_in_car_name' => old('trade_in_car_name', ''),
        'trade_in_value' => old('trade_in_value', ''),
    ];
    $err = fn (string $f) => $errors->first($f);
@endphp

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8"
     x-data="carSaleForm({ price: {{ (int) $car->price }}, buyPrice: {{ (int) $car->buy_price }}, minDp: {{ (float) $store->min_dp_percent }}, rate: {{ (float) $store->credit_rate }}, requests: @js($tradeInRequests), old: @js($old) })">
    <div class="overflow-hidden rounded-3xl border border-white/10 bg-ink-800">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 border-b border-white/10 p-4">
            <div class="flex min-w-0 items-center gap-3">
                @include('partials.car-image', ['car' => $car, 'class' => 'h-14 w-20 shrink-0 rounded-lg'])
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-brand-500">Proses Penjualan &middot; {{ $car->code }}</p>
                    <h1 class="truncate font-display text-base font-black uppercase tracking-wide text-white">{{ car_name($car, true) }}</h1>
                    <p class="text-xs text-neutral-400">{{ rupiah($car->price) }}</p>
                </div>
            </div>
            <a href="{{ route('showroom.cars.index') }}" aria-label="Tutup" class="a18-btn-ghost px-2"><x-lucide-x class="h-5 w-5" /></a>
        </div>

        <form method="POST" action="{{ route('showroom.cars.sale.store', $car) }}" class="grid gap-5 p-5 lg:grid-cols-[1.4fr_1fr]" novalidate>
            @csrf
            <div class="space-y-5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label for="co-name" class="a18-label">Nama Pembeli</label>
                        <input id="co-name" name="customer_name" value="{{ old('customer_name') }}" class="a18-input" placeholder="Nama sesuai KTP" required>
                        @if ($err('customer_name'))<p class="mt-1 text-xs text-brand-400">{{ $err('customer_name') }}</p>@endif</div>
                    <div><label for="co-phone" class="a18-label">Nomor HP</label>
                        <input id="co-phone" name="customer_phone" type="tel" value="{{ old('customer_phone') }}" class="a18-input" placeholder="0812-3456-7890"></div>
                </div>

                <div><label for="co-discount" class="a18-label">Diskon (Rp)</label>
                    <input id="co-discount" name="discount" type="number" min="0" step="500000" x-model="discount" class="a18-input">
                    @if ($err('discount'))<p class="mt-1 text-xs text-brand-400">{{ $err('discount') }}</p>@endif</div>

                {{-- Tukar tambah --}}
                <div class="a18-card p-4">
                    <input type="hidden" name="use_trade_in" value="0">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" name="use_trade_in" value="1" x-model="useTradeIn" class="h-4 w-4 accent-brand-600">
                        <span class="flex items-center gap-1.5 text-sm font-bold text-white"><x-lucide-arrow-left-right class="h-4 w-4 text-brand-500" aria-hidden="true" /> Tukar Tambah Mobil Lama</span>
                    </label>
                    <div x-show="useTradeIn" x-cloak class="mt-3 space-y-3">
                        <div><label for="co-tradein-req" class="a18-label">Ambil dari permintaan tukar tambah</label>
                            <select id="co-tradein-req" name="trade_in_request_id" x-on:change="pickRequest($event.target.value)" class="a18-input">
                                <option value="">&mdash; Isi manual &mdash;</option>
                                <template x-for="r in requests" :key="r.id"><option x-bind:value="r.id" x-text="r.label" x-bind:selected="String(r.id) === String(requestId)"></option></template>
                            </select></div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div><label for="co-tradein-name" class="a18-label">Mobil Lama</label>
                                <input id="co-tradein-name" name="trade_in_car_name" x-model="tradeName" class="a18-input" placeholder="Honda Jazz RS 2017">
                                @if ($err('trade_in_car_name'))<p class="mt-1 text-xs text-brand-400">{{ $err('trade_in_car_name') }}</p>@endif</div>
                            <div><label for="co-tradein-value" class="a18-label">Nilai Tukar Tambah (Rp)</label>
                                <input id="co-tradein-value" name="trade_in_value" type="number" min="0" step="500000" x-model="tradeValue" class="a18-input">
                                @if ($err('trade_in_value'))<p class="mt-1 text-xs text-brand-400">{{ $err('trade_in_value') }}</p>@endif</div>
                        </div>
                        <p class="text-xs text-neutral-500" x-show="requestId">Mobil ini otomatis masuk ke stok showroom setelah penjualan diselesaikan.</p>
                        <p class="text-xs text-amber-400" x-show="!requestId">Tukar tambah manual hanya mengurangi sisa bayar &mdash; mobil lama ini TIDAK otomatis masuk stok. Tambahkan lewat Stok Mobil &rarr; Tambah Mobil, atau pilih dari daftar permintaan tukar tambah di atas.</p>
                    </div>
                </div>

                {{-- Metode pembayaran --}}
                <div>
                    <span class="a18-label">Metode Pembayaran</span>
                    <input type="hidden" name="payment_method" x-bind:value="method">
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ([['cash', 'Tunai', 'banknote'], ['transfer', 'Transfer', 'landmark'], ['credit', 'Kredit', 'credit-card']] as [$id, $label, $icon])
                            <button type="button" x-on:click="method = '{{ $id }}'" x-bind:aria-pressed="method === '{{ $id }}'"
                                    class="flex flex-col items-center gap-1.5 rounded-xl border px-3 py-3 text-xs font-bold transition-colors"
                                    x-bind:class="method === '{{ $id }}' ? 'border-brand-500 bg-brand-600 text-white' : 'border-white/15 text-neutral-300 hover:border-white/40 hover:text-white'">
                                @svg('lucide-' . $icon, 'h-5 w-5') {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div x-show="method === 'cash'">
                    <label for="co-paid" class="a18-label">Uang Diterima (Rp)</label>
                    <input id="co-paid" name="paid_amount" type="number" min="0" step="100000" x-model="paid" x-bind:placeholder="'Pas ' + rupiah(amountDue)" class="a18-input">
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-for="(amount, i) in quickAmounts" :key="amount">
                            <button type="button" x-on:click="paid = amount" class="rounded-xl border border-white/15 px-3 py-1.5 text-xs font-semibold text-neutral-300 transition-colors hover:border-brand-500 hover:text-white" x-text="i === 0 ? 'Uang Pas' : rupiah(amount)"></button>
                        </template>
                    </div>
                    @if ($err('paid_amount'))<p class="mt-1 text-xs text-brand-400">{{ $err('paid_amount') }}</p>@endif
                </div>

                <div x-show="method === 'transfer'" x-cloak class="rounded-xl border border-white/10 bg-ink-900 p-4 text-sm text-neutral-300">
                    <p class="font-bold text-white">Transfer ke rekening showroom</p>
                    <p class="mt-1 text-xs text-neutral-400">Pastikan dana sebesar <span class="font-bold text-white" x-text="rupiah(amountDue)"></span> sudah diterima sebelum menyelesaikan transaksi.</p>
                </div>

                <div x-show="method === 'credit'" x-cloak class="space-y-3">
                    <div><label for="co-leasing" class="a18-label">Leasing</label>
                        <select id="co-leasing" name="leasing" x-model="leasing" class="a18-input">@foreach ($leasing as $partner)<option value="{{ $partner }}">{{ $partner }}</option>@endforeach</select></div>
                    <div class="a18-card p-4">
                        <div class="flex items-center justify-between"><label for="co-dp" class="a18-label mb-0">Uang Muka</label><span class="text-xs font-bold text-white"><span x-text="dp"></span>% &middot; <span x-text="rupiah(sim.dpAmount)"></span></span></div>
                        <input id="co-dp" name="dp_percent" type="range" x-bind:min="minDp" x-bind:max="Math.max(70, minDp)" step="5" x-model.number="dp" class="mt-2 w-full accent-brand-600">
                        <span class="a18-label mt-3">Tenor</span>
                        <input type="hidden" name="tenor_months" x-bind:value="tenor">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="t in tenors" :key="t"><button type="button" x-on:click="tenor = t" class="rounded-xl border px-3 py-1.5 text-xs font-bold transition-colors" x-bind:class="tenor === t ? 'border-brand-500 bg-brand-600 text-white' : 'border-white/15 text-neutral-300 hover:border-white/40'" x-text="t + ' bln'"></button></template>
                        </div>
                        <label for="co-rate" class="a18-label mt-3">Bunga Flat (% / tahun)</label>
                        <input id="co-rate" name="annual_rate" type="number" min="0" max="30" step="0.1" x-bind:value="rate" x-on:input="setRate($event.target.value)" class="a18-input">
                    </div>
                    @if ($err('dp_percent') || $err('tenor_months') || $err('annual_rate') || $err('leasing'))<p class="text-xs text-brand-400">{{ $err('dp_percent') ?: $err('tenor_months') ?: $err('annual_rate') ?: $err('leasing') }}</p>@endif
                </div>

                <div><label for="co-notes" class="a18-label">Catatan (opsional)</label>
                    <textarea id="co-notes" name="notes" rows="2" class="a18-input" placeholder="Misal: pelat nomor diurus showroom">{{ old('notes') }}</textarea></div>
            </div>

            {{-- Ringkasan --}}
            <div class="lg:sticky lg:top-24 lg:self-start">
                <div class="a18-card p-5">
                    <h2 class="font-display text-sm font-black uppercase tracking-wide text-white">Ringkasan</h2>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3"><dt class="text-neutral-400">Harga Mobil</dt><dd class="font-semibold text-white">{{ rupiah($car->price) }}</dd></div>
                        <div class="flex items-center justify-between gap-3" x-show="safeDiscount > 0"><dt class="text-neutral-400">Diskon</dt><dd class="font-semibold text-brand-400">&minus; <span x-text="rupiah(safeDiscount)"></span></dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="text-neutral-400">Total</dt><dd class="font-bold text-white" x-text="rupiah(total)"></dd></div>
                        <div class="flex items-center justify-between gap-3" x-show="appliedTradeIn > 0"><dt class="text-neutral-400">Tukar Tambah</dt><dd class="font-semibold text-brand-400">&minus; <span x-text="rupiah(appliedTradeIn)"></span></dd></div>
                    </dl>

                    <div class="mt-4 rounded-2xl border border-brand-600/40 bg-brand-950/40 p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-brand-300">Sisa Bayar</p>
                        <p class="font-display text-2xl font-black text-white" x-text="rupiah(amountDue)"></p>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-sm" x-show="method === 'cash'"><span class="text-neutral-400">Kembalian</span><span class="font-bold text-white" x-text="rupiah(change)"></span></div>

                    <dl class="mt-3 space-y-2 text-sm" x-show="method === 'credit'" x-cloak>
                        <div class="flex items-center justify-between"><dt class="text-neutral-400">DP (<span x-text="dp"></span>%)</dt><dd class="font-bold text-white" x-text="rupiah(sim.dpAmount)"></dd></div>
                        <div class="flex items-center justify-between"><dt class="text-neutral-400">Cicilan</dt><dd class="font-bold text-white"><span x-text="rupiah(sim.monthly)"></span> &times; <span x-text="tenor"></span> bln</dd></div>
                        <div class="flex items-center justify-between"><dt class="text-neutral-400">Leasing</dt><dd class="font-semibold text-white" x-text="leasing"></dd></div>
                    </dl>

                    <div class="mt-4 flex items-center justify-between border-t border-white/10 pt-3 text-xs">
                        <span class="text-neutral-500">Estimasi laba</span>
                        <span class="font-bold" x-bind:class="profit >= 0 ? 'text-emerald-400' : 'text-brand-400'" x-text="rupiah(profit)"></span>
                    </div>
                    <p class="mt-2 flex items-start gap-1.5 text-xs text-amber-400" x-show="profit < 0" x-cloak><x-lucide-triangle-alert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" /> Harga jual di bawah modal pembelian.</p>

                    <button type="submit" class="a18-btn-primary mt-5 w-full py-3"><x-lucide-circle-check class="h-4 w-4" /> Selesaikan Penjualan</button>
                    <a href="{{ route('showroom.cars.index') }}" class="a18-btn-ghost mt-2 w-full justify-center">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
