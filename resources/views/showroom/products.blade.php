@extends('layouts.app')

@section('title', 'Produk & Stok')

@php
    $blank = ['id' => null, 'sku' => '', 'name' => '', 'category_id' => '', 'unit' => 'pcs', 'buy_price' => '', 'sell_price' => '', 'stock' => 0, 'min_stock' => 5, 'barcode' => ''];
    $updateUrl = route('showroom.products.update', '__ID__');
    $adjustUrl = route('showroom.products.adjust', '__ID__');
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
     x-data="{ form: null, adjust: null, catOpen: false, rupiah: window.auto18.rupiah,
               get margin() { return this.form ? (Number(this.form.sell_price) || 0) - (Number(this.form.buy_price) || 0) : 0 },
               openNew() { this.form = JSON.parse(JSON.stringify(@js($blank))) },
               openEdit(p) { this.form = JSON.parse(JSON.stringify(p)) } }"
     x-on:keydown.escape.window="form = null; adjust = null; catOpen = false">

    <div class="a18-stripes-soft flex flex-col gap-4 rounded-3xl p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Panel Showroom</span>
            <h1 class="a18-heading mt-1 text-2xl sm:text-3xl">Produk Aksesoris &amp; Stok</h1>
            <p class="mt-1.5 max-w-xl text-sm text-neutral-400">Kelola barang dan jasa yang dijual di kasir: harga, margin, dan jumlah stok.</p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <button type="button" x-on:click="catOpen = true" class="a18-btn-outline"><x-lucide-tags class="h-4 w-4" /> Kategori</button>
            <button type="button" x-on:click="openNew()" class="a18-btn-primary px-5 py-3"><x-lucide-plus class="h-4 w-4" /> Tambah Produk</button>
        </div>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-brand-600/40 bg-brand-600/10 px-3 py-2 text-sm text-brand-300" role="alert">
            <p class="font-bold">Belum tersimpan. Perbaiki lalu coba lagi:</p>
            <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="a18-card p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-neutral-500">Total Produk</p><p class="mt-1.5 font-display text-xl font-black text-white">{{ $stats['total'] }}</p></div>
        <a href="{{ route('showroom.products.index', ['low' => 1]) }}" class="a18-card p-4 transition-colors hover:border-brand-600"><p class="text-[10px] font-bold uppercase tracking-wider text-neutral-500">Perlu Restok</p><p class="mt-1.5 font-display text-xl font-black {{ $stats['low'] ? 'text-amber-400' : 'text-white' }}">{{ $stats['low'] }}</p></a>
        <div class="a18-card p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-neutral-500">Nilai Stok (Modal)</p><p class="mt-1.5 truncate font-display text-xl font-black text-white" title="{{ rupiah($stats['stockValue']) }}">{{ rupiah_short($stats['stockValue']) }}</p></div>
        <div class="a18-card p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-neutral-500">Nilai Jual Stok</p><p class="mt-1.5 truncate font-display text-xl font-black text-white" title="{{ rupiah($stats['retailValue']) }}">{{ rupiah_short($stats['retailValue']) }}</p></div>
    </div>

    <form method="GET" action="{{ route('showroom.products.index') }}" class="mt-6 flex flex-wrap items-end gap-3" onchange="this.requestSubmit()">
        <div class="min-w-[200px] flex-1">
            <label for="p-q" class="a18-label">Cari</label>
            <div class="relative"><x-lucide-search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" aria-hidden="true" />
                <input id="p-q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari nama produk atau SKU..." class="a18-input pl-9"></div>
        </div>
        <div>
            <label for="p-cat" class="a18-label">Kategori</label>
            <select id="p-cat" name="category" class="a18-input">
                <option value="">Semua</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected($filters['category'] === $c->id)>{{ $c->name }} ({{ $c->products_count }})</option>@endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2.5 text-sm text-neutral-300"><input type="checkbox" name="low" value="1" @checked($filters['low']) class="h-4 w-4 accent-brand-600"> Hanya yang perlu restok</label>
        <button type="submit" class="a18-btn-outline">Terapkan</button>
    </form>

    <p class="mt-3 text-xs text-neutral-500">{{ $products->count() }} produk ditampilkan</p>

    <div class="mt-3 overflow-x-auto rounded-2xl border border-white/10">
        <table class="w-full min-w-[860px] text-sm">
            <thead class="bg-ink-900 text-left text-[10px] uppercase tracking-wider text-neutral-500">
                <tr>
                    <th class="px-4 py-3 font-bold">SKU &amp; Barcode</th><th class="px-4 py-3 font-bold">Nama Produk</th><th class="px-4 py-3 font-bold">Kategori</th>
                    <th class="px-4 py-3 text-right font-bold">Harga Modal</th><th class="px-4 py-3 text-right font-bold">Harga Jual</th><th class="px-4 py-3 text-right font-bold">Margin Laba</th>
                    <th class="px-4 py-3 text-center font-bold">Stok</th><th class="px-4 py-3 text-center font-bold">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $p)
                    @php
                        $margin = $p->sell_price - $p->buy_price;
                        $pct = $p->buy_price > 0 ? $margin / $p->buy_price * 100 : 0;
                        $js = ['id' => $p->id, 'sku' => $p->sku, 'name' => $p->name, 'category_id' => $p->category_id ?? '', 'unit' => $p->unit, 'buy_price' => (int) $p->buy_price, 'sell_price' => (int) $p->sell_price, 'stock' => (int) $p->stock, 'min_stock' => (int) $p->min_stock, 'barcode' => $p->barcode ?? ''];
                    @endphp
                    <tr class="border-t border-white/10 transition-colors hover:bg-white/5">
                        <td class="px-4 py-3"><span class="font-mono text-xs text-white">{{ $p->sku }}</span><span class="block font-mono text-[10px] text-neutral-500">{{ $p->barcode ?: '-' }}</span></td>
                        <td class="px-4 py-3 font-semibold text-white">{{ $p->name }}</td>
                        <td class="px-4 py-3 text-xs text-neutral-400">{{ $p->category?->name ?: 'Tanpa kategori' }}</td>
                        <td class="px-4 py-3 text-right text-neutral-400">{{ rupiah($p->buy_price) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-white">{{ rupiah($p->sell_price) }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $margin >= 0 ? 'text-emerald-400' : 'text-brand-400' }}">{{ rupiah($margin) }}<span class="block text-[10px] font-normal text-neutral-500">{{ number_format($pct, 1, ',', '.') }}%</span></td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" x-on:click="adjust = { id: {{ $p->id }}, name: @js($p->name), stock: {{ (int) $p->stock }}, unit: @js($p->unit), delta: '' }" title="Klik untuk ubah stok manual"
                                    class="rounded-lg border px-2.5 py-1 text-xs font-bold transition-colors hover:border-brand-500 {{ $p->stock <= 0 ? 'border-brand-500/40 bg-brand-600/20 text-brand-300' : ($p->isLowStock() ? 'border-amber-500/30 bg-amber-500/15 text-amber-300' : 'border-white/15 text-white') }}">
                                {{ $p->stock }} {{ $p->unit }}
                            </button>
                            @if ($p->isLowStock())<span class="mt-1 block text-[10px] font-bold text-amber-400">Stok menipis</span>@endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button type="button" x-on:click="openEdit(@js($js))" title="Ubah produk" aria-label="Ubah produk" class="a18-btn-ghost px-2"><x-lucide-pencil class="h-4 w-4" /></button>
                                <form method="POST" action="{{ route('showroom.products.destroy', $p) }}" onsubmit="return confirm(@js('Yakin ingin menghapus produk "' . $p->name . '"?'))">@csrf @method('DELETE')
                                    <button type="submit" title="Hapus produk" aria-label="Hapus produk" class="a18-btn-ghost px-2 text-neutral-500 hover:text-brand-400"><x-lucide-trash-2 class="h-4 w-4" /></button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-sm text-neutral-500">Tidak ada produk yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal tambah / ubah produk --}}
    <div x-show="form" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/80 p-4 backdrop-blur-sm" x-on:mousedown.self="form = null">
        <template x-if="form">
            <form method="POST" x-bind:action="form.id ? @js($updateUrl).replace('__ID__', form.id) : @js(route('showroom.products.store'))" role="dialog" aria-modal="true" aria-label="Form produk" class="my-4 w-full max-w-2xl overflow-hidden rounded-3xl border border-white/10 bg-ink-800">
                @csrf
                <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
                <div class="flex items-center justify-between gap-3 border-b border-white/10 px-5 py-3.5">
                    <h2 class="a18-heading text-base" x-text="form.id ? 'Ubah Produk' : 'Tambah Produk Baru'"></h2>
                    <button type="button" x-on:click="form = null" aria-label="Tutup" class="a18-btn-ghost px-2"><x-lucide-x class="h-5 w-5" /></button>
                </div>
                <div class="grid gap-3 p-5 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="a18-label" for="pf-name">Nama Produk</label><input id="pf-name" name="name" x-model="form.name" required class="a18-input" placeholder="Contoh: Oli Mesin Shell Helix HX7 4L"></div>
                    <div><label class="a18-label" for="pf-sku">SKU / Kode</label><input id="pf-sku" name="sku" x-model="form.sku" required class="a18-input" placeholder="OLI-1001"></div>
                    <div><label class="a18-label" for="pf-cat">Kategori</label>
                        <select id="pf-cat" name="category_id" x-model="form.category_id" class="a18-input"><option value="">Tanpa kategori</option>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                    <div><label class="a18-label" for="pf-buy">Harga Modal (Rp)</label><input id="pf-buy" name="buy_price" type="number" min="0" x-model="form.buy_price" required class="a18-input" placeholder="0"></div>
                    <div><label class="a18-label" for="pf-sell">Harga Jual (Rp)</label><input id="pf-sell" name="sell_price" type="number" min="0" x-model="form.sell_price" required class="a18-input" placeholder="0"></div>
                    <p class="text-sm text-neutral-400 sm:col-span-2">Margin per satuan: <span class="font-bold" x-bind:class="margin >= 0 ? 'text-emerald-400' : 'text-brand-400'" x-text="rupiah(margin)"></span></p>
                    <div><label class="a18-label" for="pf-stock">Stok</label><input id="pf-stock" name="stock" type="number" min="0" x-model="form.stock" required class="a18-input"></div>
                    <div><label class="a18-label" for="pf-min">Min. Stok</label><input id="pf-min" name="min_stock" type="number" min="0" x-model="form.min_stock" class="a18-input" placeholder="5"></div>
                    <div><label class="a18-label" for="pf-unit">Satuan</label><input id="pf-unit" name="unit" x-model="form.unit" class="a18-input" placeholder="pcs, botol, set"></div>
                    <div><label class="a18-label" for="pf-barcode">Barcode (opsional)</label><input id="pf-barcode" name="barcode" x-model="form.barcode" class="a18-input" placeholder="899180001001"></div>
                </div>
                <div class="flex justify-end gap-2 border-t border-white/10 bg-ink-900 p-4">
                    <button type="button" x-on:click="form = null" class="a18-btn-ghost">Batal</button>
                    <button type="submit" class="a18-btn-primary"><x-lucide-save class="h-4 w-4" /> Simpan</button>
                </div>
            </form>
        </template>
    </div>

    {{-- Modal ubah stok manual --}}
    <div x-show="adjust" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm" x-on:mousedown.self="adjust = null">
        <template x-if="adjust">
            <form method="POST" x-bind:action="@js($adjustUrl).replace('__ID__', adjust.id)" role="dialog" aria-modal="true" aria-label="Ubah stok manual" class="w-full max-w-md overflow-hidden rounded-3xl border border-white/10 bg-ink-800">
                @csrf
                <div class="border-b border-white/10 px-5 py-3.5"><h2 class="a18-heading text-base">Ubah Stok Manual</h2><p class="mt-0.5 text-xs text-neutral-400" x-text="adjust.name"></p></div>
                <div class="space-y-3 p-5">
                    <p class="text-sm text-neutral-300">Stok sekarang: <span class="font-bold text-white" x-text="adjust.stock + ' ' + adjust.unit"></span></p>
                    <div><label class="a18-label" for="adj-delta">Tambah / Kurangi Jumlah Stok</label>
                        <input id="adj-delta" name="delta" type="number" step="1" x-model="adjust.delta" required class="a18-input" placeholder="Misal 12 untuk menambah, -2 untuk mengurangi"></div>
                    <p class="text-xs text-neutral-500">Stok setelah disimpan: <span class="font-bold text-white" x-text="Math.max(0, adjust.stock + (parseInt(adjust.delta) || 0))"></span></p>
                </div>
                <div class="flex justify-end gap-2 border-t border-white/10 bg-ink-900 p-4">
                    <button type="button" x-on:click="adjust = null" class="a18-btn-ghost">Batal</button>
                    <button type="submit" class="a18-btn-primary">Simpan Stok</button>
                </div>
            </form>
        </template>
    </div>

    {{-- Modal kategori --}}
    <div x-show="catOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm" x-on:mousedown.self="catOpen = false">
        <div role="dialog" aria-modal="true" aria-label="Kelola kategori" class="w-full max-w-md overflow-hidden rounded-3xl border border-white/10 bg-ink-800">
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-3.5"><h2 class="a18-heading text-base">Kategori Produk</h2>
                <button type="button" x-on:click="catOpen = false" aria-label="Tutup" class="a18-btn-ghost px-2"><x-lucide-x class="h-5 w-5" /></button></div>
            <div class="space-y-3 p-5">
                <form method="POST" action="{{ route('showroom.categories.store') }}" class="flex gap-2">@csrf
                    <input name="name" required maxlength="60" aria-label="Nama kategori baru" placeholder="Nama kategori baru" class="a18-input"><button type="submit" class="a18-btn-primary shrink-0">Tambah</button></form>
                <ul class="max-h-64 divide-y divide-white/10 overflow-y-auto">
                    @forelse ($categories as $c)
                        <li class="flex items-center justify-between gap-2 py-2 text-sm"><span class="text-white">{{ $c->name }} <span class="text-xs text-neutral-500">({{ $c->products_count }} produk)</span></span>
                            <form method="POST" action="{{ route('showroom.categories.destroy', $c) }}" onsubmit="return confirm(@js('Hapus kategori "' . $c->name . '"? Produknya tetap ada, hanya menjadi tanpa kategori.'))">@csrf @method('DELETE')
                                <button type="submit" aria-label="Hapus kategori {{ $c->name }}" class="a18-btn-ghost px-2 text-neutral-500 hover:text-brand-400"><x-lucide-trash-2 class="h-4 w-4" /></button></form></li>
                    @empty
                        <li class="py-4 text-center text-sm text-neutral-500">Belum ada kategori.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
