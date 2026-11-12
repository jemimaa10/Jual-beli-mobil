@extends('layouts.app')

@section('title', 'Nota ' . $trx->code)

@php
    $isCar = $trx->sale_type === 'car';
    $void = $trx->status === 'void';
    $car = $trx->car_snapshot ?? [];
    $credit = $trx->credit;
    $tradeIn = $trx->trade_in;
    $methodLabel = ['cash' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'credit' => 'Kredit'][$trx->payment_method] ?? $trx->payment_method;
    $item = $trx->items->first();
    $carName = $item?->name ?: (car_name($car, true) ?: 'Unit Mobil');
    $amountDue = $trx->amount_due ?: $trx->total;
    $shop = trim($store->name ?: 'AUTO18');
    $date = $trx->date->locale('id')->translatedFormat('j M Y H:i');
    $sep = str_repeat('-', 32);

    // Teks polos untuk tombol "Salin"
    $lines = ['=== ' . ($void ? '[DIBATALKAN] ' : '') . mb_strtoupper($shop) . ' ===', $store->tagline, $store->address, 'Telp: ' . $store->phone, $sep,
        'No. Nota : ' . $trx->code, 'Waktu    : ' . $date, 'Kasir    : ' . $trx->cashier, 'Pelanggan: ' . $trx->customer_name];
    if ($trx->customer_phone) { $lines[] = 'No. HP   : ' . $trx->customer_phone; }
    $lines[] = $sep;
    if ($isCar) {
        array_push($lines, 'NOTA PENJUALAN KENDARAAN', $sep, $carName,
            'Kode Stok  : ' . ($car['code'] ?? '-'), 'Tahun      : ' . ($car['year'] ?? '-'), 'Plat       : ' . ($car['plate'] ?: '-'),
            'Kilometer  : ' . km($car['mileage'] ?? 0), 'Transmisi  : ' . ($car['transmission'] ?? '-'), 'Bahan Bakar: ' . ($car['fuel'] ?? '-'), 'Warna      : ' . ($car['color'] ?? '-'),
            $sep, 'Harga      : ' . rupiah($trx->subtotal));
        if ($trx->discount > 0) { $lines[] = 'Diskon     : -' . rupiah($trx->discount); }
        $lines[] = 'TOTAL      : ' . rupiah($trx->total);
        if ($tradeIn) { $lines[] = 'Tukar Tambah (' . ($tradeIn['car_name'] ?? '') . '): -' . rupiah($tradeIn['value']); }
        if ($amountDue != $trx->total) { $lines[] = 'Sisa Bayar : ' . rupiah($amountDue); }
        $lines[] = 'Pembayaran : ' . $methodLabel;
        if ($trx->payment_method === 'credit' && $credit) {
            array_push($lines, 'Leasing    : ' . $credit['leasing'], 'DP Dibayar : ' . $credit['dp_percent'] . '% = ' . rupiah($credit['dp_amount']),
                'Tenor      : ' . $credit['tenor_months'] . ' bulan', 'Cicilan    : ' . rupiah($credit['monthly']) . ' /bulan', 'Bunga      : ' . $credit['annual_rate'] . '% flat/thn');
        } elseif ($trx->payment_method === 'cash') {
            array_push($lines, 'Bayar      : ' . rupiah($trx->paid_amount), 'Kembalian  : ' . rupiah($trx->change_amount));
        }
        if ($trx->notes) { $lines[] = 'Catatan    : ' . $trx->notes; }
    } else {
        foreach ($trx->items as $i) { array_push($lines, $i->name, "  {$i->qty} x " . rupiah($i->price) . ' = ' . rupiah($i->subtotal)); }
        array_push($lines, $sep, 'Subtotal: ' . rupiah($trx->subtotal), 'Diskon  : ' . rupiah($trx->discount));
        if ($trx->tax > 0) { $lines[] = 'PPN     : ' . rupiah($trx->tax); }
        array_push($lines, 'TOTAL   : ' . rupiah($trx->total), 'Bayar   : ' . rupiah($trx->paid_amount) . " ({$methodLabel})", 'Kembali : ' . rupiah($trx->change_amount));
    }
    array_push($lines, '================================', $store->receipt_footer);
    $copyText = implode("\n", array_filter($lines, fn ($l) => $l !== null)) . "\n";

    $row = fn ($label, $value, $strong = false, $accent = false) => ['label' => $label, 'value' => $value, 'strong' => $strong, 'accent' => $accent];
@endphp

@section('content')
@unless ($isCar)
    {{-- Transaksi kasir berhasil: kosongkan keranjang yang disimpan di browser --}}
    <script>try { sessionStorage.removeItem('auto18_pos_cart'); } catch (e) {}</script>
@endunless
<div class="mx-auto max-w-md px-4 py-8">
    <div class="overflow-hidden rounded-3xl border border-white/10 bg-ink-800 shadow-2xl" x-data="{ note: '' , copy() {
            const text = @js($copyText);
            if (!navigator.clipboard || !navigator.clipboard.writeText) { this.note = 'Browser ini tidak mengizinkan menyalin otomatis.'; window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: this.note } })); return; }
            navigator.clipboard.writeText(text).then(() => { this.note = 'Struk teks berhasil disalin ke clipboard!'; window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: this.note } })); })
                .catch(() => { this.note = 'Gagal menyalin struk. Coba lagi.'; window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: this.note } })); });
        } }">
        <p class="sr-only" role="status" aria-live="polite" x-text="note"></p>

        <div class="a18-stripes-soft flex items-center gap-2 p-4">
            <x-lucide-circle-check class="h-5 w-5 shrink-0 text-brand-500" aria-hidden="true" />
            <span class="truncate font-display text-sm font-black uppercase tracking-wide text-white">
                {{ $void ? 'Nota Transaksi Dibatalkan' : ($isCar ? 'Penjualan Mobil Tercatat!' : 'Transaksi Berhasil Disimpan!') }}
            </span>
        </div>

        <div class="overflow-x-hidden bg-ink-900 p-4 sm:p-6">
            <div aria-hidden="true" class="relative z-10 mx-auto h-3 max-w-[344px] rounded-full bg-black shadow-[inset_0_2px_4px_rgba(0,0,0,0.9)] ring-1 ring-white/15"><span class="absolute right-3 top-1/2 h-1.5 w-1.5 -translate-y-1/2 rounded-full bg-brand-500"></span></div>
            <div class="a18-print-feed -mt-1.5 overflow-hidden">
                <div class="a18-print-feed {{ $fresh ? 'a18-feed-in' : '' }}">
                    <div id="printable-receipt" class="relative mx-auto max-w-[320px] space-y-2.5 rounded-xl border border-dashed border-slate-300 bg-white p-5 font-mono text-xs text-slate-800">
                        <div class="space-y-0.5 text-center">
                            <h4 class="font-display text-lg font-black uppercase leading-none tracking-tight text-slate-900">{{ $shop }}</h4>
                            @if ($store->tagline)<p class="text-[9px] font-bold uppercase tracking-[0.25em] text-slate-500">{{ $store->tagline }}</p>@endif
                            <p class="pt-1 text-[10px] leading-tight text-slate-500">{{ $store->address }}</p>
                            <p class="text-[10px] text-slate-500">Telp: {{ $store->phone }}</p>
                        </div>
                        <div class="my-2 border-b border-dashed border-slate-300"></div>
                        @if ($isCar)<p class="text-center text-[11px] font-bold uppercase tracking-wider text-slate-900">Nota Penjualan Kendaraan</p>@endif

                        <div class="space-y-0.5 text-[11px]">
                            @foreach ([['No. Nota:', $trx->code, true], ['Tanggal:', $date, false], ['Kasir:', $trx->cashier, false], ['Pelanggan:', $trx->customer_name, false]] as [$l, $val, $st])
                                <div class="flex justify-between gap-3"><span class="text-slate-500">{{ $l }}</span><span class="text-right {{ $st ? 'font-bold text-slate-900' : 'font-medium text-slate-800' }}">{{ $val }}</span></div>
                            @endforeach
                            @if ($isCar && $trx->customer_phone)<div class="flex justify-between gap-3"><span class="text-slate-500">No. HP:</span><span class="text-right font-medium text-slate-800">{{ $trx->customer_phone }}</span></div>@endif
                        </div>
                        <div class="my-2 border-b border-dashed border-slate-300"></div>

                        @if ($isCar)
                            <div class="space-y-1 text-[11px]">
                                <p class="font-bold leading-snug text-slate-900">{{ $carName }}</p>
                                @foreach ([['Kode Stok:', $car['code'] ?? '-'], ['Tahun:', $car['year'] ?? '-'], ['Plat:', ($car['plate'] ?? null) ?: '-'], ['Kilometer:', km($car['mileage'] ?? 0)], ['Transmisi:', $car['transmission'] ?? '-'], ['Bahan Bakar:', $car['fuel'] ?? '-'], ['Warna:', $car['color'] ?? '-']] as [$l, $val])
                                    <div class="flex justify-between gap-3"><span class="text-slate-500">{{ $l }}</span><span class="text-right font-medium text-slate-800">{{ $val }}</span></div>
                                @endforeach
                            </div>
                            <div class="my-2 border-b border-dashed border-slate-300"></div>

                            <div class="relative space-y-1 text-[11px]">
                                <div class="flex justify-between gap-3"><span class="text-slate-500">Harga</span><span class="font-medium text-slate-800">{{ rupiah($trx->subtotal) }}</span></div>
                                @if ($trx->discount > 0)<div class="flex justify-between gap-3"><span class="text-slate-500">Diskon</span><span class="font-medium text-slate-800">-{{ rupiah($trx->discount) }}</span></div>@endif
                                <div class="flex justify-between gap-3 border-t border-dashed border-slate-200 pt-1 text-sm font-bold text-slate-900"><span>TOTAL</span><span>{{ rupiah($trx->total) }}</span></div>
                                @if ($tradeIn)<div class="flex justify-between gap-3"><span class="text-slate-500">Tukar Tambah{{ ! empty($tradeIn['car_name']) ? ' (' . $tradeIn['car_name'] . ')' : '' }}</span><span class="text-right font-medium text-slate-800">-{{ rupiah($tradeIn['value']) }}</span></div>@endif
                                @if ($amountDue != $trx->total)<div class="flex justify-between gap-3"><span class="text-slate-700">Sisa Bayar</span><span class="text-right font-bold text-slate-900">{{ rupiah($amountDue) }}</span></div>@endif
                                @unless ($void)
                                    @include('partials.sold-stamp', ['label' => 'TERJUAL', 'size' => 'lg', 'animate' => $fresh, 'delay' => 0.78, 'class' => 'absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 !mt-0 mix-blend-multiply'])
                                @endunless
                            </div>
                            <div class="my-2 border-b border-dashed border-slate-300"></div>

                            <div class="space-y-1 text-[11px]">
                                <div class="flex justify-between gap-3"><span class="text-slate-500">Pembayaran</span><span class="text-right font-bold text-slate-900">{{ $methodLabel }}</span></div>
                                @if ($trx->payment_method === 'credit' && $credit)
                                    @foreach ([['Leasing', $credit['leasing'] ?: '-'], ['DP Dibayar (' . $credit['dp_percent'] . '%)', rupiah($credit['dp_amount'])], ['Tenor', $credit['tenor_months'] . ' bulan'], ['Cicilan/bulan', rupiah($credit['monthly'])], ['Bunga', $credit['annual_rate'] . '% flat/thn']] as [$l, $val])
                                        <div class="flex justify-between gap-3"><span class="text-slate-500">{{ $l }}</span><span class="text-right font-medium text-slate-800">{{ $val }}</span></div>
                                    @endforeach
                                @elseif ($trx->payment_method === 'cash')
                                    <div class="flex justify-between gap-3"><span class="text-slate-500">Bayar</span><span class="text-right font-medium text-slate-800">{{ rupiah($trx->paid_amount) }}</span></div>
                                    <div class="flex justify-between gap-3"><span class="text-slate-500">Kembalian</span><span class="text-right font-bold text-slate-900">{{ rupiah($trx->change_amount) }}</span></div>
                                @endif
                            </div>
                            @if ($trx->notes)
                                <div class="my-2 border-b border-dashed border-slate-300"></div>
                                <p class="text-[10px] leading-relaxed text-slate-600"><span class="font-bold text-slate-700">Catatan: </span>{{ $trx->notes }}</p>
                            @endif
                        @else
                            <div class="space-y-2">
                                @foreach ($trx->items as $i)
                                    <div class="space-y-0.5">
                                        <div class="font-semibold text-slate-800">{{ $i->name }}</div>
                                        <div class="flex justify-between gap-3 text-[11px] text-slate-500"><span>{{ $i->qty }} x {{ rupiah($i->price) }}</span><span class="font-medium text-slate-800">{{ rupiah($i->subtotal) }}</span></div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="my-2 border-b border-dashed border-slate-300"></div>
                            <div class="space-y-1 text-[11px]">
                                <div class="flex justify-between gap-3"><span class="text-slate-500">Subtotal</span><span class="font-medium text-slate-800">{{ rupiah($trx->subtotal) }}</span></div>
                                @if ($trx->discount > 0)<div class="flex justify-between gap-3"><span class="text-slate-500">Diskon</span><span class="font-medium text-slate-800">-{{ rupiah($trx->discount) }}</span></div>@endif
                                @if ($trx->tax > 0)<div class="flex justify-between gap-3"><span class="text-slate-500">Pajak PPN</span><span class="font-medium text-slate-800">{{ rupiah($trx->tax) }}</span></div>@endif
                                <div class="flex justify-between gap-3 border-t border-dashed border-slate-200 pt-1 text-sm font-bold text-slate-900"><span>TOTAL</span><span>{{ rupiah($trx->total) }}</span></div>
                                <div class="flex justify-between gap-3"><span class="text-slate-500">Bayar ({{ $methodLabel }})</span><span class="font-medium text-slate-800">{{ rupiah($trx->paid_amount) }}</span></div>
                                <div class="flex justify-between gap-3"><span class="text-slate-500">Kembalian</span><span class="font-bold text-slate-900">{{ rupiah($trx->change_amount) }}</span></div>
                            </div>
                        @endif

                        <div class="my-2 border-b border-dashed border-slate-300"></div>
                        <div class="space-y-1 pt-1 text-center text-[10px] text-slate-500">
                            <p class="whitespace-pre-line leading-relaxed">{{ $store->receipt_footer }}</p>
                            <div class="flex justify-center pt-2">
                                <div class="flex h-7 w-40 items-center justify-between opacity-80" aria-hidden="true">
                                    @for ($i = 0; $i < 32; $i++)<div class="h-full bg-slate-900" style="width: {{ $i % 3 === 0 ? '3px' : ($i % 2 === 0 ? '1.5px' : '1px') }}"></div>@endfor
                                </div>
                            </div>
                            <p class="font-mono text-[9px] text-slate-400">{{ $trx->code }}</p>
                        </div>

                        @if ($void)
                            @include('partials.sold-stamp', ['label' => 'DIBATALKAN', 'size' => 'lg', 'animate' => false, 'class' => 'absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 !mt-0 mix-blend-multiply'])
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-2 border-t border-white/10 bg-ink-800 p-4">
            <div class="flex items-center gap-1.5">
                <button type="button" x-on:click="copy()" class="a18-btn-ghost px-3 py-2.5 text-xs" title="Salin teks struk"><x-lucide-copy class="h-4 w-4" aria-hidden="true" /> <span class="hidden sm:inline">Salin</span></button>
                <button type="button" onclick="window.print()" class="a18-btn-outline px-3.5 py-2 text-xs"><x-lucide-printer class="h-4 w-4" aria-hidden="true" /> Cetak Struk</button>
            </div>
            <a href="{{ $isCar ? route('showroom.cars.index') : (Route::has('showroom.pos') ? route('showroom.pos') : route('showroom.dashboard')) }}" class="a18-btn-primary px-4 py-2.5 text-xs">{{ $isCar ? 'Selesai' : 'Transaksi Baru' }}</a>
        </div>
    </div>
</div>
@endsection
