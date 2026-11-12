import React, { useMemo, useState } from 'react';
import {
  Truck,
  Plus,
  Phone,
  MapPin,
  User,
  PackagePlus,
  Package,
  AlertTriangle,
  Building2,
  X
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import { formatRupiah } from '../utils/formatters';
import { toast } from '../utils/toast';

const num = (value) => (Number.isFinite(Number(value)) ? Number(value) : 0);

function StatCard({ icon: Icon, label, value, hint, tone = 'default' }) {
  const valueTone = tone === 'warning' ? 'text-amber-400' : 'text-white';
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

export default function SupplierManagement() {
  const { suppliers, addSupplier, products, recordPurchase } = useApp();

  const [isSupplierModalOpen, setIsSupplierModalOpen] = useState(false);
  const [isRestockModalOpen, setIsRestockModalOpen] = useState(false);

  // Supplier Form
  const [supForm, setSupForm] = useState({
    name: '',
    contactPerson: '',
    phone: '',
    address: ''
  });

  // Restock Form
  const [restockForm, setRestockForm] = useState({
    supplierId: suppliers[0]?.id || '',
    productId: products[0]?.id || '',
    qty: 10,
    buyPrice: '',
    invoiceNo: ''
  });

  const handleCreateSupplier = (e) => {
    e.preventDefault();
    addSupplier({
      name: supForm.name,
      contactPerson: supForm.contactPerson,
      phone: supForm.phone,
      address: supForm.address
    });
    setSupForm({ name: '', contactPerson: '', phone: '', address: '' });
    setIsSupplierModalOpen(false);
  };

  const handleRestock = (e) => {
    e.preventDefault();
    const product = products.find(p => p.id === restockForm.productId);
    recordPurchase(restockForm.supplierId, [
      {
        productId: restockForm.productId,
        qty: Number(restockForm.qty),
        buyPrice: restockForm.buyPrice ? Number(restockForm.buyPrice) : (product?.buyPrice || 0)
      }
    ], restockForm.invoiceNo);

    toast.success(`Berhasil menambah ${restockForm.qty} stok untuk "${product?.name}"!`);
    // Kosongkan harga modal & no. faktur per-invoice supaya tidak tertulis ulang ke
    // produk lain saat "Catat Stok Masuk" dibuka lagi.
    setRestockForm(prev => ({ ...prev, qty: 10, buyPrice: '', invoiceNo: '' }));
    setIsRestockModalOpen(false);
  };

  // Pastikan pilihan default terisi walau suplier/produk baru ditambahkan setelah mount
  const openRestockModal = () => {
    setRestockForm(prev => ({
      ...prev,
      supplierId: prev.supplierId || suppliers[0]?.id || '',
      productId: prev.productId || products[0]?.id || '',
      buyPrice: ''
    }));
    setIsRestockModalOpen(true);
  };

  const selectedRestockProduct = products.find(p => p.id === restockForm.productId) || null;
  const restockUnitPrice = restockForm.buyPrice
    ? num(restockForm.buyPrice)
    : num(selectedRestockProduct?.buyPrice);
  const restockTotal = restockUnitPrice * num(restockForm.qty);

  const stats = useMemo(() => ({
    suppliers: suppliers.length,
    products: products.length,
    lowStock: products.filter(p => p.stock <= p.minStock).length,
    stockValue: products.reduce((sum, p) => sum + num(p.buyPrice) * num(p.stock), 0)
  }), [suppliers, products]);

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      {/* Banner */}
      <div className="a18-stripes-soft flex flex-col gap-4 rounded-3xl p-6 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Panel Showroom</span>
          <h1 className="a18-heading mt-1 text-2xl sm:text-3xl">Suplier &amp; Stok Masuk</h1>
          <p className="mt-1.5 max-w-xl text-sm text-neutral-400">
            Data distributor oli, ban, aki, dan aksesoris Auto18 beserta pencatatan faktur barang masuk ke gudang
            showroom.
          </p>
        </div>
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
          <button type="button" onClick={openRestockModal} className="a18-btn-primary shrink-0 px-5 py-3">
            <PackagePlus className="h-4 w-4" aria-hidden="true" />
            Catat Stok Masuk
          </button>
          <button
            type="button"
            onClick={() => setIsSupplierModalOpen(true)}
            className="a18-btn-outline shrink-0 px-5 py-2.5"
          >
            <Plus className="h-4 w-4" aria-hidden="true" />
            Tambah Suplier
          </button>
        </div>
      </div>

      {/* Ringkasan */}
      <div className="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <StatCard icon={Truck} label="Total Suplier" value={stats.suppliers} hint="mitra aktif" />
        <StatCard icon={Package} label="Produk Terdaftar" value={stats.products} hint="item di katalog" />
        <StatCard
          icon={AlertTriangle}
          label="Perlu Restock"
          value={stats.lowStock}
          tone="warning"
          hint="stok di bawah minimum"
        />
        <StatCard icon={Building2} label="Nilai Stok" value={formatRupiah(stats.stockValue)} hint="modal barang di gudang" />
      </div>

      {/* Supplier Cards */}
      {suppliers.length === 0 ? (
        <div className="a18-card mt-6 p-10 text-center">
          <Truck className="mx-auto h-8 w-8 text-neutral-600" aria-hidden="true" />
          <p className="mt-3 font-bold text-white">Belum ada data suplier</p>
          <p className="mt-1 text-sm text-neutral-500">
            Tambahkan distributor sparepart atau aksesoris untuk mulai mencatat barang masuk.
          </p>
          <button
            type="button"
            onClick={() => setIsSupplierModalOpen(true)}
            className="a18-btn-primary mx-auto mt-4"
          >
            <Plus className="h-4 w-4" aria-hidden="true" />
            Tambah Suplier
          </button>
        </div>
      ) : (
        <div className="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
          {suppliers.map(s => (
            <div key={s.id} className="a18-card space-y-3 p-5 transition-colors hover:border-brand-600">
              <div className="flex items-start justify-between gap-2">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600/15 text-brand-400">
                  <Truck className="h-5 w-5" aria-hidden="true" />
                </div>
                <span className="rounded-full border border-white/10 bg-white/5 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-neutral-300">
                  Distributor
                </span>
              </div>

              <div>
                <h4 className="font-display text-base font-black uppercase tracking-tight text-white">{s.name}</h4>
                <p className="mt-1 flex items-center gap-1.5 text-xs text-neutral-400">
                  <User className="h-3.5 w-3.5 shrink-0 text-neutral-500" aria-hidden="true" />
                  <span className="truncate">PIC: {s.contactPerson || '-'}</span>
                </p>
              </div>

              {Array.isArray(s.categories) && s.categories.length > 0 && (
                <div className="flex flex-wrap gap-1.5">
                  {s.categories.map(cat => (
                    <span
                      key={cat}
                      className="rounded-md border border-white/10 bg-white/5 px-2 py-0.5 text-[10px] font-medium text-neutral-300"
                    >
                      {cat}
                    </span>
                  ))}
                </div>
              )}

              <div className="space-y-1.5 border-t border-white/10 pt-3 text-xs text-neutral-400">
                <div className="flex items-center gap-2">
                  <Phone className="h-3.5 w-3.5 shrink-0 text-neutral-500" aria-hidden="true" />
                  {s.phone ? (
                    <a href={`tel:${String(s.phone).replace(/[^0-9+]/g, '')}`} className="transition-colors hover:text-white">
                      {s.phone}
                    </a>
                  ) : (
                    <span>-</span>
                  )}
                </div>
                <div className="flex items-center gap-2">
                  <MapPin className="h-3.5 w-3.5 shrink-0 text-neutral-500" aria-hidden="true" />
                  <span className="truncate">{s.address || '-'}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Restock Modal */}
      {isRestockModalOpen && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
          role="dialog"
          aria-modal="true"
          aria-label="Pencatatan barang masuk"
        >
          <div className="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-3xl border border-white/10 bg-ink-800 p-6">
            <div className="flex items-center justify-between gap-3">
              <h3 className="a18-heading text-base">Catat Barang Masuk</h3>
              <button
                type="button"
                onClick={() => setIsRestockModalOpen(false)}
                aria-label="Tutup"
                className="rounded-lg p-1.5 text-neutral-400 transition-colors hover:bg-white/10 hover:text-white"
              >
                <X className="h-5 w-5" aria-hidden="true" />
              </button>
            </div>
            <p className="mt-1 text-xs text-neutral-500">
              Stok produk bertambah otomatis begitu faktur suplier dicatat.
            </p>

            <form onSubmit={handleRestock} className="mt-4 space-y-4">
              <div>
                <label className="a18-label" htmlFor="restock-supplier">Pilih Suplier</label>
                <select
                  id="restock-supplier"
                  value={restockForm.supplierId}
                  onChange={(e) => setRestockForm({ ...restockForm, supplierId: e.target.value })}
                  className="a18-input"
                >
                  {suppliers.length === 0 && <option value="">Belum ada suplier</option>}
                  {suppliers.map(s => (
                    <option key={s.id} value={s.id}>{s.name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="a18-label" htmlFor="restock-product">Produk yang Masuk</label>
                <select
                  id="restock-product"
                  value={restockForm.productId}
                  onChange={(e) => setRestockForm({ ...restockForm, productId: e.target.value, buyPrice: '' })}
                  className="a18-input"
                >
                  {products.length === 0 && <option value="">Belum ada produk</option>}
                  {products.map(p => (
                    <option key={p.id} value={p.id}>{p.name} (stok: {p.stock} {p.unit})</option>
                  ))}
                </select>
                {selectedRestockProduct && selectedRestockProduct.stock <= selectedRestockProduct.minStock && (
                  <p className="mt-1.5 flex items-center gap-1.5 text-[11px] font-semibold text-amber-400">
                    <AlertTriangle className="h-3 w-3" aria-hidden="true" />
                    Stok tinggal {selectedRestockProduct.stock} {selectedRestockProduct.unit}, di bawah minimum
                    ({selectedRestockProduct.minStock}).
                  </p>
                )}
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="a18-label" htmlFor="restock-qty">Jumlah Masuk</label>
                  <input
                    id="restock-qty"
                    type="number"
                    required
                    min="1"
                    value={restockForm.qty}
                    onChange={(e) => setRestockForm({ ...restockForm, qty: e.target.value })}
                    className="a18-input font-mono"
                  />
                </div>
                <div>
                  <label className="a18-label" htmlFor="restock-price">Harga Modal / Satuan</label>
                  <input
                    id="restock-price"
                    type="number"
                    min="0"
                    value={restockForm.buyPrice}
                    onChange={(e) => setRestockForm({ ...restockForm, buyPrice: e.target.value })}
                    placeholder={selectedRestockProduct ? String(selectedRestockProduct.buyPrice) : '0'}
                    className="a18-input font-mono"
                  />
                </div>
              </div>

              <div>
                <label className="a18-label" htmlFor="restock-invoice">No. Faktur (Opsional)</label>
                <input
                  id="restock-invoice"
                  type="text"
                  placeholder="INV-A18-0001"
                  value={restockForm.invoiceNo}
                  onChange={(e) => setRestockForm({ ...restockForm, invoiceNo: e.target.value })}
                  className="a18-input font-mono text-xs"
                />
              </div>

              <div className="flex items-center justify-between rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-xs">
                <span className="font-bold uppercase tracking-wider text-neutral-500">Perkiraan nilai faktur</span>
                <span className="font-mono font-bold text-white">{formatRupiah(restockTotal)}</span>
              </div>

              <div className="flex justify-end gap-2 border-t border-white/10 pt-4">
                <button type="button" onClick={() => setIsRestockModalOpen(false)} className="a18-btn-ghost">
                  Batal
                </button>
                <button type="submit" disabled={!restockForm.productId} className="a18-btn-primary">
                  Tambah Stok
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Add Supplier Modal */}
      {isSupplierModalOpen && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
          role="dialog"
          aria-modal="true"
          aria-label="Tambah data suplier"
        >
          <div className="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-3xl border border-white/10 bg-ink-800 p-6">
            <div className="flex items-center justify-between gap-3">
              <h3 className="a18-heading text-base">Tambah Data Suplier</h3>
              <button
                type="button"
                onClick={() => setIsSupplierModalOpen(false)}
                aria-label="Tutup"
                className="rounded-lg p-1.5 text-neutral-400 transition-colors hover:bg-white/10 hover:text-white"
              >
                <X className="h-5 w-5" aria-hidden="true" />
              </button>
            </div>

            <form onSubmit={handleCreateSupplier} className="mt-4 space-y-3">
              <div>
                <label className="a18-label" htmlFor="sup-name">Nama Suplier / Perusahaan</label>
                <input
                  id="sup-name"
                  type="text"
                  required
                  value={supForm.name}
                  onChange={(e) => setSupForm({ ...supForm, name: e.target.value })}
                  placeholder="Contoh: PT Sinar Pelumas Nusantara"
                  className="a18-input"
                />
              </div>

              <div>
                <label className="a18-label" htmlFor="sup-pic">Nama Sales / PIC</label>
                <input
                  id="sup-pic"
                  type="text"
                  required
                  value={supForm.contactPerson}
                  onChange={(e) => setSupForm({ ...supForm, contactPerson: e.target.value })}
                  placeholder="Contoh: Bpk. Rudi Hartono"
                  className="a18-input"
                />
              </div>

              <div>
                <label className="a18-label" htmlFor="sup-phone">Nomor Telepon / WhatsApp</label>
                <input
                  id="sup-phone"
                  type="text"
                  required
                  value={supForm.phone}
                  onChange={(e) => setSupForm({ ...supForm, phone: e.target.value })}
                  placeholder="0812-7788-1801"
                  className="a18-input"
                />
              </div>

              <div>
                <label className="a18-label" htmlFor="sup-address">Alamat Gudang / Kantor</label>
                <input
                  id="sup-address"
                  type="text"
                  value={supForm.address}
                  onChange={(e) => setSupForm({ ...supForm, address: e.target.value })}
                  placeholder="Jl. Daan Mogot KM 12, Jakarta Barat"
                  className="a18-input"
                />
              </div>

              <div className="flex justify-end gap-2 border-t border-white/10 pt-4">
                <button type="button" onClick={() => setIsSupplierModalOpen(false)} className="a18-btn-ghost">
                  Batal
                </button>
                <button type="submit" className="a18-btn-primary">
                  Simpan Suplier
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

    </div>
  );
}
