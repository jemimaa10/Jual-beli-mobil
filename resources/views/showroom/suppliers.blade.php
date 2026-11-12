@extends('layouts.app')

@section('title', 'Suplier')

@php
    $blank = ['id' => null, 'name' => '', 'contact_person' => '', 'phone' => '', 'address' => ''];
    $updateUrl = route('showroom.suppliers.update', '__ID__');
    $tel = fn ($n) => 'tel:' . preg_replace('/[^\d+]/', '', (string) $n);
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
     x-data="{ form: null, stockIn: false, supplierId: '', products: @js($products), rows: [{ product_id: '', qty: 1, buy_price: '' }], rupiah: window.auto18.rupiah,
               product(id) { return this.products.find(p => String(p.id) === String(id)) },
               pick(row) { const p = this.product(row.product_id); row.buy_price = p ? p.buy_price : '' },
               get invoiceTotal() { return this.rows.reduce((s, r) => s + (Number(r.qty) || 0) * (Number(r.buy_price) || 0), 0) },
               openStockIn(id) { this.supplierId = id || ''; this.rows = [{ product_id: '', qty: 1, buy_price: '' }]; this.stockIn = true } }"
     x-on:keydown.escape.window="form = null; stockIn = false">

    <div class="a18-stripes-soft flex flex-col gap-4 rounded-3xl p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Panel Showroom</span>
            <h1 class="a18-heading mt-1 text-2xl sm:text-3xl">Suplier &amp; Stok Masuk</h1>
            <p class="mt-1.5 max-w-xl text-sm text-neutral-400">Data pemasok oli, aki, ban, dan aksesoris, serta pencatatan barang yang masuk ke stok.</p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <button type="button" x-on:click="openStockIn('')" class="a18-btn-outline"><x-lucide-package-plus class="h-4 w-4" /> Catat Barang Masuk</button>
            <button type="button" x-on:click="form = JSON.parse(JSON.stringify(@js($blank)))" class="a18-btn-primary px-5 py-3"><x-lucide-plus class="h-4 w-4" /> Tambah Suplier</button>
        </div>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-brand-600/40 bg-brand-600/10 px-3 py-2 text-sm text-brand-300" role="alert">
            <p class="font-bold">Belum tersimpan. Perbaiki lalu coba lagi:</p>
            <ul class="mt-1 list-inside list-disc">@foreach (array_unique($errors->all()) as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($suppliers as $s)
            @php $js = ['id' => $s->id, 'name' => $s->name, 'contact_person' => $s->contact_person ?? '', 'phone' => $s->phone ?? '', 'address' => $s->address ?? '']; @endphp
            <div class="a18-card flex flex-col p-5">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="font-display text-base font-black uppercase leading-tight tracking-wide text-white">{{ $s->name }}</h2>
                        @if ($s->contact_person)<p class="mt-1 text-xs text-neutral-400">{{ $s->contact_person }}</p>@endif
                    </div>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-600/20 text-brand-400"><x-lucide-truck class="h-5 w-5" /></span>
                </div>
                <ul class="mt-3 space-y-1.5 text-xs text-neutral-400">
                    @if ($s->phone)<li class="flex items-center gap-1.5"><x-lucide-phone class="h-3.5 w-3.5 text-brand-500" /><a href="{{ $tel($s->phone) }}" class="hover:text-white">{{ $s->phone }}</a></li>@endif
                    @if ($s->address)<li class="flex items-start gap-1.5"><x-lucide-map-pin class="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand-500" /><span>{{ $s->address }}</span></li>@endif
                </ul>
                @if (count($s->categories ?? []))
                    <div class="mt-3 flex flex-wrap gap-1.5">@foreach ($s->categories as $c)<span class="rounded-full border border-white/15 px-2 py-0.5 text-[10px] text-neutral-300">{{ $c }}</span>@endforeach</div>
                @endif
                @if ($s->products_count)<p class="mt-3 text-[11px] text-neutral-500">{{ $s->products_count }} produk terakhir dipasok dari suplier ini</p>@endif
                <div class="mt-auto flex flex-wrap items-center gap-1 border-t border-white/10 pt-3">
                    <button type="button" x-on:click="openStockIn({{ $s->id }})" class="a18-btn-primary px-3 py-1.5 text-xs"><x-lucide-package-plus class="h-3.5 w-3.5" /> Barang Masuk</button>
                    <button type="button" x-on:click="form = JSON.parse(JSON.stringify(@js($js)))" title="Ubah suplier" aria-label="Ubah suplier" class="a18-btn-ghost px-2"><x-lucide-pencil class="h-4 w-4" /></button>
                    <form method="POST" action="{{ route('showroom.suppliers.destroy', $s) }}" onsubmit="return confirm(@js('Hapus suplier "' . $s->name . '"?'))">@csrf @method('DELETE')
                        <button type="submit" title="Hapus suplier" aria-label="Hapus suplier" class="a18-btn-ghost px-2 text-neutral-500 hover:text-brand-400"><x-lucide-trash-2 class="h-4 w-4" /></button></form>
                </div>
            </div>
        @empty
            <p class="a18-card p-10 text-center text-sm text-neutral-500 sm:col-span-2 lg:col-span-3">Belum ada data suplier.</p>
        @endforelse
    </div>

    {{-- Modal suplier --}}
    <div x-show="form" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/80 p-4 backdrop-blur-sm" x-on:mousedown.self="form = null">
        <template x-if="form">
            <form method="POST" x-bind:action="form.id ? @js($updateUrl).replace('__ID__', form.id) : @js(route('showroom.suppliers.store'))" role="dialog" aria-modal="true" aria-label="Form suplier" class="w-full max-w-lg overflow-hidden rounded-3xl border border-white/10 bg-ink-800">
                @csrf
                <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
                <div class="flex items-center justify-between border-b border-white/10 px-5 py-3.5"><h2 class="a18-heading text-base" x-text="form.id ? 'Ubah Data Suplier' : 'Tambah Data Suplier'"></h2>
                    <button type="button" x-on:click="form = null" aria-label="Tutup" class="a18-btn-ghost px-2"><x-lucide-x class="h-5 w-5" /></button></div>
                <div class="space-y-3 p-5">
                    <div><label class="a18-label" for="sf-name">Nama Suplier / Perusahaan</label><input id="sf-name" name="name" x-model="form.name" required class="a18-input" placeholder="Contoh: PT Sinar Pelumas Nusantara"></div>
                    <div><label class="a18-label" for="sf-pic">Nama Sales / PIC</label><input id="sf-pic" name="contact_person" x-model="form.contact_person" class="a18-input" placeholder="Contoh: Bpk. Rudi Hartono"></div>
                    <div><label class="a18-label" for="sf-phone">Nomor Telepon / WhatsApp</label><input id="sf-phone" name="phone" type="tel" x-model="form.phone" class="a18-input" placeholder="0812-7788-1801"></div>
                    <div><label class="a18-label" for="sf-address">Alamat Gudang / Kantor</label><input id="sf-address" name="address" x-model="form.address" class="a18-input" placeholder="Jl. Daan Mogot KM 12, Jakarta Barat"></div>
                </div>
                <div class="flex justify-end gap-2 border-t border-white/10 bg-ink-900 p-4">
                    <button type="button" x-on:click="form = null" class="a18-btn-ghost">Batal</button>
                    <button type="submit" class="a18-btn-primary"><x-lucide-save class="h-4 w-4" /> Simpan</button>
                </div>
            </form>
        </template>
    </div>

    {{-- Modal barang masuk --}}
    <div x-show="stockIn" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/80 p-4 backdrop-blur-sm" x-on:mousedown.self="stockIn = false">
        <form method="POST" action="{{ route('showroom.stock-in') }}" role="dialog" aria-modal="true" aria-label="Catat barang masuk" class="my-4 w-full max-w-2xl overflow-hidden rounded-3xl border border-white/10 bg-ink-800">
            @csrf
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-3.5"><h2 class="a18-heading text-base">Catat Barang Masuk</h2>
                <button type="button" x-on:click="stockIn = false" aria-label="Tutup" class="a18-btn-ghost px-2"><x-lucide-x class="h-5 w-5" /></button></div>
            <div class="space-y-4 p-5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="a18-label" for="si-supplier">Pilih Suplier</label>
                        <select id="si-supplier" name="supplier_id" x-model="supplierId" required class="a18-input">
                            <option value="">&mdash; Pilih suplier &mdash;</option>
                            @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select></div>
                    <div><label class="a18-label" for="si-invoice">No. Faktur (Opsional)</label><input id="si-invoice" name="invoice_no" class="a18-input" placeholder="INV-A18-0001"></div>
                </div>

                <div>
                    <span class="a18-label">Produk yang Masuk</span>
                    <div class="space-y-2">
                        <template x-for="(row, i) in rows" :key="i">
                            <div class="grid grid-cols-12 items-end gap-2 rounded-xl border border-white/10 bg-ink-900 p-3">
                                <div class="col-span-12 sm:col-span-6">
                                    <label class="a18-label" x-bind:for="'si-p-' + i">Produk</label>
                                    <select x-bind:id="'si-p-' + i" x-bind:name="'items[' + i + '][product_id]'" x-model="row.product_id" x-on:change="pick(row)" required class="a18-input">
                                        <option value="">&mdash; Pilih produk &mdash;</option>
                                        <template x-for="p in products" :key="p.id"><option x-bind:value="p.id" x-text="p.label + ' · stok ' + p.stock" x-bind:selected="String(p.id) === String(row.product_id)"></option></template>
                                    </select>
                                </div>
                                <div class="col-span-4 sm:col-span-2"><label class="a18-label" x-bind:for="'si-q-' + i">Jumlah Masuk</label><input x-bind:id="'si-q-' + i" type="number" min="1" step="1" x-bind:name="'items[' + i + '][qty]'" x-model="row.qty" required class="a18-input"></div>
                                <div class="col-span-6 sm:col-span-3"><label class="a18-label" x-bind:for="'si-b-' + i">Harga Modal / Satuan</label><input x-bind:id="'si-b-' + i" type="number" min="0" x-bind:name="'items[' + i + '][buy_price]'" x-model="row.buy_price" class="a18-input" placeholder="Tetap"></div>
                                <div class="col-span-2 sm:col-span-1"><button type="button" x-on:click="rows.splice(i, 1)" x-bind:disabled="rows.length === 1" aria-label="Hapus baris" class="a18-btn-ghost w-full px-2 text-neutral-500 hover:text-brand-400 disabled:opacity-30"><x-lucide-trash-2 class="h-4 w-4" /></button></div>
                            </div>
                        </template>
                    </div>
                    <button type="button" x-on:click="rows.push({ product_id: '', qty: 1, buy_price: '' })" class="a18-btn-ghost mt-2 text-xs"><x-lucide-plus class="h-4 w-4" /> Tambah baris produk</button>
                    <p class="mt-1 text-[11px] text-neutral-500">Kosongkan harga modal bila tidak berubah.</p>
                </div>

                <div class="flex items-center justify-between rounded-xl border border-white/10 bg-ink-900 p-3 text-sm">
                    <span class="text-neutral-400">Perkiraan nilai faktur</span><span class="font-display text-lg font-black text-white" x-text="rupiah(invoiceTotal)"></span>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-white/10 bg-ink-900 p-4">
                <button type="button" x-on:click="stockIn = false" class="a18-btn-ghost">Batal</button>
                <button type="submit" class="a18-btn-primary"><x-lucide-circle-check class="h-4 w-4" /> Simpan Barang Masuk</button>
            </div>
        </form>
    </div>
</div>
@endsection
