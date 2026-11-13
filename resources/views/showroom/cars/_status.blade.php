{{-- Kontrol status mobil: terjual = lencana + "Batalkan penjualan"; lainnya = pilihan Tersedia/Dipesan. Params: $car, $statusClass --}}
@if ($car->status === 'sold')
    <div class="space-y-1">
        <span class="inline-block rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase {{ $statusClass['sold'] }}">Terjual {{ $car->sold_at?->locale('id')->translatedFormat('j M Y') }}</span>
        <form method="POST" action="{{ route('showroom.cars.sale.void', $car) }}"
              onsubmit="return confirm('Batalkan status terjual?\n\nTransaksi penjualannya akan ditandai &quot;Dibatalkan&quot; dan tidak lagi dihitung di omset/laba Dashboard &amp; Laporan (tetap tercatat di riwayat). Mobil ini kembali berstatus Tersedia.\n\nKalau penjualan ini memakai tukar tambah, mobil lama yang sudah masuk stok TIDAK ikut ditarik kembali.')">
            @csrf
            <button type="submit" class="block text-[10px] text-neutral-500 underline hover:text-brand-400">Batalkan status terjual</button>
        </form>
    </div>
@else
    <form method="POST" action="{{ route('showroom.cars.status', $car) }}" onchange="this.requestSubmit()">
        @csrf @method('PATCH')
        <select name="status" aria-label="Status {{ car_name($car) }}" class="a18-input min-w-[118px] py-1.5 text-xs">
            <option value="available" @selected($car->status === 'available')>Tersedia</option>
            <option value="booked" @selected($car->status === 'booked')>Dipesan</option>
        </select>
    </form>
@endif
