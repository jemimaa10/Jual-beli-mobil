@extends('layouts.app')

@section('title', 'Pengaturan Showroom')

@php
    $v = fn (string $f) => old($f, $settings->{$f});
    $num = fn (string $f) => old($f, rtrim(rtrim(number_format((float) $settings->{$f}, 2, '.', ''), '0'), '.'));
    $field = function (string $name, string $label, string $type = 'text', ?string $hint = null, ?string $placeholder = null) use ($v, $errors) {
        return compact('name', 'label', 'type', 'hint', 'placeholder');
    };
    $profile = [
        $field('name', 'Nama Showroom'),
        $field('tagline', 'Slogan / Tagline'),
        $field('address', 'Alamat Showroom'),
        $field('phone', 'Nomor Telepon', 'tel'),
        $field('email', 'Email', 'email', null, 'halo@auto18.id'),
        $field('call_center', 'Call Center 24 Jam', 'tel'),
        $field('whatsapp', 'WhatsApp', 'text', 'Format 628xxx, kosongkan jika tidak ada', '6281234567890'),
        $field('instagram', 'Instagram', 'text', 'Username tanpa @'),
        $field('tiktok', 'TikTok', 'text', 'Username tanpa @'),
        $field('youtube', 'YouTube', 'text', 'Nama channel'),
        $field('support_partner', 'Didukung Oleh (Partner)', 'text', 'Tampil sebagai "Supported by" di footer'),
    ];
@endphp

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="a18-stripes-soft rounded-3xl p-6">
        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Panel Showroom</span>
        <h1 class="a18-heading mt-1 text-2xl sm:text-3xl">Pengaturan Showroom</h1>
        <p class="mt-1.5 text-sm text-neutral-400">Profil, kontak, kasir &amp; simulasi kredit</p>
    </div>

    <form method="POST" action="{{ route('showroom.settings.update') }}" class="a18-card mt-6 space-y-6 p-5">
        @csrf @method('PUT')

        @if ($errors->any())
            <p class="rounded-lg border border-brand-600/40 bg-brand-600/10 px-3 py-2 text-sm text-brand-300" role="alert">Belum tersimpan. Periksa kolom yang bertanda merah.</p>
        @endif

        <section>
            <h2 class="a18-label">Profil &amp; Kontak</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($profile as $f)
                    <div class="{{ in_array($f['name'], ['address', 'tagline'], true) ? 'sm:col-span-2' : '' }}">
                        <label for="st-{{ $f['name'] }}" class="a18-label">{{ $f['label'] }}</label>
                        <input id="st-{{ $f['name'] }}" name="{{ $f['name'] }}" type="{{ $f['type'] }}" value="{{ $v($f['name']) }}" @if ($f['placeholder']) placeholder="{{ $f['placeholder'] }}" @endif class="a18-input">
                        @if ($f['hint'])<p class="mt-1 text-[11px] text-neutral-500">{{ $f['hint'] }}</p>@endif
                        @error($f['name'])<p class="mt-1 text-xs text-brand-400">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="a18-label">Kasir &amp; Struk</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="st-cashier" class="a18-label">Nama Kasir Default</label>
                    <input id="st-cashier" name="cashier_name" value="{{ $v('cashier_name') }}" class="a18-input">
                    @error('cashier_name')<p class="mt-1 text-xs text-brand-400">{{ $message }}</p>@enderror
                </div>
                <div x-data="{ on: {{ old('tax_enabled', $settings->tax_enabled) ? 'true' : 'false' }} }">
                    <span class="a18-label">Pajak PPN Kasir</span>
                    <label class="flex items-center gap-2 text-sm text-neutral-300"><input type="checkbox" name="tax_enabled" value="1" x-model="on" class="h-4 w-4 accent-brand-600"> Terapkan PPN pada transaksi kasir aksesoris</label>
                    <div class="mt-2 flex items-center gap-2">
                        <label for="st-tax" class="text-xs text-neutral-400">Tarif PPN</label>
                        <input id="st-tax" name="tax_rate" type="number" min="0" max="100" step="0.1" value="{{ $num('tax_rate') }}" class="a18-input w-24 py-1.5"><span class="text-sm text-neutral-400">%</span>
                    </div>
                    @error('tax_rate')<p class="mt-1 text-xs text-brand-400">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="st-footer" class="a18-label">Pesan Kaki Struk</label>
                    <textarea id="st-footer" name="receipt_footer" rows="3" class="a18-input">{{ $v('receipt_footer') }}</textarea>
                    @error('receipt_footer')<p class="mt-1 text-xs text-brand-400">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section x-data="{ rate: {{ (float) $num('credit_rate') }}, dp: {{ (float) $num('min_dp_percent') }}, get sim() { return window.auto18.installment({ price: 200000000, dpPercent: this.dp, tenorMonths: 36, annualRate: this.rate }) } }">
            <h2 class="a18-label">Simulasi Kredit Mobil</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="st-rate" class="a18-label">Bunga Flat</label>
                    <input id="st-rate" name="credit_rate" type="number" min="0" max="30" step="0.1" x-model.number="rate" class="a18-input">
                    <p class="mt-1 text-[11px] text-neutral-500">Persen flat per tahun</p>
                    @error('credit_rate')<p class="mt-1 text-xs text-brand-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="st-dp" class="a18-label">DP Minimal</label>
                    <input id="st-dp" name="min_dp_percent" type="number" min="0" max="90" step="1" x-model.number="dp" class="a18-input">
                    <p class="mt-1 text-[11px] text-neutral-500">Persen dari harga mobil</p>
                    @error('min_dp_percent')<p class="mt-1 text-xs text-brand-400">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="mt-3 rounded-xl border border-white/10 bg-ink-900 p-3 text-xs text-neutral-400">Contoh: mobil Rp 200.000.000, tenor 36 bulan &rarr; DP <span class="font-bold text-white" x-text="window.auto18.rupiah(sim.dpAmount)"></span>, cicilan <span class="font-bold text-white" x-text="window.auto18.rupiah(sim.monthly)"></span>/bulan.</p>
        </section>

        <div class="flex justify-end border-t border-white/10 pt-4">
            <button type="submit" class="a18-btn-primary"><x-lucide-save class="h-4 w-4" /> Simpan</button>
        </div>
    </form>

    <div class="a18-card mt-6 flex flex-col gap-3 border-brand-600/40 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-sm font-black uppercase tracking-wide text-white">Reset Data Demo</h2>
            <p class="mt-1 text-xs text-neutral-400">Menghapus semua mobil, produk, suplier, permintaan, test drive, transaksi, dan pengaturan, lalu mengisi ulang dengan data demo awal. Akun staf tidak berubah. Tidak bisa dibatalkan.</p>
        </div>
        <form method="POST" action="{{ route('showroom.settings.reset') }}" onsubmit="return confirm('Kembalikan semua data ke demo awal? Semua perubahan akan hilang dan tidak bisa dibatalkan.')">
            @csrf
            <button type="submit" class="a18-btn-outline shrink-0 border-brand-500 text-brand-300 hover:bg-brand-600 hover:text-white"><x-lucide-rotate-ccw class="h-4 w-4" /> Reset Data Demo</button>
        </form>
    </div>
</div>
@endsection
