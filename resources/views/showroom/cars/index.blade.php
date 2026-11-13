@extends('layouts.app')

@section('title', 'Stok Mobil')

@php
    $tabs = [
        'stock' => ['Stok Mobil', 'car-front', 0],
        'requests' => ['Jual & Tukar Tambah', 'arrow-left-right', $newRequestCount],
        'testdrive' => ['Test Drive', 'calendar-check', $pendingTestDrives],
    ];
    $statusLabel = ['available' => 'Tersedia', 'booked' => 'Dipesan', 'sold' => 'Terjual'];
    $statusClass = [
        'available' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
        'booked' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
        'sold' => 'bg-brand-600/20 text-brand-300 border-brand-500/40',
    ];
    $tel = fn ($n) => 'tel:' . preg_replace('/[^\d+]/', '', (string) $n);
    $day = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->locale('id')->translatedFormat('D, j M') : '-';
    $kpis = [
        ['car-front', 'Total Unit', $stats['total'], null, null, route('showroom.cars.index')],
        ['circle-check', 'Tersedia', $stats['available'], null, null, route('showroom.cars.index', ['status' => 'available'])],
        ['clipboard-list', 'Dipesan', $stats['booked'], null, null, route('showroom.cars.index', ['status' => 'booked'])],
        ['receipt', 'Terjual Bulan Ini', $stats['soldThisMonth'], null, null, route('showroom.cars.index', ['status' => 'sold'])],
        ['wallet', 'Nilai Stok', rupiah_short($stats['stockValue']), rupiah($stats['stockValue']), 'modal unit belum terjual', null],
        ['trending-up', 'Potensi Laba', rupiah_short($stats['potentialProfit']), rupiah($stats['potentialProfit']), 'jika semua terjual', null],
    ];
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    {{-- Banner --}}
    <div class="a18-stripes-soft flex flex-col gap-4 rounded-3xl p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Panel Showroom</span>
            <h1 class="a18-heading mt-1 text-2xl sm:text-3xl">Stok Mobil &amp; Permintaan</h1>
            <p class="mt-1.5 max-w-xl text-sm text-neutral-400">Kelola unit mobil bekas, permintaan jual/tukar tambah pelanggan, dan jadwal test drive.</p>
        </div>
        <a href="{{ route('showroom.cars.create') }}" class="a18-btn-primary shrink-0 px-5 py-3"><x-lucide-plus class="h-4 w-4" /> Tambah Mobil</a>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-brand-600/40 bg-brand-600/10 px-3 py-2 text-sm text-brand-300" role="alert">
            <p class="font-bold">Belum tersimpan. Perbaiki lalu coba lagi:</p>
            <ul class="mt-1 list-inside list-disc">@foreach (array_unique($errors->all()) as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- KPI --}}
    <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-6">
        @foreach ($kpis as [$icon, $label, $value, $title, $hint, $href])
            @if ($href)<a href="{{ $href }}" class="a18-card min-w-0 p-4 text-left transition-colors hover:border-brand-600">@else<div class="a18-card min-w-0 p-4 text-left">@endif
                <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-neutral-500">@svg('lucide-' . $icon, 'h-3.5 w-3.5 text-brand-500') {{ $label }}</div>
                <p class="mt-1.5 truncate font-display text-xl font-black text-white" @if ($title) title="{{ $title }}" @endif>{{ $value }}</p>
                @if ($hint)<p class="text-xs text-neutral-500">{{ $hint }}</p>@endif
            @if ($href)</a>@else</div>@endif
        @endforeach
    </div>

    {{-- Tab --}}
    <div class="mt-6 flex gap-2 overflow-x-auto border-b border-white/10 pb-px">
        @foreach ($tabs as $id => [$label, $icon, $badge])
            <a href="{{ route('showroom.cars.index', ['tab' => $id]) }}" @if ($tab === $id) aria-current="page" @endif
               class="relative flex shrink-0 items-center gap-2 rounded-t-xl px-4 py-2.5 text-sm font-bold transition-colors {{ $tab === $id ? 'bg-brand-600 text-white' : 'text-neutral-400 hover:bg-white/5 hover:text-white' }}">
                @svg('lucide-' . $icon, 'h-4 w-4') {{ $label }}
                @if ($badge > 0)<span class="rounded-full px-1.5 py-0.5 text-[10px] font-black {{ $tab === $id ? 'bg-white text-brand-700' : 'bg-brand-600 text-white' }}">{{ $badge }}</span>@endif
            </a>
        @endforeach
    </div>

    {{-- ============================== STOK ============================== --}}
    @if ($tab === 'stock')
        <div class="mt-6">
            <form method="GET" action="{{ route('showroom.cars.index') }}" class="flex flex-wrap items-end gap-3" onchange="this.requestSubmit()">
                <div class="min-w-[200px] flex-1">
                    <label for="inv-search" class="a18-label">Cari</label>
                    <div class="relative">
                        <x-lucide-search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" aria-hidden="true" />
                        <input id="inv-search" type="search" name="q" value="{{ $stock['q'] }}" placeholder="Model, kode stok, atau plat" class="a18-input pl-9">
                    </div>
                </div>
                <div>
                    <label for="inv-status" class="a18-label">Status</label>
                    <select id="inv-status" name="status" class="a18-input">
                        <option value="">Semua</option>
                        @foreach ($statusLabel as $k => $v)<option value="{{ $k }}" @selected($stock['status'] === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="inv-brand" class="a18-label">Merek</label>
                    <select id="inv-brand" name="brand" class="a18-input">
                        <option value="">Semua</option>
                        @foreach (config('auto18.brands') as $b)<option value="{{ $b }}" @selected($stock['brand'] === $b)>{{ $b }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="inv-sort" class="a18-label">Urutkan</label>
                    <select id="inv-sort" name="sort" class="a18-input">
                        @foreach (['newest' => 'Terbaru', 'price' => 'Harga Tertinggi', 'margin' => 'Margin Tertinggi', 'year' => 'Tahun Terbaru'] as $k => $v)<option value="{{ $k }}" @selected($stock['sort'] === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <button type="submit" class="a18-btn-outline">Terapkan</button>
            </form>

            <p class="mt-3 text-xs text-neutral-500">{{ $cars->count() }} unit ditampilkan</p>


            {{-- Tabel desktop --}}
            <div class="mt-3 hidden overflow-x-auto rounded-2xl border border-white/10 md:block">
                <table class="w-full min-w-[920px] text-sm">
                    <thead class="bg-ink-900 text-left text-[10px] uppercase tracking-wider text-neutral-500">
                        <tr>
                            <th class="px-4 py-3">Mobil</th><th class="px-4 py-3">Spesifikasi</th>
                            <th class="px-3 py-3 text-right">Harga Jual</th><th class="hidden px-3 py-3 text-right 2xl:table-cell">Modal</th>
                            <th class="px-3 py-3 text-right">Margin</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cars as $car)
                            @php $margin = $car->price - $car->buy_price; $pct = $car->buy_price > 0 ? $margin / $car->buy_price * 100 : 0; @endphp
                            <tr class="border-t border-white/10 transition-colors hover:bg-white/5">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @include('partials.car-image', ['car' => $car, 'class' => 'h-14 w-20 shrink-0 rounded-lg'])
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-white">{{ car_name($car) }}</p>
                                            <p class="font-mono text-[11px] text-neutral-500">{{ $car->code }} &middot; {{ $car->plate ?: 'tanpa plat' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-neutral-400">{{ $car->year }} &middot; {{ km($car->mileage) }}<br>{{ $car->transmission }} &middot; {{ $car->fuel }}</td>
                                <td class="px-3 py-3 text-right font-semibold text-white">{{ rupiah($car->price) }}</td>
                                <td class="hidden px-3 py-3 text-right text-neutral-400 2xl:table-cell">{{ rupiah($car->buy_price) }}</td>
                                <td class="px-3 py-3 text-right font-semibold {{ $margin >= 0 ? 'text-emerald-400' : 'text-brand-400' }}">{{ rupiah($margin) }}<span class="block text-[10px] font-normal text-neutral-500">{{ number_format($pct, 1, ',', '.') }}%</span></td>
                                <td class="px-3 py-3">@include('showroom.cars._status', ['car' => $car, 'statusClass' => $statusClass])</td>
                                <td class="px-3 py-3">@include('showroom.cars._actions', ['car' => $car])</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-neutral-500">Tidak ada mobil yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Kartu ponsel --}}
            <div class="mt-3 space-y-3 md:hidden">
                @forelse ($cars as $car)
                    @php $margin = $car->price - $car->buy_price; @endphp
                    <div class="a18-card p-4">
                        <div class="flex gap-3">
                            @include('partials.car-image', ['car' => $car, 'class' => 'h-16 w-24 shrink-0 rounded-lg'])
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold text-white">{{ car_name($car) }}</p>
                                <p class="font-mono text-[11px] text-neutral-500">{{ $car->code }}</p>
                                <p class="mt-1 text-xs text-neutral-400">{{ $car->year }} &middot; {{ km($car->mileage) }} &middot; {{ $car->transmission }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-sm">
                            <span class="font-bold text-white">{{ rupiah($car->price) }}</span>
                            <span class="{{ $margin >= 0 ? 'text-emerald-400' : 'text-brand-400' }}">Margin {{ rupiah($margin) }}</span>
                        </div>
                        <div class="mt-3">@include('showroom.cars._status', ['car' => $car, 'statusClass' => $statusClass])</div>
                        <div class="mt-3 border-t border-white/10 pt-3">@include('showroom.cars._actions', ['car' => $car])</div>
                    </div>
                @empty
                    <p class="a18-card p-8 text-center text-sm text-neutral-500">Tidak ada mobil yang cocok.</p>
                @endforelse
            </div>
        </div>
    @endif

    {{-- ============================== PERMINTAAN ============================== --}}
    @if ($tab === 'requests')
        <div class="mt-6">
            <form method="GET" action="{{ route('showroom.cars.index') }}" class="flex flex-wrap items-end gap-3" onchange="this.requestSubmit()">
                <input type="hidden" name="tab" value="requests">
                <div>
                    <label for="req-status" class="a18-label">Status</label>
                    <select id="req-status" name="rstatus" class="a18-input">
                        <option value="">Semua</option>
                        @foreach ($requestStatus as $k => [$l])<option value="{{ $k }}" @selected($rFilter['status'] === $k)>{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="req-type" class="a18-label">Jenis</label>
                    <select id="req-type" name="rtype" class="a18-input">
                        <option value="">Semua</option>
                        <option value="sell" @selected($rFilter['type'] === 'sell')>Jual Langsung</option>
                        <option value="trade-in" @selected($rFilter['type'] === 'trade-in')>Tukar Tambah</option>
                    </select>
                </div>
                <button type="submit" class="a18-btn-outline">Terapkan</button>
            </form>

            <div class="mt-4 space-y-4">
                @forelse ($requests as $r)
                    @php
                        [$sLabel, $sClass] = $requestStatus[$r->status] ?? [$r->status, ''];
                        $est = $r->system_estimate;
                        $offerDefault = $r->offer_price ?: $est['mid'];
                        $sellDefault = (int) (round($offerDefault * 1.12 / 500000) * 500000);
                    @endphp
                    <div class="a18-card p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs text-neutral-500">{{ $r->code }}</span>
                                    <span class="rounded-full border border-white/15 px-2 py-0.5 text-[10px] font-bold uppercase text-neutral-300">{{ $r->type === 'trade-in' ? 'Tukar Tambah' : 'Jual Langsung' }}</span>
                                    <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase {{ $sClass }}">{{ $sLabel }}</span>
                                </div>
                                <h3 class="mt-2 font-display text-base font-black uppercase tracking-wide text-white">{{ car_name($r, true) }}</h3>
                                <p class="mt-1 text-xs text-neutral-400">{{ $r->transmission }} &middot; {{ km($r->mileage) }} &middot; {{ $r->color }} &middot; {{ $r->location }} &middot; Kondisi {{ $r->condition }}</p>
                            </div>
                            <div class="text-right text-xs text-neutral-400">
                                <p class="font-bold text-white">{{ $r->customer_name }}</p>
                                <a href="{{ $tel($r->phone) }}" class="inline-flex items-center gap-1 hover:text-white"><x-lucide-phone class="h-3 w-3" aria-hidden="true" /> {{ $r->phone }}</a>
                                <p class="mt-1 text-neutral-500">Masuk {{ $r->created_at->locale('id')->translatedFormat('j M Y H:i') }}</p>
                            </div>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-white/10 pt-4 text-xs sm:grid-cols-4">
                            <div><dt class="text-neutral-500">Estimasi Sistem</dt><dd class="font-semibold text-white">{{ rupiah($est['low']) }} &ndash; {{ rupiah($est['high']) }}</dd></div>
                            <div><dt class="text-neutral-500">Harga Diharapkan</dt><dd class="font-semibold text-white">{{ $r->asking_price > 0 ? rupiah($r->asking_price) : '-' }}</dd></div>
                            <div><dt class="text-neutral-500">Jadwal Inspeksi</dt><dd class="font-semibold text-white">{{ $day($r->inspection_date) }} &middot; {{ $r->inspection_time ?: '-' }}</dd></div>
                            <div><dt class="text-neutral-500">Penawaran</dt><dd class="font-semibold text-white">{{ $r->offer_price > 0 ? rupiah($r->offer_price) : '-' }}</dd></div>
                        </dl>

                        @if ($r->tradeInCar)<p class="mt-3 text-xs text-neutral-400">Ingin ditukar dengan <a href="{{ route('cars.show', $r->tradeInCar) }}" class="font-bold text-brand-400 underline hover:text-brand-300">{{ car_name($r->tradeInCar, true) }}</a></p>@endif
                        @if ($r->notes)<p class="mt-2 text-xs italic text-neutral-500">"{{ $r->notes }}"</p>@endif
                        @if ($r->purchasedCar)<p class="mt-2 text-xs text-emerald-400">Sudah masuk stok sebagai <a href="{{ route('cars.show', $r->purchasedCar) }}" class="underline">{{ $r->purchasedCar->code }}</a></p>@endif

                        <div class="mt-4 border-t border-white/10 pt-4">
                            @if ($r->status === 'new')
                                <form method="POST" action="{{ route('showroom.requests.update', $r) }}" class="flex flex-wrap items-end gap-3">@csrf @method('PATCH')
                                    <input type="hidden" name="action" value="schedule">
                                    <div><label class="a18-label" for="insp-date-{{ $r->id }}">Tanggal Inspeksi</label><input id="insp-date-{{ $r->id }}" type="date" name="inspection_date" min="{{ today()->toDateString() }}" value="{{ $r->inspection_date?->toDateString() ?: today()->toDateString() }}" class="a18-input"></div>
                                    <div><label class="a18-label" for="insp-time-{{ $r->id }}">Jam</label><input id="insp-time-{{ $r->id }}" type="time" name="inspection_time" value="{{ $r->inspection_time ?: '10:00' }}" class="a18-input"></div>
                                    <button type="submit" class="a18-btn-primary"><x-lucide-calendar-check class="h-4 w-4" /> Jadwalkan Inspeksi</button>
                                </form>
                            @elseif ($r->status === 'scheduled')
                                <form method="POST" action="{{ route('showroom.requests.update', $r) }}" class="flex flex-wrap items-end gap-3">@csrf @method('PATCH')
                                    <input type="hidden" name="action" value="inspect">
                                    <div><label class="a18-label" for="offer-{{ $r->id }}">Penawaran Setelah Inspeksi (Rp)</label><input id="offer-{{ $r->id }}" type="number" name="offer_price" min="0" step="500000" value="{{ $offerDefault }}" class="a18-input"></div>
                                    <button type="submit" class="a18-btn-primary"><x-lucide-circle-check class="h-4 w-4" /> Simpan Hasil Inspeksi</button>
                                </form>
                            @elseif ($r->status === 'inspected')
                                <form method="POST" action="{{ route('showroom.requests.purchase', $r) }}" class="flex flex-wrap items-end gap-3">@csrf
                                    <div><label class="a18-label" for="buy-{{ $r->id }}">Harga Beli (Rp)</label><input id="buy-{{ $r->id }}" type="number" name="offer_price" min="0" step="500000" value="{{ $offerDefault }}" class="a18-input"></div>
                                    <div><label class="a18-label" for="sell-{{ $r->id }}">Harga Jual Nanti (Rp)</label><input id="sell-{{ $r->id }}" type="number" name="sell_price" min="0" step="500000" value="{{ $sellDefault }}" class="a18-input"></div>
                                    <button type="submit" class="a18-btn-primary"><x-lucide-wallet class="h-4 w-4" /> Beli &amp; Masukkan Stok</button>
                                </form>
                            @endif

                            <div class="mt-3 flex flex-wrap gap-2">
                                @if (in_array($r->status, ['new', 'scheduled', 'inspected'], true))
                                    <form method="POST" action="{{ route('showroom.requests.update', $r) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="reject">
                                        <button type="submit" class="a18-btn-ghost text-xs"><x-lucide-circle-x class="h-4 w-4" /> Tolak</button></form>
                                @endif
                                <form method="POST" action="{{ route('showroom.requests.destroy', $r) }}" onsubmit="return confirm('Hapus permintaan {{ $r->code }}?')">@csrf @method('DELETE')
                                    <button type="submit" class="a18-btn-ghost text-xs text-neutral-500 hover:text-brand-400"><x-lucide-trash-2 class="h-4 w-4" /> Hapus</button></form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="a18-card p-10 text-center text-sm text-neutral-500">Belum ada permintaan yang cocok.</p>
                @endforelse
            </div>
        </div>
    @endif

    {{-- ============================== TEST DRIVE ============================== --}}
    @if ($tab === 'testdrive')
        <div class="mt-6">
            <form method="GET" action="{{ route('showroom.cars.index') }}" class="flex flex-wrap items-end gap-3" onchange="this.requestSubmit()">
                <input type="hidden" name="tab" value="testdrive">
                <div>
                    <label for="td-filter" class="a18-label">Status</label>
                    <select id="td-filter" name="tstatus" class="a18-input">
                        <option value="">Semua</option>
                        @foreach ($testDriveStatus as $k => [$l])<option value="{{ $k }}" @selected($tdFilter['status'] === $k)>{{ $l }}</option>@endforeach
                    </select>
                </div>
                <label class="flex items-center gap-2 pb-2.5 text-sm text-neutral-300"><input type="checkbox" name="today" value="1" @checked($tdFilter['today']) class="h-4 w-4 accent-brand-600"> Hanya hari ini</label>
                <button type="submit" class="a18-btn-outline">Terapkan</button>
            </form>

            <div class="mt-4 space-y-3">
                @forelse ($testDrives as $t)
                    @php [$sLabel, $sClass] = $testDriveStatus[$t->status] ?? [$t->status, '']; $isToday = $t->date->isSameDay($today); @endphp
                    <div class="a18-card p-4 {{ $isToday ? 'border-brand-600/60' : '' }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs text-neutral-500">{{ $t->code }}</span>
                                    <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase {{ $sClass }}">{{ $sLabel }}</span>
                                    @if ($isToday)<span class="rounded-full bg-brand-600 px-2 py-0.5 text-[10px] font-black uppercase text-white">Hari Ini</span>@endif
                                </div>
                                <p class="mt-1.5 font-bold text-white">
                                    @if ($t->car_id)<a href="{{ route('cars.show', $t->car_id) }}" class="underline decoration-brand-600 underline-offset-4 hover:text-brand-400">{{ $t->car_name }}</a>@else{{ $t->car_name }}@endif
                                </p>
                                <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-neutral-400">
                                    <span class="inline-flex items-center gap-1"><x-lucide-calendar-check class="h-3.5 w-3.5" aria-hidden="true" /> {{ $day($t->date) }} &middot; {{ $t->time }}</span>
                                    <span class="inline-flex items-center gap-1"><x-lucide-map-pin class="h-3.5 w-3.5" aria-hidden="true" /> {{ $t->location === 'rumah' ? 'Di rumah' . ($t->address ? ' · ' . $t->address : '') : 'Di showroom' }}</span>
                                </p>
                                @if ($t->notes)<p class="mt-1 text-xs italic text-neutral-500">"{{ $t->notes }}"</p>@endif
                            </div>
                            <div class="text-right text-xs text-neutral-400">
                                <p class="font-bold text-white">{{ $t->customer_name }}</p>
                                <a href="{{ $tel($t->phone) }}" class="inline-flex items-center gap-1 hover:text-white"><x-lucide-phone class="h-3 w-3" aria-hidden="true" /> {{ $t->phone }}</a>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 border-t border-white/10 pt-3">
                            @if ($t->status === 'pending')
                                <form method="POST" action="{{ route('showroom.test-drives.update', $t) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="confirmed"><button type="submit" class="a18-btn-primary px-3 py-1.5 text-xs">Konfirmasi</button></form>
                            @endif
                            @if (in_array($t->status, ['pending', 'confirmed'], true))
                                <form method="POST" action="{{ route('showroom.test-drives.update', $t) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="done"><button type="submit" class="a18-btn-outline px-3 py-1.5 text-xs">Selesai</button></form>
                                <form method="POST" action="{{ route('showroom.test-drives.update', $t) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><button type="submit" class="a18-btn-ghost text-xs">Batalkan</button></form>
                            @endif
                            <form method="POST" action="{{ route('showroom.test-drives.destroy', $t) }}" onsubmit="return confirm('Hapus booking {{ $t->code }}?')">@csrf @method('DELETE')
                                <button type="submit" class="a18-btn-ghost text-xs text-neutral-500 hover:text-brand-400"><x-lucide-trash-2 class="h-4 w-4" /> Hapus</button></form>
                        </div>
                    </div>
                @empty
                    <p class="a18-card p-10 text-center text-sm text-neutral-500">Belum ada jadwal test drive.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
@endsection
