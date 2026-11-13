@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $methodLabel = ['cash' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'credit' => 'Kredit'];
    $day = fn ($d) => \Illuminate\Support\Carbon::parse($d)->locale('id')->translatedFormat('D, j M');
    $kpi = fn ($icon, $label, $value, $hint = null, $href = null, $title = null) => compact('icon', 'label', 'value', 'hint', 'href', 'title');
    $todayCards = [
        $kpi('wallet', 'Omset Hari Ini', rupiah_short($today['revenue']), 'penjualan mobil + aksesoris', null, rupiah($today['revenue'])),
        $kpi('trending-up', 'Laba Hari Ini', rupiah_short($today['profit']), 'setelah modal & diskon', null, rupiah($today['profit'])),
        $kpi('receipt', 'Jumlah Transaksi', $today['count'], 'nota selesai hari ini', route('showroom.reports', ['period' => 'today'])),
    ];
    $showroomCards = [
        $kpi('car-front', 'Mobil Tersedia / Dipesan', $showroom['available'] . ' / ' . $showroom['booked'], 'unit siap dijual · unit dipesan', route('showroom.cars.index')),
        $kpi('badge-check', 'Mobil Terjual Bulan Ini', $showroom['soldThisMonth'], 'unit', route('showroom.cars.index', ['status' => 'sold'])),
        $kpi('arrow-left-right', 'Permintaan Jual Baru', $showroom['newRequests'], 'menunggu dijadwalkan', route('showroom.cars.index', ['tab' => 'requests'])),
        $kpi('calendar-check', 'Test Drive', $showroom['testDrivesUpcoming'], $showroom['testDrivesToday'] . ' di antaranya hari ini', route('showroom.cars.index', ['tab' => 'testdrive'])),
    ];
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="a18-stripes-soft flex flex-col gap-4 rounded-3xl p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Panel Showroom</span>
            <h1 class="a18-heading mt-1 text-2xl sm:text-3xl">Dashboard Showroom Auto18</h1>
            <p class="mt-1.5 max-w-xl text-sm text-neutral-400">{{ now()->locale('id')->translatedFormat('l, j F Y') }} &middot; ringkasan penjualan mobil, kasir aksesoris, dan aktivitas showroom.</p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <a href="{{ route('showroom.pos') }}" class="a18-btn-outline"><x-lucide-shopping-bag class="h-4 w-4" /> Buka Kasir</a>
            <a href="{{ route('showroom.cars.create') }}" class="a18-btn-primary"><x-lucide-plus class="h-4 w-4" /> Tambah Mobil</a>
        </div>
    </div>

    @foreach ([['Performa Hari Ini', $todayCards, 'sm:grid-cols-3'], ['Ringkasan Showroom', $showroomCards, 'sm:grid-cols-2 lg:grid-cols-4']] as [$heading, $cards, $cols])
        <h2 class="mt-6 font-display text-xs font-black uppercase tracking-[0.2em] text-neutral-500">{{ $heading }}</h2>
        <div class="mt-3 grid grid-cols-1 gap-3 {{ $cols }}">
            @foreach ($cards as $c)
                @if ($c['href'])<a href="{{ $c['href'] }}" class="a18-card min-w-0 p-4 transition-colors hover:border-brand-600">@else<div class="a18-card min-w-0 p-4">@endif
                    <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-neutral-500">@svg('lucide-' . $c['icon'], 'h-3.5 w-3.5 text-brand-500') {{ $c['label'] }}</div>
                    <p class="mt-1.5 truncate font-display text-2xl font-black text-white" @if ($c['title']) title="{{ $c['title'] }}" @endif>{{ $c['value'] }}</p>
                    @if ($c['hint'])<p class="text-xs text-neutral-500">{{ $c['hint'] }}</p>@endif
                @if ($c['href'])</a>@else</div>@endif
            @endforeach
        </div>
    @endforeach

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Grafik omset 7 hari --}}
        <section class="a18-card p-5 lg:col-span-2" x-data="{ mode: 'all', series: @js($series), rupiahShort: window.auto18.rupiahShort,
                value(d) { return this.mode === 'car' ? d.car : (this.mode === 'retail' ? d.retail : d.car + d.retail) },
                get max() { return Math.max(1, ...this.series.map(d => this.value(d))) },
                get total() { return this.series.reduce((s, d) => s + this.value(d), 0) } }">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="a18-heading text-sm sm:text-base">Tren Omset 7 Hari</h2>
                    <p class="text-xs text-neutral-500">Total <span class="font-bold text-white" x-text="window.auto18.rupiah(total)"></span></p>
                </div>
                <div class="flex rounded-xl border border-white/15 p-0.5" role="group" aria-label="Filter jenis omset">
                    @foreach ([['all', 'Semua'], ['car', 'Mobil'], ['retail', 'Aksesoris']] as [$id, $label])
                        <button type="button" x-on:click="mode = '{{ $id }}'" x-bind:aria-pressed="mode === '{{ $id }}'" class="rounded-lg px-3 py-1 text-xs font-bold transition-colors" x-bind:class="mode === '{{ $id }}' ? 'bg-brand-600 text-white' : 'text-neutral-400 hover:text-white'">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="mt-5 flex h-48 items-end gap-2 sm:gap-3" aria-hidden="true">
                <template x-for="d in series" :key="d.date">
                    <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1.5" x-bind:title="d.full + ': ' + window.auto18.rupiah(value(d))">
                        <span class="truncate text-[10px] font-bold text-neutral-400" x-text="value(d) ? rupiahShort(value(d)).replace('Rp ', '') : ''"></span>
                        <div class="flex w-full max-w-[56px] flex-col justify-end overflow-hidden rounded-t-lg bg-white/5 transition-all duration-300" x-bind:style="'height:' + Math.max(2, value(d) / max * 100) + '%'">
                            <div class="bg-brand-600" x-show="mode !== 'retail'" x-bind:style="'height:' + (value(d) ? (mode === 'car' ? 100 : d.car / (d.car + d.retail) * 100) : 0) + '%'"></div>
                            <div class="bg-white/70" x-show="mode !== 'car'" x-bind:style="'height:' + (value(d) ? (mode === 'retail' ? 100 : d.retail / (d.car + d.retail) * 100) : 0) + '%'"></div>
                        </div>
                        <span class="text-[10px] uppercase text-neutral-500" x-text="d.label"></span>
                    </div>
                </template>
            </div>
            <div class="mt-3 flex gap-4 text-[11px] text-neutral-400">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-brand-600"></span> Penjualan mobil</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-white/70"></span> Aksesoris &amp; jasa</span>
            </div>

            {{-- Versi tabel untuk pembaca layar --}}
            <table class="sr-only">
                <caption>Omset 7 hari terakhir</caption>
                <thead><tr><th>Hari</th><th>Mobil</th><th>Aksesoris</th><th>Total</th></tr></thead>
                <tbody>@foreach ($series as $d)<tr><td>{{ $d['full'] }}</td><td>{{ rupiah($d['car']) }}</td><td>{{ rupiah($d['retail']) }}</td><td>{{ rupiah($d['car'] + $d['retail']) }}</td></tr>@endforeach</tbody>
            </table>
        </section>

        {{-- Stok menipis --}}
        <section class="a18-card p-5">
            <div class="flex items-center justify-between gap-2">
                <h2 class="a18-heading text-sm sm:text-base">Stok Menipis</h2>
                <a href="{{ route('showroom.products.index', ['low' => 1]) }}" class="text-xs font-bold text-brand-400 hover:text-brand-300">Lihat semua</a>
            </div>
            <ul class="mt-3 divide-y divide-white/10">
                @forelse ($lowStock as $p)
                    <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <span class="min-w-0"><span class="block truncate font-semibold text-white">{{ $p->name }}</span><span class="font-mono text-[10px] text-neutral-500">{{ $p->sku }}</span></span>
                        <span class="shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-bold {{ $p->stock <= 0 ? 'border-brand-500/40 bg-brand-600/20 text-brand-300' : 'border-amber-500/30 bg-amber-500/15 text-amber-300' }}">{{ $p->stock <= 0 ? 'Habis' : 'Sisa ' . $p->stock }}</span>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-neutral-500">Semua stok aksesoris aman.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Transaksi terbaru --}}
        <section class="a18-card p-5">
            <div class="flex items-center justify-between gap-2">
                <h2 class="a18-heading text-sm sm:text-base">Transaksi Terbaru</h2>
                <a href="{{ route('showroom.reports') }}" class="text-xs font-bold text-brand-400 hover:text-brand-300">Laporan lengkap</a>
            </div>
            <ul class="mt-3 divide-y divide-white/10">
                @forelse ($recent as $t)
                    <li>
                        <a href="{{ route('showroom.receipt', $t) }}" class="flex items-center justify-between gap-3 py-2.5 text-sm transition-colors hover:bg-white/5">
                            <span class="min-w-0">
                                <span class="block truncate font-semibold text-white">{{ $t->sale_type === 'car' ? ($t->items->first()?->name ?? 'Mobil') : $t->items->pluck('name')->implode(', ') }}</span>
                                <span class="text-[11px] text-neutral-500">{{ $t->code }} &middot; {{ $t->date->locale('id')->translatedFormat('j M H:i') }} &middot; {{ $methodLabel[$t->payment_method] ?? $t->payment_method }}</span>
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="block font-bold {{ $t->status === 'void' ? 'text-neutral-500 line-through' : 'text-white' }}">{{ rupiah($t->total) }}</span>
                                @if ($t->status === 'void')<span class="text-[10px] font-bold uppercase text-brand-400">Dibatalkan</span>@endif
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-neutral-500">Belum ada transaksi.</li>
                @endforelse
            </ul>
        </section>

        {{-- Aksesoris terlaris --}}
        <section class="a18-card p-5">
            <h2 class="a18-heading text-sm sm:text-base">Aksesoris Terlaris</h2>
            <ul class="mt-3 divide-y divide-white/10">
                @forelse ($topProducts as $i => $p)
                    <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <span class="flex min-w-0 items-center gap-3"><span class="font-display text-lg font-black text-white/20">{{ $i + 1 }}</span><span class="truncate font-semibold text-white">{{ $p->name }}</span></span>
                        <span class="shrink-0 text-right"><span class="block font-bold text-white">{{ (int) $p->sold }} terjual</span><span class="text-[11px] text-neutral-500">{{ rupiah($p->revenue) }}</span></span>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-neutral-500">Belum ada penjualan aksesoris.</li>
                @endforelse
            </ul>
        </section>

        {{-- Test drive mendatang --}}
        <section class="a18-card p-5">
            <div class="flex items-center justify-between gap-2">
                <h2 class="a18-heading text-sm sm:text-base">Test Drive Mendatang</h2>
                <a href="{{ route('showroom.cars.index', ['tab' => 'testdrive']) }}" class="text-xs font-bold text-brand-400 hover:text-brand-300">Kelola</a>
            </div>
            <ul class="mt-3 divide-y divide-white/10">
                @forelse ($upcomingDrives as $t)
                    <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <span class="min-w-0"><span class="block truncate font-semibold text-white">{{ $t->car_name }}</span><span class="text-[11px] text-neutral-500">{{ $t->customer_name }} &middot; {{ $day($t->date) }} &middot; {{ $t->time }}</span></span>
                        @if ($t->date->isToday())<span class="shrink-0 rounded-full bg-brand-600 px-2 py-0.5 text-[10px] font-black uppercase text-white">Hari Ini</span>@endif
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-neutral-500">Tidak ada jadwal test drive mendatang.</li>
                @endforelse
            </ul>
        </section>

        {{-- Permintaan jual --}}
        <section class="a18-card p-5">
            <div class="flex items-center justify-between gap-2">
                <h2 class="a18-heading text-sm sm:text-base">Permintaan Jual &amp; Tukar Tambah</h2>
                <a href="{{ route('showroom.cars.index', ['tab' => 'requests']) }}" class="text-xs font-bold text-brand-400 hover:text-brand-300">Kelola</a>
            </div>
            <ul class="mt-3 divide-y divide-white/10">
                @forelse ($newRequestList as $r)
                    @php [$sLabel, $sClass] = \App\Http\Controllers\Showroom\CarController::REQUEST_STATUS[$r->status]; @endphp
                    <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <span class="min-w-0"><span class="block truncate font-semibold text-white">{{ car_name($r, true) }}</span><span class="text-[11px] text-neutral-500">{{ $r->customer_name }} &middot; {{ $r->type === 'trade-in' ? 'Tukar tambah' : 'Jual langsung' }}</span></span>
                        <span class="shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase {{ $sClass }}">{{ $sLabel }}</span>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-neutral-500">Belum ada permintaan jual mobil.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
