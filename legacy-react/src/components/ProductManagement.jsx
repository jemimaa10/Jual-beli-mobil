import React, { useMemo, useState } from 'react';
import {
  Plus,
  Search,
  Pencil,
  Trash2,
  AlertTriangle,
  X,
  Package,
  Boxes,
  Wallet,
  TrendingUp,
  Barcode
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import { formatRupiah, generateSku } from '../utils/formatters';

// Satuan yang dipakai katalog sparepart, aksesoris, dan jasa showroom
const UNIT_OPTIONS = ['pcs', 'botol', 'liter', 'set', 'unit', 'pack', 'paket', 'pasang'];

// Prefix SKU otomatis per kategori otomotif (fallback: PRD)
const SKU_PREFIX_BY_CATEGORY = {
  'Oli & Pelumas': 'OLI',
  'Aki & Kelistrikan': 'AKI',
  'Ban & Kaki-kaki': 'BAN',
  'Aksesoris Interior': 'INT',
  'Perawatan & Cuci Mobil': 'CUC',
  'Jasa Showroom': 'JSA'
};

const skuPrefixFor = (categoryName) => SKU_PREFIX_BY_CATEGORY[categoryName] || 'PRD';

const num = (value) => (Number.isFinite(Number(value)) ? Number(value) : 0);

function StatCard({ icon: Icon, label, value, hint, tone = 'default' }) {
  const valueTone =
    tone === 'warning' ? 'text-amber-400' : tone === 'profit' ? 'text-emerald-400' : 'text-white';
  const iconTone = tone === 'warning' ? 'text-amber-400' : 'text-brand-500';

  return (
    <div className="a18-card p-4">
      <div className="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-neutral-500">
        <Icon className={`h-3.5 w-3.5 ${iconTone}`} aria-hidden="true" />
        {label}
      </div>
      <p className={`mt-1.5 break-words font-display text-lg font-black sm:text-xl ${valueTone}`}>{value}</p>
      {hint && <p className="mt-0.5 text-[11px] text-neutral-500">{hint}</p>}
    </div>
  );
}

export default function ProductManagement() {
  const {
    products,
    addProduct,
    updateProduct,
    deleteProduct,
    adjustStock,
    categories
  } = useApp();

  const [search, setSearch] = useState('');
  const [catFilter, setCatFilter] = useState('all');
  const [filterLowStockOnly, setFilterLowStockOnly] = useState(false);

  // Modal states
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [isStockModalOpen, setIsStockModalOpen] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [stockDelta, setStockDelta] = useState('');

  // SKU otomatis berhenti mengikuti kategori setelah diketik manual
  const [skuTouched, setSkuTouched] = useState(false);

  // Form states
  const [formData, setFormData] = useState({
    sku: '',
    name: '',
    categoryId: '',
    unit: 'pcs',
    buyPrice: '',
    sellPrice: '',
    stock: '',
    minStock: '5',
    barcode: ''
  });

  const openAddModal = () => {
    setSelectedProduct(null);
    const firstCat = categories[0];
    setSkuTouched(false);
    setFormData({
      sku: generateSku(skuPrefixFor(firstCat?.name)),
      name: '',
      categoryId: firstCat?.id || '',
      unit: 'pcs',
      buyPrice: '',
      sellPrice: '',
      stock: '10',
      minStock: '5',
      barcode: ''
    });
    setIsFormOpen(true);
  };

  const openEditModal = (product) => {
    setSelectedProduct(product);
    setSkuTouched(true);
    setFormData({
      sku: product.sku,
      name: product.name,
      categoryId: product.categoryId,
      unit: product.unit,
      buyPrice: String(product.buyPrice),
      sellPrice: String(product.sellPrice),
      stock: String(product.stock),
      minStock: String(product.minStock),
      barcode: product.barcode || ''
    });
    setIsFormOpen(true);
  };

  // Ganti kategori → prefix SKU ikut menyesuaikan selama SKU belum diketik manual
  const handleCategoryChange = (categoryId) => {
    const cat = categories.find(c => c.id === categoryId);
    setFormData(prev => ({
      ...prev,
      categoryId,
      sku: !selectedProduct && !skuTouched ? generateSku(skuPrefixFor(cat?.name)) : prev.sku
    }));
  };

  const handleSaveProduct = (e) => {
    e.preventDefault();
    const cat = categories.find(c => c.id === formData.categoryId);
    const payload = {
      sku: formData.sku || generateSku(skuPrefixFor(cat?.name)),
      name: formData.name,
      categoryId: formData.categoryId,
      categoryName: cat ? cat.name : 'Umum',
      unit: formData.unit,
      buyPrice: Number(formData.buyPrice) || 0,
      sellPrice: Number(formData.sellPrice) || 0,
      stock: Number(formData.stock) || 0,
      minStock: Number(formData.minStock) || 5,
      barcode: formData.barcode
    };

    if (selectedProduct) {
      updateProduct(selectedProduct.id, payload);
    } else {
      addProduct(payload);
    }
    setIsFormOpen(false);
  };

  const handleDelete = (id, name) => {
    if (confirm(`Yakin ingin menghapus produk "${name}"?`)) {
      deleteProduct(id);
    }
  };

  const handleStockAdjustment = (e) => {
    e.preventDefault();
    if (!selectedProduct) return;
    const delta = Number(stockDelta);
    if (!isNaN(delta) && delta !== 0) {
      adjustStock(selectedProduct.id, delta, 'Penyesuaian Manual');
    }
    setIsStockModalOpen(false);
    setStockDelta('');
  };

  const openStockModal = (product) => {
    setSelectedProduct(product);
    setStockDelta('');
    setIsStockModalOpen(true);
  };

  // Ringkasan stok gudang aksesoris
  const stats = useMemo(() => {
    const lowStock = products.filter(p => p.stock <= p.minStock);
    return {
      total: products.length,
      low: lowStock.length,
      out: products.filter(p => p.stock <= 0).length,
      stockValue: products.reduce((sum, p) => sum + num(p.buyPrice) * num(p.stock), 0),
      potentialProfit: products.reduce(
        (sum, p) => sum + (num(p.sellPrice) - num(p.buyPrice)) * num(p.stock),
        0
      )
    };
  }, [products]);

  // Filtered list
  const filtered = products.filter(p => {
    const matchCat = catFilter === 'all' || p.categoryId === catFilter;
    const matchSearch = p.name.toLowerCase().includes(search.toLowerCase()) || p.sku.toLowerCase().includes(search.toLowerCase());
    const matchLow = filterLowStockOnly ? p.stock <= p.minStock : true;
    return matchCat && matchSearch && matchLow;
  });

  const stockBadgeClass = (p) => {
    if (p.stock <= 0) return 'border-brand-500/40 bg-brand-600/15 text-brand-300 hover:bg-brand-600/25';
    if (p.stock <= p.minStock) return 'border-amber-500/40 bg-amber-500/10 text-amber-300 hover:bg-amber-500/20';
    return 'border-white/15 bg-white/5 text-white hover:bg-white/10';
  };

  const renderStockButton = (p) => (
    <button
      type="button"
      onClick={() => openStockModal(p)}
      title="Klik untuk ubah stok manual"
      aria-label={`Ubah stok ${p.name}`}
      className={`inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 font-mono text-xs font-bold transition-colors ${stockBadgeClass(p)}`}
    >
      <span>{p.stock}</span>
      <span className="text-[10px] font-normal opacity-80">{p.unit}</span>
    </button>
  );

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      {/* Banner */}
      <div className="a18-stripes-soft flex flex-col gap-4 rounded-3xl p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Panel Showroom</span>
          <h1 className="a18-heading mt-1 text-2xl sm:text-3xl">Produk Aksesoris &amp; Stok</h1>
          <p className="mt-1.5 max-w-xl text-sm text-neutral-400">
            Kelola katalog sparepart, oli, ban, aksesoris interior, dan jasa showroom Auto18 — SKU, harga modal,
            harga jual, sampai stok gudang.
          </p>
        </div>
        <button type="button" onClick={openAddModal} className="a18-btn-primary shrink-0 px-5 py-3">
          <Plus className="h-4 w-4" aria-hidden="true" />
          Tambah Produk
        </button>
      </div>

      {/* Ringkasan */}
      <div className="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <StatCard icon={Package} label="Total Produk" value={stats.total} hint="item di katalog" />
        <StatCard
          icon={AlertTriangle}
          label="Stok Menipis"
          value={stats.low}
          tone="warning"
          hint={`${stats.out} item habis`}
        />
        <StatCard icon={Wallet} label="Nilai Stok" value={formatRupiah(stats.stockValue)} hint="modal barang di gudang" />
        <StatCard
          icon={TrendingUp}
          label="Potensi Laba"
          value={formatRupiah(stats.potentialProfit)}
          tone="profit"
          hint="bila stok terjual habis"
        />
      </div>

      {/* Filter Toolbar */}
      <div className="a18-card mt-6 flex flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between">
        <div className="relative w-full md:w-80">
          <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" aria-hidden="true" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Cari nama produk atau SKU..."
            aria-label="Cari produk atau SKU"
            className="a18-input pl-9"
          />
        </div>

        <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center md:w-auto">
          <select
            value={catFilter}
            onChange={(e) => setCatFilter(e.target.value)}
            aria-label="Filter kategori"
            className="a18-input sm:w-56"
          >
            <option value="all">Semua Kategori ({products.length})</option>
            {categories.map(c => (
              <option key={c.id} value={c.id}>{c.name}</option>
            ))}
          </select>

          <button
            type="button"
            onClick={() => setFilterLowStockOnly(!filterLowStockOnly)}
            aria-pressed={filterLowStockOnly}
            className={`inline-flex shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-xl border px-3.5 py-2.5 text-xs font-bold transition-colors ${
              filterLowStockOnly
                ? 'border-amber-500/50 bg-amber-500/15 text-amber-300'
                : 'border-white/15 bg-ink-900 text-neutral-400 hover:border-white/30 hover:text-white'
            }`}
          >
            <AlertTriangle className="h-3.5 w-3.5" aria-hidden="true" />
            Stok Menipis
          </button>
        </div>
      </div>

      {/* Tabel produk (desktop) */}
      <div className="mt-4 hidden overflow-x-auto rounded-2xl border border-white/10 md:block">
        <table className="w-full min-w-[900px] text-left text-sm">
          <thead className="bg-ink-900 text-[10px] uppercase tracking-wider text-neutral-500">
            <tr>
              <th className="px-4 py-3 font-bold">SKU &amp; Barcode</th>
              <th className="px-4 py-3 font-bold">Nama Produk</th>
              <th className="px-4 py-3 font-bold">Kategori</th>
              <th className="px-4 py-3 text-right font-bold">Harga Modal</th>
              <th className="px-4 py-3 text-right font-bold">Harga Jual</th>
              <th className="px-4 py-3 text-right font-bold">Margin Laba</th>
              <th className="px-4 py-3 text-center font-bold">Stok</th>
              <th className="px-4 py-3 text-center font-bold">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {filtered.length === 0 ? (
              <tr>
                <td colSpan={8} className="px-4 py-10 text-center text-sm text-neutral-500">
                  Tidak ada produk yang cocok dengan filter.
                </td>
              </tr>
            ) : (
              filtered.map(p => {
                const profit = p.sellPrice - p.buyPrice;
                const profitMargin = p.sellPrice > 0 ? Math.round((profit / p.sellPrice) * 100) : 0;

                return (
                  <tr key={p.id} className="border-t border-white/10 transition-colors hover:bg-white/5">
                    <td className="px-4 py-3">
                      <div className="font-mono text-xs font-bold text-white">{p.sku}</div>
                      {p.barcode && <div className="font-mono text-[10px] text-neutral-500">{p.barcode}</div>}
                    </td>
                    <td className="px-4 py-3">
                      <div className="font-semibold text-white">{p.name}</div>
                      <div className="text-[11px] text-neutral-500">Satuan: {p.unit}</div>
                    </td>
                    <td className="px-4 py-3">
                      <span className="inline-block rounded-md border border-white/10 bg-white/5 px-2 py-0.5 text-[11px] font-medium text-neutral-300">
                        {p.categoryName}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-right font-mono text-neutral-400">
                      {formatRupiah(p.buyPrice)}
                    </td>
                    <td className="px-4 py-3 text-right font-mono font-bold text-white">
                      {formatRupiah(p.sellPrice)}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <span className={`font-mono font-bold ${profit > 0 ? 'text-emerald-400' : 'text-brand-400'}`}>
                        {profit > 0 ? '+' : ''}{formatRupiah(profit)}
                      </span>
                      <div className="text-[10px] text-neutral-500">({profitMargin}%)</div>
                    </td>
                    <td className="px-4 py-3 text-center">{renderStockButton(p)}</td>
                    <td className="px-4 py-3">
                      <div className="flex items-center justify-center gap-1.5">
                        <button
                          type="button"
                          onClick={() => openEditModal(p)}
                          title="Ubah produk"
                          aria-label={`Ubah ${p.name}`}
                          className="rounded-lg p-1.5 text-neutral-400 transition-colors hover:bg-white/10 hover:text-white"
                        >
                          <Pencil className="h-4 w-4" aria-hidden="true" />
                        </button>
                        <button
                          type="button"
                          onClick={() => handleDelete(p.id, p.name)}
                          title="Hapus produk"
                          aria-label={`Hapus ${p.name}`}
                          className="rounded-lg p-1.5 text-neutral-400 transition-colors hover:bg-brand-600/20 hover:text-brand-400"
                        >
                          <Trash2 className="h-4 w-4" aria-hidden="true" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

      {/* Kartu produk (mobile) */}
      <div className="mt-4 space-y-3 md:hidden">
        {filtered.length === 0 ? (
          <div className="a18-card p-8 text-center text-sm text-neutral-500">
            Tidak ada produk yang cocok dengan filter.
          </div>
        ) : (
          filtered.map(p => {
            const profit = p.sellPrice - p.buyPrice;
            const profitMargin = p.sellPrice > 0 ? Math.round((profit / p.sellPrice) * 100) : 0;

            return (
              <div key={p.id} className="a18-card p-4">
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0">
                    <p className="truncate font-bold text-white">{p.name}</p>
                    <p className="font-mono text-[11px] text-neutral-500">
                      {p.sku}{p.barcode ? ` · ${p.barcode}` : ''}
                    </p>
                  </div>
                  {renderStockButton(p)}
                </div>

                <div className="mt-2 flex flex-wrap items-center gap-1.5">
                  <span className="rounded-md border border-white/10 bg-white/5 px-2 py-0.5 text-[10px] font-medium text-neutral-300">
                    {p.categoryName}
                  </span>
                  <span className="text-[10px] text-neutral-500">Satuan: {p.unit}</span>
                </div>

                <div className="mt-3 grid grid-cols-3 gap-2 border-t border-white/10 pt-3 text-xs">
                  <div>
                    <p className="text-[10px] uppercase tracking-wider text-neutral-500">Modal</p>
                    <p className="font-mono text-neutral-300">{formatRupiah(p.buyPrice)}</p>
                  </div>
                  <div>
                    <p className="text-[10px] uppercase tracking-wider text-neutral-500">Jual</p>
                    <p className="font-mono font-bold text-white">{formatRupiah(p.sellPrice)}</p>
                  </div>
                  <div>
                    <p className="text-[10px] uppercase tracking-wider text-neutral-500">Margin</p>
                    <p className={`font-mono font-bold ${profit > 0 ? 'text-emerald-400' : 'text-brand-400'}`}>
                      {profit > 0 ? '+' : ''}{formatRupiah(profit)}
                    </p>
                    <p className="text-[10px] text-neutral-500">({profitMargin}%)</p>
                  </div>
                </div>

                <div className="mt-3 flex items-center justify-end gap-2">
                  <button type="button" onClick={() => openEditModal(p)} className="a18-btn-ghost px-3 py-1.5 text-xs">
                    <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                    Ubah
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDelete(p.id, p.name)}
                    className="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold text-brand-400 transition-colors hover:bg-brand-600/20"
                  >
                    <Trash2 className="h-3.5 w-3.5" aria-hidden="true" />
                    Hapus
                  </button>
                </div>
              </div>
            );
          })
        )}
      </div>

      {/* Add / Edit Product Modal */}
      {isFormOpen && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
          role="dialog"
          aria-modal="true"
          aria-label={selectedProduct ? 'Ubah data produk' : 'Tambah produk baru'}
        >
          <div className="flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-3xl border border-white/10 bg-ink-800">
            <div className="flex shrink-0 items-center justify-between border-b border-white/10 p-5">
              <h3 className="a18-heading text-base">
                {selectedProduct ? 'Ubah Data Produk' : 'Tambah Produk Baru'}
              </h3>
              <button
                type="button"
                onClick={() => setIsFormOpen(false)}
                aria-label="Tutup"
                className="rounded-lg p-1.5 text-neutral-400 transition-colors hover:bg-white/10 hover:text-white"
              >
                <X className="h-5 w-5" aria-hidden="true" />
              </button>
            </div>

            <form onSubmit={handleSaveProduct} className="flex-1 space-y-4 overflow-y-auto p-5">
              <div>
                <label className="a18-label" htmlFor="prod-name">Nama Produk</label>
                <input
                  id="prod-name"
                  type="text"
                  required
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  placeholder="Contoh: Oli Mesin Shell Helix HX7 4L"
                  className="a18-input"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="a18-label" htmlFor="prod-sku">SKU / Kode</label>
                  <input
                    id="prod-sku"
                    type="text"
                    required
                    value={formData.sku}
                    onChange={(e) => {
                      setSkuTouched(true);
                      setFormData({ ...formData, sku: e.target.value });
                    }}
                    className="a18-input font-mono text-xs"
                  />
                </div>
                <div>
                  <label className="a18-label" htmlFor="prod-cat">Kategori</label>
                  <select
                    id="prod-cat"
                    value={formData.categoryId}
                    onChange={(e) => handleCategoryChange(e.target.value)}
                    className="a18-input text-xs"
                  >
                    {categories.map(c => (
                      <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="a18-label" htmlFor="prod-buy">Harga Modal (Rp)</label>
                  <input
                    id="prod-buy"
                    type="number"
                    min="0"
                    required
                    value={formData.buyPrice}
                    onChange={(e) => setFormData({ ...formData, buyPrice: e.target.value })}
                    placeholder="0"
                    className="a18-input font-mono"
                  />
                </div>
                <div>
                  <label className="a18-label" htmlFor="prod-sell">Harga Jual (Rp)</label>
                  <input
                    id="prod-sell"
                    type="number"
                    min="0"
                    required
                    value={formData.sellPrice}
                    onChange={(e) => setFormData({ ...formData, sellPrice: e.target.value })}
                    placeholder="0"
                    className="a18-input font-mono font-bold"
                  />
                </div>
              </div>

              {/* Preview margin */}
              <div className="flex items-center justify-between rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-xs">
                <span className="font-bold uppercase tracking-wider text-neutral-500">Margin per satuan</span>
                <span
                  className={`font-mono font-bold ${
                    Number(formData.sellPrice) - Number(formData.buyPrice) > 0 ? 'text-emerald-400' : 'text-brand-400'
                  }`}
                >
                  {formatRupiah((Number(formData.sellPrice) || 0) - (Number(formData.buyPrice) || 0))}
                </span>
              </div>

              <div className="grid grid-cols-3 gap-2">
                <div>
                  <label className="a18-label" htmlFor="prod-stock">Stok</label>
                  <input
                    id="prod-stock"
                    type="number"
                    min="0"
                    required
                    value={formData.stock}
                    onChange={(e) => setFormData({ ...formData, stock: e.target.value })}
                    className="a18-input text-xs"
                  />
                </div>
                <div>
                  <label className="a18-label" htmlFor="prod-min">Min. Stok</label>
                  <input
                    id="prod-min"
                    type="number"
                    min="0"
                    value={formData.minStock}
                    onChange={(e) => setFormData({ ...formData, minStock: e.target.value })}
                    className="a18-input text-xs"
                  />
                </div>
                <div>
                  <label className="a18-label" htmlFor="prod-unit">Satuan</label>
                  <select
                    id="prod-unit"
                    value={formData.unit}
                    onChange={(e) => setFormData({ ...formData, unit: e.target.value })}
                    className="a18-input text-xs"
                  >
                    {UNIT_OPTIONS.map(u => (
                      <option key={u} value={u}>{u}</option>
                    ))}
                    {!UNIT_OPTIONS.includes(formData.unit) && formData.unit && (
                      <option value={formData.unit}>{formData.unit}</option>
                    )}
                  </select>
                </div>
              </div>

              <div>
                <label className="a18-label" htmlFor="prod-barcode">
                  <span className="inline-flex items-center gap-1.5">
                    <Barcode className="h-3.5 w-3.5" aria-hidden="true" />
                    Barcode (Opsional)
                  </span>
                </label>
                <input
                  id="prod-barcode"
                  type="text"
                  value={formData.barcode}
                  onChange={(e) => setFormData({ ...formData, barcode: e.target.value })}
                  placeholder="899180001001"
                  className="a18-input font-mono text-xs"
                />
              </div>

              <div className="flex justify-end gap-2 border-t border-white/10 pt-4">
                <button type="button" onClick={() => setIsFormOpen(false)} className="a18-btn-ghost">
                  Batal
                </button>
                <button type="submit" className="a18-btn-primary">
                  Simpan Produk
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Quick Stock Adjustment Modal */}
      {isStockModalOpen && selectedProduct && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
          role="dialog"
          aria-modal="true"
          aria-label="Ubah stok manual"
        >
          <div className="w-full max-w-sm space-y-4 rounded-3xl border border-white/10 bg-ink-800 p-5">
            <div className="flex items-start justify-between gap-3">
              <div className="flex items-center gap-2">
                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600/15 text-brand-400">
                  <Boxes className="h-4 w-4" aria-hidden="true" />
                </span>
                <h4 className="a18-heading text-base">Ubah Stok Manual</h4>
              </div>
              <button
                type="button"
                onClick={() => setIsStockModalOpen(false)}
                aria-label="Tutup"
                className="rounded-lg p-1.5 text-neutral-400 transition-colors hover:bg-white/10 hover:text-white"
              >
                <X className="h-5 w-5" aria-hidden="true" />
              </button>
            </div>

            <p className="text-xs text-neutral-400">
              Produk: <span className="font-semibold text-white">{selectedProduct.name}</span>
              <br />
              Stok saat ini:{' '}
              <span className="font-mono font-bold text-white">
                {selectedProduct.stock} {selectedProduct.unit}
              </span>
              {selectedProduct.stock <= selectedProduct.minStock && (
                <span className="ml-1 inline-flex items-center gap-1 text-amber-400">
                  <AlertTriangle className="h-3 w-3" aria-hidden="true" />
                  di bawah minimum ({selectedProduct.minStock})
                </span>
              )}
            </p>

            <form onSubmit={handleStockAdjustment} className="space-y-3">
              <div>
                <label className="a18-label" htmlFor="stock-delta">Tambah / Kurangi Jumlah Stok</label>
                <input
                  id="stock-delta"
                  type="number"
                  required
                  autoFocus
                  placeholder="Misal 12 untuk menambah, -2 untuk mengurangi"
                  value={stockDelta}
                  onChange={(e) => setStockDelta(e.target.value)}
                  className="a18-input font-mono"
                />
                <p className="mt-1.5 text-[11px] text-neutral-500">
                  Gunakan tanda minus (-) untuk mengurangi stok, misal barang rusak atau retur ke suplier.
                </p>
              </div>

              <div className="flex justify-end gap-2 pt-1">
                <button type="button" onClick={() => setIsStockModalOpen(false)} className="a18-btn-ghost">
                  Batal
                </button>
                <button type="submit" className="a18-btn-primary">
                  Terapkan
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

    </div>
  );
}
