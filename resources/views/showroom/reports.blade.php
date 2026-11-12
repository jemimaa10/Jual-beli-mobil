@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@php
    $exportQuery = array_filter($filters, fn ($v) => $v !== null && $v !== '');
    $cards = [
        ['wallet', 'Total Omset', $totals['revenue'], $totals['count'] . ' nota selesai'],
        ['package', 'Total Modal (HPP)', $totals['cost'], 'harga beli seluruh item terjual'],
        ['trending-up', 'Laba Bersih', $totals['profit'], 'setelah diskon'],
        ['receipt', 'Rata-Rata Transaksi', $totals['average'], 'nilai per nota'],
    ];
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="a18-stripes-soft flex flex-col gap-4 rounded-3xl p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Panel Showroom</span>
            <h1 class="a18-heading mt-1 text-2xl sm:text-3xl">Laporan Penjualan</h1>
            <p class="mt-1.5 max-w-xl text-sm text-neutral-400">Riwayat penjualan mobil dan kasir aksesoris. Transaksi yang dibatalkan tetap tercatat tetapi tidak dihitung.</p>
        </div>
        <a href="{{ route('showroom.reports.export', $exportQuery) }}" class="a18-btn-outline shrink-0"><x-lucide-download class="h-4 w-4" /> Ekspor CSV</a>
    </div>

    <form method="GET" action="{{ route('showroom.reports') }}" class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <label for="r-q" class="a18-label">Cari</label>
            <input id="r-q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Kode nota, pelanggan, atau item" class="a18-input">
        </div>
        <div>
            <label for="r-period" class="a18-label">Periode</label>
            <select id="r-period" name="period" class="a18-input">@foreach ($periods as $k => $v)<option value="{{ $k }}" @selected($filters['period'] === $k && ! $filters['from'] && ! $filters['to'])>{{ $v }}</option>@endforeach</select>
        </div>
        <div>
            <label for="r-type" class="a18-label">Jenis</label>
            <select id="r-type" name="sale_type" class="a18-input">
                <option value="">Semua Jenis</option>
                <option value="car" @selected($filters['sale_type'] === 'car')>Penjualan Mobil</option>
                <option value="retail" @selected($filters['sale_type'] === 'retail')>Aksesoris &amp; Jasa</option>
            </select>
        </div>
        <div>
            <label for="r-method" class="a18-label">Metode Bayar</label>
            <select id="r-method" name="payment_method" class="a18-input">
                <option value="">Semua Metode</option>
                @foreach ($methods as $k => $v)<option value="{{ $k }}" @selected($filters['payment_method'] === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div class="flex items-end"><button type="submit" class="a18-btn-primary w-full">Terapkan</button></div>
        <div>
            <label for="r-from" class="a18-label">Dari Tanggal</label>
            <input id="r-from" type="date" name="from" value="{{ $filters['from'] }}" class="a18-input">
        </div>
        <div>
            <label for="r-to" class="a18-label">Sampai Tanggal</label>
            <input id="r-to" type="date" name="to" value="{{ $filters['to'] }}" class="a18-input">
        </div>
        <p class="self-end pb-2.5 text-[11px] text-neutral-500 sm:col-span-2 lg:col-span-3">Rentang tanggal, bila diisi, menggantikan pilihan Periode. <a href="{{ route('showroom.reports') }}" class="font-bold text-brand-400 hover:text-brand-300">Reset filter</a></p>
    </form>

    <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($cards as [$icon, $label, $value, $hint])
            <div class="a18-card min-w-0 p-4">
                <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-neutral-500">@svg('lucide-' . $icon, 'h-3.5 w-3.5 text-brand-500') {{ $label }}</div>
                <p class="mt-1.5 truncate font-display text-xl font-black text-white" title="{{ rupiah($value) }}">{{ rupiah_short($value) }}</p>
                <p class="text-xs text-neutral-500">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    @php $sum = max(1, $totals['car'] + $totals['retail']); @endphp
    <div class="mt-3 grid gap-3 sm:grid-cols-2">
        @foreach ([['Omset Mobil', $totals['car'], 'bg-brand-600'], ['Omset Aksesoris', $totals['retail'], 'bg-white/70']] as [$label, $value, $bar])
            <div class="a18-card p-4">
                <div class="flex items-center justify-between gap-2 text-sm"><span class="text-neutral-400">{{ $label }}</span><span class="font-bold text-white">{{ rupiah($value) }}</span></div>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full {{ $bar }}" style="width: {{ round($value / $sum * 100, 1) }}%"></div></div>
                <p class="mt-1 text-[11px] text-neutral-500">{{ number_format($value / $sum * 100, 1, ',', '.') }}% dari omset</p>
            </div>
        @endforeach
    </div>

    <p class="mt-6 text-xs text-neutral-500">{{ $transactions->count() }} transaksi ditampilkan</p>
    <div class="mt-3 overflow-x-auto rounded-2xl border border-white/10">
        <table class="w-full min-w-[900px] text-sm">
            <thead class="bg-ink-900 text-left text-[10px] uppercase tracking-wider text-neutral-500">
                <tr><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3">Pelanggan</th><th class="px-4 py-3">Rincian</th><th class="px-4 py-3">Metode</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3 text-right">Laba</th><th class="px-4 py-3 text-center">Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($transactions as $t)
                    @php $void = $t->status === 'void'; @endphp
                    <tr class="border-t border-white/10 transition-colors hover:bg-white/5 {{ $void ? 'opacity-60' : '' }}">
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-neutral-400">{{ $t->date->locale('id')->translatedFormat('j M Y') }}<span class="block text-neutral-500">{{ $t->date->format('H:i') }}</span></td>
                        <td class="px-4 py-3 font-mono text-xs text-white">{{ $t->code }}@if ($void)<span class="mt-0.5 block font-sans text-[10px] font-bold uppercase text-brand-400">Dibatalkan</span>@endif</td>
                        <td class="px-4 py-3"><span class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase {{ $t->sale_type === 'car' ? 'border-brand-500/40 bg-brand-600/20 text-brand-300' : 'border-white/15 text-neutral-300' }}">{{ $t->sale_type === 'car' ? 'Mobil' : 'Aksesoris' }}</span></td>
                        <td class="px-4 py-3 text-neutral-300">{{ $t->customer_name }}</td>
                        <td class="max-w-[260px] px-4 py-3 text-xs text-neutral-400"><span class="line-clamp-2">{{ $t->items->map(fn ($i) => ($t->sale_type === 'car' ? '' : $i->qty . 'x ') . $i->name)->implode(', ') }}</span></td>
                        <td class="px-4 py-3 text-xs text-neutral-300">{{ $methods[$t->payment_method] ?? $t->payment_method }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ $void ? 'text-neutral-500 line-through' : 'text-white' }}">{{ rupiah($t->total) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ $void ? 'text-neutral-500 line-through' : ($t->total_profit >= 0 ? 'text-emerald-400' : 'text-brand-400') }}">{{ rupiah($t->total_profit) }}</td>
                        <td class="px-4 py-3 text-center"><a href="{{ route('showroom.receipt', $t) }}" title="Lihat / cetak ulang nota" aria-label="Lihat nota {{ $t->code }}" class="a18-btn-ghost inline-flex px-2"><x-lucide-receipt class="h-4 w-4" /></a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-10 text-center text-sm text-neutral-500">Tidak ada transaksi pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
