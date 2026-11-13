{{-- Tombol aksi satu mobil (dipakai tabel desktop & kartu ponsel). Param: $car --}}
<div class="flex flex-wrap items-center gap-1 whitespace-nowrap">
    <a href="{{ route('cars.show', $car) }}" title="Lihat detail" aria-label="Lihat detail" class="a18-btn-ghost px-2"><x-lucide-eye class="h-4 w-4" /></a>
    <a href="{{ route('showroom.cars.edit', $car) }}" title="Edit mobil" aria-label="Edit mobil" class="a18-btn-ghost px-2"><x-lucide-pencil class="h-4 w-4" /></a>
    <form method="POST" action="{{ route('showroom.cars.feature', $car) }}">
        @csrf @method('PATCH')
        <button type="submit" title="{{ $car->is_featured ? 'Hapus dari Pilihan Auto18' : 'Jadikan Pilihan Auto18' }}" aria-label="Tandai pilihan Auto18" aria-pressed="{{ $car->is_featured ? 'true' : 'false' }}" class="a18-btn-ghost px-2">
            <x-lucide-star class="h-4 w-4 {{ $car->is_featured ? 'fill-brand-500 text-brand-500' : '' }}" />
        </button>
    </form>
    @if ($car->status === 'sold')
        @if ($car->sold_transaction_id)
            <a href="{{ route('showroom.receipt', $car->sold_transaction_id) }}" title="Lihat nota" aria-label="Lihat nota" class="a18-btn-ghost px-2"><x-lucide-receipt class="h-4 w-4" /></a>
        @endif
    @else
        <a href="{{ route('showroom.cars.sale.create', $car) }}" class="a18-btn-primary px-3 py-1.5 text-xs">Jual</a>
    @endif
    <form method="POST" action="{{ route('showroom.cars.destroy', $car) }}" onsubmit="return confirm(@js('Hapus ' . car_name($car, true) . ' dari stok?'))">
        @csrf @method('DELETE')
        <button type="submit" title="Hapus mobil" aria-label="Hapus mobil" class="a18-btn-ghost px-2 text-neutral-500 hover:text-brand-400"><x-lucide-trash-2 class="h-4 w-4" /></button>
    </form>
</div>
