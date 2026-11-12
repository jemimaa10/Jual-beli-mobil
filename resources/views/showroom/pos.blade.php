@extends('layouts.app')

@section('title', 'Kasir Aksesoris')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-bind:class="cart.length ? 'pb-28 lg:pb-8' : ''"
     x-data="pos({ products: @js($products), taxEnabled: {{ $store->tax_enabled ? 'true' : 'false' }}, taxRate: {{ (float) $store->tax_rate }} })"
     x-on:keydown.escape.window="payOpen = false">

    {{-- Banner --}}
    <div class="a18-stripes-soft flex flex-col gap-4 rounded-3xl p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Kasir Showroom</span>
            <h1 class="a18-heading mt-1 text-2xl sm:text-3xl">Kasir Aksesoris &amp; Perawatan</h1>
            <p class="mt-1.5 max-w-xl text-sm text-neutral-400">Transaksi cepat untuk oli, aki, ban, aksesoris, dan jasa showroom Auto18.</p>
        </div>
        <div class="a18-pill shrink-0 px-5 py-3 text-center">
            <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Keranjang</p>
            <p class="font-display text-xl font-black text-white"><span x-text="count">0</span> item</p>
        </div>
    </div>

    @if ($errors->any())
        <p class="mt-4 rounded-lg border border-brand-600/40 bg-brand-600/10 px-3 py-2 text-sm text-brand-300" role="alert">{{ $errors->first() }}</p>
    @endif

    <div class="mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        {{-- Katalog --}}
        <div class="space-y-4 lg:col-span-7 xl:col-span-8">
            <div class="a18-card space-y-3 p-4">
                <div class="relative">
                    <x-lucide-search class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-neutral-500" aria-hidden="true" />
                    <input type="text" x-model="search" placeholder="Cari oli, aki, ban, kode SKU, atau scan barcode..." aria-label="Cari barang atau jasa" class="a18-input pl-11 pr-10">
                    <button type="button" x-show="search" x-cloak x-on:click="search = ''" aria-label="Hapus pencarian" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-neutral-500 transition-colors hover:text-white"><x-lucide-x class="h-4 w-4" /></button>
                </div>
                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                    <button type="button" x-on:click="category = 'all'" x-bind:aria-pressed="category === 'all'" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-bold transition-colors"
                            x-bind:class="category === 'all' ? 'bg-brand-600 text-white' : 'bg-white/5 text-neutral-400 hover:bg-white/10 hover:text-white'">Semua ({{ count($products) }})</button>
                    @foreach ($categories as $cat)
                        <button type="button" x-on:click="category = {{ $cat->id }}" x-bind:aria-pressed="category === {{ $cat->id }}" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-bold transition-colors"
                                x-bind:class="category === {{ $cat->id }} ? 'bg-brand-600 text-white' : 'bg-white/5 text-neutral-400 hover:bg-white/10 hover:text-white'">{{ $cat->name }} (<span x-text="countIn({{ $cat->id }})"></span>)</button>
                    @endforeach
                </div>
            </div>

            <div x-show="filtered.length === 0" x-cloak class="a18-card p-12 text-center">
                <x-lucide-package-open class="mx-auto mb-3 h-12 w-12 text-neutral-700" aria-hidden="true" />
                <h3 class="font-display font-black uppercase tracking-wide text-white">Barang Tidak Ditemukan</h3>
                <p class="mt-1 text-xs text-neutral-500">Coba kata kunci lain, misalnya "oli", "aki", atau "kaca film", atau ganti filter kategori.</p>
            </div>

            <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 xl:grid-cols-4">
                <template x-for="p in filtered" :key="p.id">
                    <button type="button" x-on:click="add(p)" x-bind:disabled="p.stock <= 0" x-bind:aria-label="'Tambah ' + p.name + ' ke keranjang'"
                            class="group a18-card flex flex-col justify-between p-3.5 text-left transition-colors"
                            x-bind:class="p.stock <= 0 ? 'cursor-not-allowed opacity-50' : 'hover:border-brand-600 active:scale-[0.98]'">
                        <div class="mb-2 flex items-center justify-between gap-1 overflow-hidden">
                            <span class="truncate rounded bg-white/5 px-1.5 py-0.5 font-mono text-[10px] text-neutral-500" x-text="p.sku"></span>
                            <span x-show="p.stock <= 0" class="rounded-full border border-brand-500/40 bg-brand-600/20 px-2 py-0.5 text-[10px] font-bold text-brand-300">Habis</span>
                            <span x-show="p.stock > 0 && p.stock <= p.min_stock" class="whitespace-nowrap rounded-full border border-amber-500/30 bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-300" x-text="'Sisa ' + p.stock"></span>
                            <span x-show="p.stock > p.min_stock" class="whitespace-nowrap text-[10px] text-neutral-500" x-text="'Stok ' + p.stock + ' ' + p.unit"></span>
                        </div>
                        <div class="mb-3 flex-1">
                            <span class="line-clamp-2 block text-sm font-bold leading-snug text-white transition-colors group-hover:text-brand-400" x-text="p.name"></span>
                            <span class="mt-0.5 block truncate text-[11px] text-neutral-500" x-text="p.category_name || ''"></span>
                        </div>
                        <div class="flex items-center justify-between gap-2 border-t border-white/10 pt-2">
                            <div class="min-w-0">
                                <span class="block text-[10px] uppercase leading-none tracking-wider text-neutral-500">Harga</span>
                                <span class="text-sm font-bold text-white" x-text="rupiah(p.sell_price)"></span>
                            </div>
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-black transition-colors"
                                  x-bind:class="qtyOf(p.id) > 0 ? 'bg-brand-600 text-white' : 'bg-white/5 text-neutral-300 group-hover:bg-brand-600 group-hover:text-white'">
                                <span x-show="qtyOf(p.id) > 0" x-text="qtyOf(p.id)"></span>
                                <span x-show="qtyOf(p.id) === 0"><x-lucide-plus class="h-4 w-4" aria-hidden="true" /></span>
                            </span>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        {{-- Keranjang --}}
        <div class="lg:sticky lg:top-28 lg:col-span-5 xl:col-span-4">
            <div class="a18-card flex flex-col lg:h-[calc(100vh-9rem)]">
                <div class="flex items-center justify-between gap-2 border-b border-white/10 p-4">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="rounded-xl border border-brand-500/30 bg-brand-600/20 p-2 text-brand-400"><x-lucide-shopping-cart class="h-5 w-5" aria-hidden="true" /></span>
                        <div class="min-w-0">
                            <h2 class="font-display text-base font-black uppercase tracking-wide text-white">Keranjang Kasir</h2>
                            <p class="text-xs text-neutral-400"><span x-text="count">0</span> item dipilih</p>
                        </div>
                    </div>
                    <button type="button" x-show="cart.length" x-cloak x-on:click="clear()" class="a18-btn-ghost shrink-0 px-2.5 py-1.5 text-xs text-neutral-400 hover:text-brand-400"><x-lucide-trash-2 class="h-3.5 w-3.5" aria-hidden="true" /> Kosongkan</button>
                </div>

                <div class="min-h-[8rem] flex-1 overflow-y-auto overflow-x-hidden p-4 lg:min-h-0">
                    <div x-show="cart.length === 0" class="flex h-full flex-col items-center justify-center py-8 text-center">
                        <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-neutral-600"><x-lucide-wrench class="h-8 w-8" aria-hidden="true" /></div>
                        <p class="font-bold text-white">Keranjang Masih Kosong</p>
                        <p class="mt-1 max-w-xs text-xs text-neutral-500">Pilih oli, aki, ban, aksesoris, atau jasa showroom di katalog untuk memulai transaksi.</p>
                    </div>
                    <div class="-my-3 divide-y divide-white/10" x-show="cart.length" x-cloak>
                        <template x-for="line in lines" :key="line.id">
                            <div class="flex items-center justify-between gap-2 py-3">
                                <div class="min-w-0 flex-1">
                                    <h5 class="truncate text-sm font-semibold text-white" x-bind:title="line.product.name" x-text="line.product.name"></h5>
                                    <div class="mt-0.5 flex items-center gap-2 text-xs text-neutral-500">
                                        <span x-text="rupiah(line.product.sell_price)"></span><span aria-hidden="true">&bull;</span>
                                        <span class="font-bold text-neutral-200" x-text="rupiah(line.product.sell_price * line.qty)"></span>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5 rounded-xl bg-white/5 p-1">
                                    <button type="button" x-on:click="setQty(line.id, line.qty - 1)" x-bind:aria-label="'Kurangi jumlah ' + line.product.name" class="flex h-6 w-6 items-center justify-center rounded-lg bg-ink-600 text-white transition-colors hover:bg-brand-600"><x-lucide-minus class="h-3 w-3" aria-hidden="true" /></button>
                                    <span class="w-7 text-center text-xs font-bold text-white" x-text="line.qty"></span>
                                    <button type="button" x-on:click="setQty(line.id, line.qty + 1)" x-bind:aria-label="'Tambah jumlah ' + line.product.name" class="flex h-6 w-6 items-center justify-center rounded-lg bg-ink-600 text-white transition-colors hover:bg-brand-600"><x-lucide-plus class="h-3 w-3" aria-hidden="true" /></button>
                                </div>
                                <button type="button" x-on:click="remove(line.id)" title="Hapus item" x-bind:aria-label="'Hapus ' + line.product.name + ' dari keranjang'" class="shrink-0 rounded-lg p-1.5 text-neutral-500 transition-colors hover:text-brand-400"><x-lucide-trash-2 class="h-4 w-4" aria-hidden="true" /></button>
                            </div>
                        </template>
                    </div>
                </div>

                <div x-show="cart.length" x-cloak class="space-y-3 rounded-b-2xl border-t border-white/10 bg-ink-900 p-4">
                    <div>
                        <span class="a18-label">Diskon Transaksi</span>
                        <div class="flex items-center gap-2">
                            <div class="flex shrink-0 rounded-xl border border-white/15 p-0.5">
                                @foreach ([['nominal', 'Rp'], ['percent', '%']] as [$id, $label])
                                    <button type="button" x-on:click="discountType = '{{ $id }}'" x-bind:aria-pressed="discountType === '{{ $id }}'" class="rounded-lg px-2.5 py-1 text-xs font-bold transition-colors"
                                            x-bind:class="discountType === '{{ $id }}' ? 'bg-brand-600 text-white' : 'text-neutral-400 hover:text-white'">{{ $label }}</button>
                                @endforeach
                            </div>
                            <input type="number" min="0" x-model="discountValue" x-bind:placeholder="discountType === 'percent' ? 'Potongan (%)' : 'Potongan (Rp)'" aria-label="Nilai diskon" class="a18-input min-w-0 flex-1 py-1.5 text-xs">
                        </div>
                    </div>
                    <div class="space-y-1.5 text-xs text-neutral-400">
                        <div class="flex justify-between"><span>Subtotal</span><span class="font-semibold text-white" x-text="rupiah(subtotal)"></span></div>
                        <div class="flex justify-between text-brand-400" x-show="discount > 0">
                            <span class="flex items-center gap-2">Potongan Diskon <button type="button" x-on:click="discountValue = ''" class="text-[10px] uppercase tracking-wider text-neutral-500 underline hover:text-white">Batal</button></span>
                            <span class="font-semibold">-<span x-text="rupiah(discount)"></span></span>
                        </div>
                        @if ($store->tax_enabled)
                            <div class="flex justify-between"><span>PPN ({{ rtrim(rtrim(number_format($store->tax_rate, 2, ',', '.'), '0'), ',') }}%)</span><span class="font-semibold text-white" x-text="rupiah(tax)"></span></div>
                        @endif
                        <div class="flex items-baseline justify-between gap-2 border-t border-white/10 pt-2">
                            <span class="font-display text-sm font-black uppercase tracking-wide text-white">Total Bayar</span>
                            <span class="font-display text-xl font-black text-white" x-text="rupiah(total)"></span>
                        </div>
                    </div>
                    <button type="button" x-on:click="openPay()" class="a18-btn-primary w-full py-3.5"><x-lucide-credit-card class="h-5 w-5" aria-hidden="true" /> Bayar Sekarang</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bar bayar mengambang (ponsel) --}}
    <div x-show="cart.length && !payOpen" x-cloak class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-ink-900/95 p-3 backdrop-blur-sm lg:hidden">
        <div class="mx-auto flex max-w-7xl items-center gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-500"><span x-text="count"></span> item</p>
                <p class="truncate font-display text-lg font-black text-white" x-text="rupiah(total)"></p>
            </div>
            <button type="button" x-on:click="openPay()" class="a18-btn-primary shrink-0 px-5 py-3"><x-lucide-credit-card class="h-4 w-4" aria-hidden="true" /> Bayar</button>
        </div>
    </div>

    {{-- Modal pembayaran: form biasa (POST) --}}
    <div x-show="payOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm" x-on:mousedown.self="payOpen = false">
        <form method="POST" action="{{ route('showroom.pos.checkout') }}" x-on:submit="submit($event)" role="dialog" aria-modal="true" aria-label="Pembayaran kasir"
              class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-3xl border border-white/10 bg-ink-800 shadow-2xl">
            @csrf
            <template x-for="(line, i) in cart" :key="line.id">
                <span><input type="hidden" x-bind:name="'items[' + i + '][product_id]'" x-bind:value="line.id"><input type="hidden" x-bind:name="'items[' + i + '][qty]'" x-bind:value="line.qty"></span>
            </template>
            <input type="hidden" name="discount_type" x-bind:value="discountType">
            <input type="hidden" name="discount_value" x-bind:value="discountValue === '' ? 0 : discountValue">
            <input type="hidden" name="payment_method" x-bind:value="method">

            <div class="flex items-center justify-between gap-2 border-b border-white/10 p-5">
                <div class="min-w-0"><h3 class="a18-heading text-lg">Pembayaran Kasir</h3><p class="text-xs text-neutral-400">Pilih metode &amp; selesaikan transaksi</p></div>
                <button type="button" x-on:click="payOpen = false" aria-label="Tutup pembayaran" class="rounded-xl p-2 text-neutral-400 transition-colors hover:bg-white/10 hover:text-white"><x-lucide-x class="h-5 w-5" aria-hidden="true" /></button>
            </div>

            <div class="a18-stripes-soft p-6 text-center">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Total Tagihan</span>
                <div class="mt-1 font-display text-3xl font-black text-white" x-text="rupiah(total)"></div>
            </div>

            <div class="flex-1 space-y-5 overflow-y-auto p-6">
                <div>
                    <label for="pay-customer" class="a18-label">Nama Pelanggan (Opsional)</label>
                    <input id="pay-customer" type="text" name="customer_name" x-model="customer" placeholder="Contoh: Pelanggan Umum, Bpk. Rizky, dll." class="a18-input">
                </div>

                <div>
                    <span class="a18-label">Metode Pembayaran</span>
                    <div class="grid grid-cols-3 gap-2.5">
                        @foreach ([['cash', 'Tunai', 'banknote'], ['qris', 'QRIS', 'qr-code'], ['transfer', 'Transfer', 'building-2']] as [$id, $label, $icon])
                            <button type="button" x-on:click="method = '{{ $id }}'" x-bind:aria-pressed="method === '{{ $id }}'" class="flex flex-col items-center justify-center gap-1.5 rounded-2xl border p-3 transition-colors"
                                    x-bind:class="method === '{{ $id }}' ? 'border-brand-600 bg-brand-600/15 font-bold text-white' : 'border-white/10 bg-ink-900 text-neutral-400 hover:border-white/25 hover:text-white'">
                                <span x-bind:class="method === '{{ $id }}' ? 'text-brand-500' : 'text-neutral-500'">@svg('lucide-' . $icon, 'h-5 w-5')</span>
                                <span class="text-xs">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div x-show="method === 'cash'" class="space-y-4 pt-2">
                    <div>
                        <label for="pay-cash" class="a18-label">Uang Diterima (Rp)</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-neutral-500">Rp</span>
                            <input id="pay-cash" x-ref="cash" type="number" name="paid_amount" x-model="cash" x-bind:disabled="method !== 'cash'" placeholder="0" class="a18-input pl-10 text-base font-bold">
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="opt in quickCash" :key="opt.value"><button type="button" x-on:click="cash = String(opt.value)" class="rounded-lg bg-white/5 px-3 py-1.5 text-xs font-semibold text-neutral-200 transition-colors hover:bg-white/10" x-text="opt.label"></button></template>
                    </div>
                    <div x-show="cashNumber > 0" class="flex items-center justify-between gap-2 rounded-xl border p-3.5" x-bind:class="cashEnough ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-brand-500/40 bg-brand-600/15 text-brand-300'">
                        <span class="text-xs font-semibold" x-text="cashEnough ? 'Kembalian:' : 'Uang Kurang:'"></span>
                        <span class="font-display text-base font-black" x-text="rupiah(Math.abs(cashNumber - total))"></span>
                    </div>
                </div>

                <div x-show="method === 'qris'" x-cloak class="space-y-3 rounded-2xl border border-white/10 bg-ink-900 p-4 text-center">
                    <div class="mx-auto flex h-44 w-44 flex-col items-center justify-center rounded-2xl bg-white p-3">
                        <svg class="h-full w-full text-slate-900" viewBox="0 0 100 100" fill="currentColor" role="img" aria-label="Simulasi kode QRIS"><path d="M10 10h30v30H10zM50 10h10v10H50zM70 10h20v20H70zM50 30h10v10H50zM15 15v20h20V15H15zM20 20h10v10H20zM75 15v10h10V15H75zM10 50h10v20H10zM30 50h20v10H30zM70 50h20v10H70zM10 80h30v10H10zM60 70h10v20H60zM80 80h10v10H80zM30 70h10v10H30zM50 80h10v10H50z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">Scan QRIS Nasional</p>
                        <p class="text-xs text-neutral-400">BCA, Mandiri, BRI, GoPay, OVO, Dana, ShopeePay</p>
                        <p class="mt-1 text-xs font-bold text-brand-400">Total: <span x-text="rupiah(total)"></span></p>
                    </div>
                </div>

                <div x-show="method === 'transfer'" x-cloak class="space-y-3">
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['BCA', 'Mandiri', 'BRI'] as $b)
                            <button type="button" x-on:click="bank = '{{ $b }}'" x-bind:aria-pressed="bank === '{{ $b }}'" class="rounded-xl border py-2 text-xs font-bold transition-colors"
                                    x-bind:class="bank === '{{ $b }}' ? 'border-brand-600 bg-brand-600/15 text-white' : 'border-white/10 bg-ink-900 text-neutral-400 hover:border-white/25 hover:text-white'">{{ $b }}</button>
                        @endforeach
                    </div>
                    <div class="space-y-1.5 rounded-xl border border-white/10 bg-ink-900 p-4 text-xs">
                        <div class="flex justify-between gap-2"><span class="text-neutral-500">Bank Tujuan:</span><span class="font-bold text-white" x-text="'Bank ' + bank"></span></div>
                        <div class="flex justify-between gap-2"><span class="text-neutral-500">Nomor Rekening:</span><span class="font-mono font-bold text-white" x-text="account"></span></div>
                        <div class="flex justify-between gap-2"><span class="text-neutral-500">Atas Nama:</span><span class="font-semibold text-white">{{ $store->name }}</span></div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse items-stretch gap-2 border-t border-white/10 bg-ink-900 p-4 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" x-on:click="payOpen = false" class="a18-btn-ghost">Batal</button>
                <button type="submit" x-bind:disabled="method === 'cash' && !cashEnough" class="a18-btn-primary px-6">Selesaikan &amp; Cetak Struk <x-lucide-arrow-right class="h-4 w-4" aria-hidden="true" /></button>
            </div>
        </form>
    </div>
</div>
@endsection
