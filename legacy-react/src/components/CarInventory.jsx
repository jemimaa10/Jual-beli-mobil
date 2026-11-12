import React, { useMemo, useState } from 'react';
import { motion } from 'motion/react';
import {
  Plus, Search, Pencil, Trash2, Star, Receipt, Eye, CarFront, Wallet, TrendingUp,
  CalendarCheck, Phone, MapPin, ArrowLeftRight, CheckCircle2, XCircle, ClipboardList
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import CarImage from './CarImage';
import CarFormModal from './CarFormModal';
import { CAR_BRANDS } from '../data/initialData';
import {
  CAR_STATUS, SELL_REQUEST_STATUS, TEST_DRIVE_STATUS, estimateCarPrice, formatKm, formatRupiahShort, getCarName
} from '../utils/carUtils';
import { formatDate, formatRupiah } from '../utils/formatters';
import { toast } from '../utils/toast';

// Latar tab aktif bergeser ke tab yang baru dipilih (layoutId yang sama)
const TAB_SPRING = { type: 'spring', bounce: 0.15, duration: 0.35 };
// Sudut atas mengikuti rounded-t-xl tombol. Radius lewat style agar Motion
// mengoreksinya terhadap skala saat latar melebar/menyempit ke tab lain.
const TAB_PILL_RADIUS = {
  borderTopLeftRadius: 12,
  borderTopRightRadius: 12,
  borderBottomLeftRadius: 0,
  borderBottomRightRadius: 0,
};

const todayLocal = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const formatDay = (value) => {
  if (!value) return '-';
  const [y, m, d] = String(value).split('-').map(Number);
  if (!y || !m || !d) return value;
  return new Intl.DateTimeFormat('id-ID', { weekday: 'short', day: 'numeric', month: 'short' }).format(new Date(y, m - 1, d));
};

const roundTo = (value, step) => Math.round(value / step) * step;

function StatCard({ icon: Icon, label, value, valueTitle, hint, onClick }) {
  const Wrapper = onClick ? 'button' : 'div';
  return (
    <Wrapper
      {...(onClick ? { type: 'button', onClick } : {})}
      className={`a18-card min-w-0 p-4 text-left ${onClick ? 'transition-colors hover:border-brand-600' : ''}`}
    >
      <div className="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-neutral-500">
        <Icon className="h-3.5 w-3.5 text-brand-500" aria-hidden="true" />
        {label}
      </div>
      {/* formatRupiah pakai spasi tak terputus dan bisa lebih lebar dari kartu di grid
          6 kolom (lg/xl) maupun grid 2 kolom (HP) — value ini sudah dipendekkan oleh
          pemanggil bila perlu; title menampilkan nominal lengkap saat hover. */}
      <p className="mt-1.5 truncate font-display text-xl font-black text-white" title={valueTitle}>{value}</p>
      {hint && <p className="text-xs text-neutral-500">{hint}</p>}
    </Wrapper>
  );
}

export default function CarInventory() {
  const {
    cars, deleteCar, setCarStatus, voidCarSale, updateCar, openCarDetail, openCarCheckout,
    sellRequests, updateSellRequest, deleteSellRequest, purchaseFromSellRequest,
    testDrives, updateTestDrive, deleteTestDrive,
    transactions, setCurrentReceipt, setIsReceiptModalOpen,
  } = useApp();

  const [tab, setTab] = useState('stock');
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [brandFilter, setBrandFilter] = useState('all');
  const [sort, setSort] = useState('newest');
  const [formCar, setFormCar] = useState(null); // objek mobil untuk diedit
  const [isFormOpen, setIsFormOpen] = useState(false);

  const [requestStatusFilter, setRequestStatusFilter] = useState('all');
  const [requestTypeFilter, setRequestTypeFilter] = useState('all');
  const [requestDraft, setRequestDraft] = useState({}); // { [id]: { inspectionDate, inspectionTime, offerPrice, sellPrice } }

  const [testDriveFilter, setTestDriveFilter] = useState('all');
  const [onlyToday, setOnlyToday] = useState(false);

  const newRequestCount = sellRequests.filter(r => r.status === 'new').length;
  const pendingTestDrives = testDrives.filter(t => t.status === 'pending').length;

  // ---------------------------------------------------------------- Statistik
  const stats = useMemo(() => {
    const now = new Date();
    const soldThisMonth = cars.filter(c => {
      if (c.status !== 'sold' || !c.soldAt) return false;
      const d = new Date(c.soldAt);
      return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
    });
    const unsold = cars.filter(c => c.status !== 'sold');
    return {
      total: cars.length,
      available: cars.filter(c => c.status === 'available').length,
      booked: cars.filter(c => c.status === 'booked').length,
      soldThisMonth: soldThisMonth.length,
      stockValue: unsold.reduce((sum, c) => sum + (c.buyPrice || 0), 0),
      potentialProfit: unsold.reduce((sum, c) => sum + ((c.price || 0) - (c.buyPrice || 0)), 0),
    };
  }, [cars]);

  // ---------------------------------------------------------------- Stok mobil
  const filteredCars = useMemo(() => {
    const keyword = search.trim().toLowerCase();
    return cars
      .filter(car => {
        if (statusFilter !== 'all' && car.status !== statusFilter) return false;
        if (brandFilter !== 'all' && car.brand !== brandFilter) return false;
        if (keyword) {
          const haystack = `${car.brand} ${car.model} ${car.variant} ${car.code} ${car.plate}`.toLowerCase();
          if (!haystack.includes(keyword)) return false;
        }
        return true;
      })
      .sort((a, b) => {
        switch (sort) {
          case 'price': return b.price - a.price;
          case 'margin': return (b.price - b.buyPrice) - (a.price - a.buyPrice);
          case 'year': return b.year - a.year;
          default: return new Date(b.createdAt) - new Date(a.createdAt);
        }
      });
  }, [cars, search, statusFilter, brandFilter, sort]);

  const openReceipt = (car) => {
    const trx = transactions.find(t => t.id === car.soldTransactionId);
    if (!trx) {
      toast.error('Nota penjualan tidak ditemukan.');
      return;
    }
    setCurrentReceipt(trx);
    setIsReceiptModalOpen(true);
  };

  const removeCar = (car) => {
    if (confirm(`Hapus ${getCarName(car, { withYear: true })} dari stok?`)) deleteCar(car.id);
  };

  const cancelSoldStatus = (car) => {
    if (confirm(
      'Batalkan status terjual?\n\n' +
      'Transaksi penjualannya akan ditandai "Dibatalkan" dan tidak lagi dihitung di omset/laba Dashboard & Laporan ' +
      '(tetap tercatat di riwayat). Mobil ini kembali berstatus Tersedia.\n\n' +
      'Kalau penjualan ini memakai tukar tambah, mobil lama yang sudah masuk stok TIDAK ikut ditarik kembali.'
    )) {
      voidCarSale(car.id);
    }
  };

  const carActions = (car) => (
    <div className="flex items-center gap-1 whitespace-nowrap max-md:flex-wrap">
      <button type="button" onClick={() => openCarDetail(car.id)} title="Lihat detail" aria-label="Lihat detail" className="a18-btn-ghost px-2">
        <Eye className="h-4 w-4" />
      </button>
      <button type="button" onClick={() => { setFormCar(car); setIsFormOpen(true); }} title="Edit mobil" aria-label="Edit mobil" className="a18-btn-ghost px-2">
        <Pencil className="h-4 w-4" />
      </button>
      <button
        type="button"
        onClick={() => updateCar(car.id, { isFeatured: !car.isFeatured })}
        title={car.isFeatured ? 'Hapus dari Pilihan Auto18' : 'Jadikan Pilihan Auto18'}
        aria-label="Tandai pilihan Auto18"
        aria-pressed={Boolean(car.isFeatured)}
        className="a18-btn-ghost px-2"
      >
        <Star className={`h-4 w-4 ${car.isFeatured ? 'fill-brand-500 text-brand-500' : ''}`} />
      </button>
      {car.status === 'sold' ? (
        <button type="button" onClick={() => openReceipt(car)} title="Lihat nota" className="a18-btn-ghost px-2 text-xs">
          <Receipt className="h-4 w-4" />
        </button>
      ) : (
        <button type="button" onClick={() => openCarCheckout(car.id)} className="a18-btn-primary px-3 py-1.5 text-xs">
          Jual
        </button>
      )}
      <button type="button" onClick={() => removeCar(car)} title="Hapus mobil" aria-label="Hapus mobil" className="a18-btn-ghost px-2 text-neutral-500 hover:text-brand-400">
        <Trash2 className="h-4 w-4" />
      </button>
    </div>
  );

  const statusControl = (car) => {
    if (car.status === 'sold') {
      return (
        <div className="space-y-1">
          <span className={`inline-block rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase ${CAR_STATUS.sold.className}`}>
            Terjual {car.soldAt ? formatDate(car.soldAt, false) : ''}
          </span>
          <button type="button" onClick={() => cancelSoldStatus(car)} className="block text-[10px] text-neutral-500 underline hover:text-brand-400">
            Batalkan status terjual
          </button>
        </div>
      );
    }
    return (
      <select
        value={car.status}
        onChange={(e) => setCarStatus(car.id, e.target.value)}
        aria-label={`Status ${getCarName(car)}`}
        className="a18-input min-w-[118px] py-1.5 text-xs"
      >
        <option value="available">Tersedia</option>
        <option value="booked">Dipesan</option>
      </select>
    );
  };

  // ---------------------------------------------------------------- Permintaan jual
  const filteredRequests = useMemo(() => {
    return sellRequests
      .filter(r => (requestStatusFilter === 'all' || r.status === requestStatusFilter))
      .filter(r => (requestTypeFilter === 'all' || r.type === requestTypeFilter))
      .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
  }, [sellRequests, requestStatusFilter, requestTypeFilter]);

  const draftFor = (request) => {
    const estimate = request.estimateLow > 0
      ? { low: request.estimateLow, high: request.estimateHigh, mid: Math.round((request.estimateLow + request.estimateHigh) / 2) }
      : estimateCarPrice({
          brand: request.brand, model: request.model, bodyType: request.bodyType,
          year: request.year, mileage: request.mileage, transmission: request.transmission, condition: request.condition,
        });
    const offer = requestDraft[request.id]?.offerPrice ?? (request.offerPrice || estimate.mid);
    return {
      estimate,
      inspectionDate: requestDraft[request.id]?.inspectionDate ?? (request.inspectionDate || todayLocal()),
      inspectionTime: requestDraft[request.id]?.inspectionTime ?? (request.inspectionTime || '10:00'),
      offerPrice: offer,
      sellPrice: requestDraft[request.id]?.sellPrice ?? roundTo(offer * 1.12, 500000),
    };
  };

  const patchDraft = (id, patch) => setRequestDraft(prev => ({ ...prev, [id]: { ...prev[id], ...patch } }));

  const buyFromRequest = (request) => {
    const draft = draftFor(request);
    const car = purchaseFromSellRequest(request.id, {
      offerPrice: Number(draft.offerPrice) || 0,
      sellPrice: Number(draft.sellPrice) || 0,
    });
    if (car) {
      setTab('stock');
      setSearch(car.code);
      setStatusFilter('all');
      setBrandFilter('all');
    }
  };

  // ---------------------------------------------------------------- Test drive
  const filteredTestDrives = useMemo(() => {
    const today = todayLocal();
    return testDrives
      .filter(t => (testDriveFilter === 'all' || t.status === testDriveFilter))
      .filter(t => (!onlyToday || t.date === today))
      .sort((a, b) => {
        const aUpcoming = a.date >= today ? 0 : 1;
        const bUpcoming = b.date >= today ? 0 : 1;
        if (aUpcoming !== bUpcoming) return aUpcoming - bUpcoming;
        return `${a.date} ${a.time}`.localeCompare(`${b.date} ${b.time}`) * (aUpcoming === 0 ? 1 : -1);
      });
  }, [testDrives, testDriveFilter, onlyToday]);

  const TABS = [
    { id: 'stock', label: 'Stok Mobil', icon: CarFront, badge: 0 },
    { id: 'requests', label: 'Jual & Tukar Tambah', icon: ArrowLeftRight, badge: newRequestCount },
    { id: 'testdrive', label: 'Test Drive', icon: CalendarCheck, badge: pendingTestDrives },
  ];

  return (
    <div className="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
      {/* Banner */}
      <div className="a18-stripes-soft flex flex-col gap-4 rounded-3xl p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Panel Showroom</span>
          <h1 className="a18-heading mt-1 text-2xl sm:text-3xl">Stok Mobil & Permintaan</h1>
          <p className="mt-1.5 max-w-xl text-sm text-neutral-400">
            Kelola unit mobil bekas, permintaan jual/tukar tambah pelanggan, dan jadwal test drive.
          </p>
        </div>
        <button type="button" onClick={() => { setFormCar(null); setIsFormOpen(true); }} className="a18-btn-primary shrink-0 px-5 py-3">
          <Plus className="h-4 w-4" />
          Tambah Mobil
        </button>
      </div>

      {/* KPI */}
      <div className="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-6">
        <StatCard icon={CarFront} label="Total Unit" value={stats.total} />
        <StatCard icon={CheckCircle2} label="Tersedia" value={stats.available} onClick={() => { setTab('stock'); setStatusFilter('available'); }} />
        <StatCard icon={ClipboardList} label="Dipesan" value={stats.booked} onClick={() => { setTab('stock'); setStatusFilter('booked'); }} />
        <StatCard icon={Receipt} label="Terjual Bulan Ini" value={stats.soldThisMonth} onClick={() => { setTab('stock'); setStatusFilter('sold'); }} />
        <StatCard
          icon={Wallet}
          label="Nilai Stok"
          value={formatRupiahShort(stats.stockValue)}
          valueTitle={formatRupiah(stats.stockValue)}
          hint="modal unit belum terjual"
        />
        <StatCard
          icon={TrendingUp}
          label="Potensi Laba"
          value={formatRupiahShort(stats.potentialProfit)}
          valueTitle={formatRupiah(stats.potentialProfit)}
          hint="jika semua terjual"
        />
      </div>

      {/* Tab — isolate: z-index latar & teks tidak bocor keluar baris tab */}
      <div className="isolate mt-6 flex gap-2 overflow-x-auto border-b border-white/10 pb-px">
        {TABS.map(({ id, label, icon: Icon, badge }) => (
          <button
            key={id}
            type="button"
            onClick={() => setTab(id)}
            aria-current={tab === id ? 'page' : undefined}
            className={`relative flex shrink-0 items-center gap-2 rounded-t-xl px-4 py-2.5 text-sm font-bold transition-colors ${
              tab === id ? 'text-white' : 'text-neutral-400 hover:bg-white/5 hover:text-white'
            }`}
          >
            {tab === id && (
              // layoutDependency: hanya bergeser saat tab diganti, bukan pada render ulang lain
              <motion.span
                layoutId="car-inventory-tab-pill"
                layoutDependency={tab}
                transition={TAB_SPRING}
                aria-hidden="true"
                className="absolute inset-0 z-[1] bg-brand-600"
                style={TAB_PILL_RADIUS}
              />
            )}
            <Icon className="relative z-10 h-4 w-4" aria-hidden="true" />
            <span className="relative z-10">{label}</span>
            {badge > 0 && (
              <span className={`relative z-10 rounded-full px-1.5 py-0.5 text-[10px] font-black ${tab === id ? 'bg-white text-brand-700' : 'bg-brand-600 text-white'}`}>
                {badge}
              </span>
            )}
          </button>
        ))}
      </div>

      {/* ---------------------------------------------------------------- STOK */}
      {tab === 'stock' && (
        <div className="mt-6">
          <div className="flex flex-wrap items-end gap-3">
            <div className="min-w-[200px] flex-1">
              <label htmlFor="inv-search" className="a18-label">Cari</label>
              <div className="relative">
                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" aria-hidden="true" />
                <input
                  id="inv-search"
                  type="search"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  placeholder="Model, kode stok, atau plat"
                  className="a18-input pl-9"
                />
              </div>
            </div>
            <div>
              <label htmlFor="inv-status" className="a18-label">Status</label>
              <select id="inv-status" value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className="a18-input">
                <option value="all">Semua</option>
                <option value="available">Tersedia</option>
                <option value="booked">Dipesan</option>
                <option value="sold">Terjual</option>
              </select>
            </div>
            <div>
              <label htmlFor="inv-brand" className="a18-label">Merek</label>
              <select id="inv-brand" value={brandFilter} onChange={(e) => setBrandFilter(e.target.value)} className="a18-input">
                <option value="all">Semua</option>
                {CAR_BRANDS.map(brand => <option key={brand} value={brand}>{brand}</option>)}
              </select>
            </div>
            <div>
              <label htmlFor="inv-sort" className="a18-label">Urutkan</label>
              <select id="inv-sort" value={sort} onChange={(e) => setSort(e.target.value)} className="a18-input">
                <option value="newest">Terbaru</option>
                <option value="price">Harga Tertinggi</option>
                <option value="margin">Margin Tertinggi</option>
                <option value="year">Tahun Terbaru</option>
              </select>
            </div>
          </div>

          <p className="mt-3 text-xs text-neutral-500">{filteredCars.length} unit ditampilkan</p>

          {/* Tabel desktop */}
          <div className="mt-3 hidden overflow-x-auto rounded-2xl border border-white/10 md:block">
            <table className="w-full min-w-[920px] text-sm">
              <thead className="bg-ink-900 text-left text-[10px] uppercase tracking-wider text-neutral-500">
                <tr>
                  <th className="px-4 py-3">Mobil</th>
                  <th className="px-4 py-3">Spesifikasi</th>
                  <th className="px-3 py-3 text-right">Harga Jual</th>
                  <th className="hidden px-3 py-3 text-right 2xl:table-cell">Modal</th>
                  <th className="px-3 py-3 text-right">Margin</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {filteredCars.map(car => {
                  const margin = (car.price || 0) - (car.buyPrice || 0);
                  const marginPercent = car.buyPrice > 0 ? (margin / car.buyPrice) * 100 : 0;
                  return (
                    <tr key={car.id} className="border-t border-white/10 transition-colors hover:bg-white/5">
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-3">
                          <CarImage car={car} className="h-14 w-20 shrink-0 rounded-lg" />
                          <div className="min-w-0">
                            <p className="truncate font-bold text-white">{getCarName(car)}</p>
                            <p className="font-mono text-[11px] text-neutral-500">{car.code} · {car.plate || 'tanpa plat'}</p>
                          </div>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-xs text-neutral-400">
                        {car.year} · {formatKm(car.mileage)}<br />{car.transmission} · {car.fuel}
                      </td>
                      <td className="px-3 py-3 text-right font-semibold text-white">{formatRupiah(car.price)}</td>
                      <td className="hidden px-3 py-3 text-right text-neutral-400 2xl:table-cell">{formatRupiah(car.buyPrice)}</td>
                      <td className={`px-3 py-3 text-right font-semibold ${margin >= 0 ? 'text-emerald-400' : 'text-brand-400'}`}>
                        {formatRupiah(margin)}
                        <span className="block text-[10px] font-normal text-neutral-500">{marginPercent.toFixed(1)}%</span>
                      </td>
                      <td className="px-3 py-3">{statusControl(car)}</td>
                      <td className="px-3 py-3">{carActions(car)}</td>
                    </tr>
                  );
                })}
                {filteredCars.length === 0 && (
                  <tr>
                    <td colSpan={7} className="px-4 py-10 text-center text-sm text-neutral-500">Tidak ada mobil yang cocok.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          {/* Kartu mobile */}
          <div className="mt-3 space-y-3 md:hidden">
            {filteredCars.map(car => {
              const margin = (car.price || 0) - (car.buyPrice || 0);
              return (
                <div key={car.id} className="a18-card p-4">
                  <div className="flex gap-3">
                    <CarImage car={car} className="h-16 w-24 shrink-0 rounded-lg" />
                    <div className="min-w-0 flex-1">
                      <p className="truncate font-bold text-white">{getCarName(car)}</p>
                      <p className="font-mono text-[11px] text-neutral-500">{car.code}</p>
                      <p className="mt-1 text-xs text-neutral-400">{car.year} · {formatKm(car.mileage)} · {car.transmission}</p>
                    </div>
                  </div>
                  <div className="mt-3 flex items-center justify-between text-sm">
                    <span className="font-bold text-white">{formatRupiah(car.price)}</span>
                    <span className={margin >= 0 ? 'text-emerald-400' : 'text-brand-400'}>Margin {formatRupiah(margin)}</span>
                  </div>
                  <div className="mt-3">{statusControl(car)}</div>
                  <div className="mt-3 border-t border-white/10 pt-3">{carActions(car)}</div>
                </div>
              );
            })}
            {filteredCars.length === 0 && (
              <p className="a18-card p-8 text-center text-sm text-neutral-500">Tidak ada mobil yang cocok.</p>
            )}
          </div>
        </div>
      )}

      {/* ---------------------------------------------------------------- PERMINTAAN */}
      {tab === 'requests' && (
        <div className="mt-6">
          <div className="flex flex-wrap items-end gap-3">
            <div>
              <label htmlFor="req-status" className="a18-label">Status</label>
              <select id="req-status" value={requestStatusFilter} onChange={(e) => setRequestStatusFilter(e.target.value)} className="a18-input">
                <option value="all">Semua</option>
                {Object.entries(SELL_REQUEST_STATUS).map(([key, value]) => (
                  <option key={key} value={key}>{value.label}</option>
                ))}
              </select>
            </div>
            <div>
              <label htmlFor="req-type" className="a18-label">Jenis</label>
              <select id="req-type" value={requestTypeFilter} onChange={(e) => setRequestTypeFilter(e.target.value)} className="a18-input">
                <option value="all">Semua</option>
                <option value="sell">Jual Langsung</option>
                <option value="trade-in">Tukar Tambah</option>
              </select>
            </div>
          </div>

          <div className="mt-4 space-y-4">
            {filteredRequests.map(request => {
              const draft = draftFor(request);
              const status = SELL_REQUEST_STATUS[request.status];
              const targetCar = request.tradeInCarId ? cars.find(c => c.id === request.tradeInCarId) : null;
              const purchasedCar = request.purchasedCarId ? cars.find(c => c.id === request.purchasedCarId) : null;

              return (
                <div key={request.id} className="a18-card p-5">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <div className="flex flex-wrap items-center gap-2">
                        <span className="font-mono text-xs text-neutral-500">{request.code}</span>
                        <span className="rounded-full border border-white/15 px-2 py-0.5 text-[10px] font-bold uppercase text-neutral-300">
                          {request.type === 'trade-in' ? 'Tukar Tambah' : 'Jual Langsung'}
                        </span>
                        {status && (
                          <span className={`rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase ${status.className}`}>
                            {status.label}
                          </span>
                        )}
                      </div>
                      <h3 className="mt-2 font-display text-base font-black uppercase tracking-wide text-white">
                        {request.brand} {request.model} {request.variant} {request.year}
                      </h3>
                      <p className="mt-1 text-xs text-neutral-400">
                        {request.transmission} · {formatKm(request.mileage)} · {request.color} · {request.location} · Kondisi {request.condition}
                      </p>
                    </div>
                    <div className="text-right text-xs text-neutral-400">
                      <p className="font-bold text-white">{request.customerName}</p>
                      <a href={`tel:${String(request.phone).replace(/[^\d+]/g, '')}`} className="inline-flex items-center gap-1 hover:text-white">
                        <Phone className="h-3 w-3" aria-hidden="true" />
                        {request.phone}
                      </a>
                      <p className="mt-1 text-neutral-500">Masuk {formatDate(request.createdAt)}</p>
                    </div>
                  </div>

                  <dl className="mt-4 grid grid-cols-2 gap-3 border-t border-white/10 pt-4 text-xs sm:grid-cols-4">
                    <div>
                      <dt className="text-neutral-500">Estimasi Sistem</dt>
                      <dd className="font-semibold text-white">{formatRupiah(draft.estimate.low)} – {formatRupiah(draft.estimate.high)}</dd>
                    </div>
                    <div>
                      <dt className="text-neutral-500">Harga Diharapkan</dt>
                      <dd className="font-semibold text-white">{request.askingPrice > 0 ? formatRupiah(request.askingPrice) : '-'}</dd>
                    </div>
                    <div>
                      <dt className="text-neutral-500">Jadwal Inspeksi</dt>
                      <dd className="font-semibold text-white">{formatDay(request.inspectionDate)} · {request.inspectionTime || '-'}</dd>
                    </div>
                    <div>
                      <dt className="text-neutral-500">Penawaran</dt>
                      <dd className="font-semibold text-white">{request.offerPrice > 0 ? formatRupiah(request.offerPrice) : '-'}</dd>
                    </div>
                  </dl>

                  {targetCar && (
                    <p className="mt-3 text-xs text-neutral-400">
                      Ingin ditukar dengan{' '}
                      <button type="button" onClick={() => openCarDetail(targetCar.id)} className="font-bold text-brand-400 underline hover:text-brand-300">
                        {getCarName(targetCar, { withYear: true })}
                      </button>
                    </p>
                  )}
                  {request.notes && <p className="mt-2 text-xs italic text-neutral-500">"{request.notes}"</p>}
                  {purchasedCar && (
                    <p className="mt-2 text-xs text-emerald-400">
                      Sudah masuk stok sebagai{' '}
                      <button type="button" onClick={() => openCarDetail(purchasedCar.id)} className="underline">
                        {purchasedCar.code}
                      </button>
                    </p>
                  )}

                  {/* Aksi per status */}
                  <div className="mt-4 border-t border-white/10 pt-4">
                    {request.status === 'new' && (
                      <div className="flex flex-wrap items-end gap-3">
                        <div>
                          <label htmlFor={`insp-date-${request.id}`} className="a18-label">Tanggal Inspeksi</label>
                          <input
                            id={`insp-date-${request.id}`}
                            type="date"
                            min={todayLocal()}
                            value={draft.inspectionDate}
                            onChange={(e) => patchDraft(request.id, { inspectionDate: e.target.value })}
                            className="a18-input"
                          />
                        </div>
                        <div>
                          <label htmlFor={`insp-time-${request.id}`} className="a18-label">Jam</label>
                          <input
                            id={`insp-time-${request.id}`}
                            type="time"
                            value={draft.inspectionTime}
                            onChange={(e) => patchDraft(request.id, { inspectionTime: e.target.value })}
                            className="a18-input"
                          />
                        </div>
                        <button
                          type="button"
                          onClick={() => updateSellRequest(request.id, {
                            status: 'scheduled',
                            inspectionDate: draft.inspectionDate,
                            inspectionTime: draft.inspectionTime,
                          })}
                          className="a18-btn-primary"
                        >
                          <CalendarCheck className="h-4 w-4" />
                          Jadwalkan Inspeksi
                        </button>
                      </div>
                    )}

                    {request.status === 'scheduled' && (
                      <div className="flex flex-wrap items-end gap-3">
                        <div>
                          <label htmlFor={`offer-${request.id}`} className="a18-label">Penawaran Setelah Inspeksi (Rp)</label>
                          <input
                            id={`offer-${request.id}`}
                            type="number"
                            min={0}
                            step={500000}
                            value={draft.offerPrice}
                            onChange={(e) => patchDraft(request.id, { offerPrice: e.target.value })}
                            className="a18-input"
                          />
                        </div>
                        <button
                          type="button"
                          onClick={() => updateSellRequest(request.id, { status: 'inspected', offerPrice: Number(draft.offerPrice) || 0 })}
                          className="a18-btn-primary"
                        >
                          <CheckCircle2 className="h-4 w-4" />
                          Simpan Hasil Inspeksi
                        </button>
                      </div>
                    )}

                    {request.status === 'inspected' && (
                      <div className="flex flex-wrap items-end gap-3">
                        <div>
                          <label htmlFor={`buy-${request.id}`} className="a18-label">Harga Beli (Rp)</label>
                          <input
                            id={`buy-${request.id}`}
                            type="number"
                            min={0}
                            step={500000}
                            value={draft.offerPrice}
                            onChange={(e) => patchDraft(request.id, { offerPrice: e.target.value })}
                            className="a18-input"
                          />
                        </div>
                        <div>
                          <label htmlFor={`sell-${request.id}`} className="a18-label">Harga Jual Nanti (Rp)</label>
                          <input
                            id={`sell-${request.id}`}
                            type="number"
                            min={0}
                            step={500000}
                            value={draft.sellPrice}
                            onChange={(e) => patchDraft(request.id, { sellPrice: e.target.value })}
                            className="a18-input"
                          />
                        </div>
                        <button type="button" onClick={() => buyFromRequest(request)} className="a18-btn-primary">
                          <Wallet className="h-4 w-4" />
                          Beli & Masukkan Stok
                        </button>
                      </div>
                    )}

                    <div className="mt-3 flex flex-wrap gap-2">
                      {['new', 'scheduled', 'inspected'].includes(request.status) && (
                        <button
                          type="button"
                          onClick={() => updateSellRequest(request.id, { status: 'rejected' })}
                          className="a18-btn-ghost text-xs"
                        >
                          <XCircle className="h-4 w-4" />
                          Tolak
                        </button>
                      )}
                      <button
                        type="button"
                        onClick={() => { if (confirm(`Hapus permintaan ${request.code}?`)) deleteSellRequest(request.id); }}
                        className="a18-btn-ghost text-xs text-neutral-500 hover:text-brand-400"
                      >
                        <Trash2 className="h-4 w-4" />
                        Hapus
                      </button>
                    </div>
                  </div>
                </div>
              );
            })}

            {filteredRequests.length === 0 && (
              <p className="a18-card p-10 text-center text-sm text-neutral-500">Belum ada permintaan yang cocok.</p>
            )}
          </div>
        </div>
      )}

      {/* ---------------------------------------------------------------- TEST DRIVE */}
      {tab === 'testdrive' && (
        <div className="mt-6">
          <div className="flex flex-wrap items-end gap-3">
            <div>
              <label htmlFor="td-filter" className="a18-label">Status</label>
              <select id="td-filter" value={testDriveFilter} onChange={(e) => setTestDriveFilter(e.target.value)} className="a18-input">
                <option value="all">Semua</option>
                {Object.entries(TEST_DRIVE_STATUS).map(([key, value]) => (
                  <option key={key} value={key}>{value.label}</option>
                ))}
              </select>
            </div>
            <label className="flex items-center gap-2 pb-2.5 text-sm text-neutral-300">
              <input type="checkbox" checked={onlyToday} onChange={(e) => setOnlyToday(e.target.checked)} className="h-4 w-4 accent-brand-600" />
              Hanya hari ini
            </label>
          </div>

          <div className="mt-4 space-y-3">
            {filteredTestDrives.map(booking => {
              const status = TEST_DRIVE_STATUS[booking.status];
              const car = cars.find(c => c.id === booking.carId);
              const isToday = booking.date === todayLocal();

              return (
                <div key={booking.id} className={`a18-card p-4 ${isToday ? 'border-brand-600/60' : ''}`}>
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <span className="font-mono text-xs text-neutral-500">{booking.code}</span>
                        {status && (
                          <span className={`rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase ${status.className}`}>
                            {status.label}
                          </span>
                        )}
                        {isToday && (
                          <span className="rounded-full bg-brand-600 px-2 py-0.5 text-[10px] font-black uppercase text-white">Hari Ini</span>
                        )}
                      </div>
                      <p className="mt-1.5 font-bold text-white">
                        {car ? (
                          <button type="button" onClick={() => openCarDetail(car.id)} className="underline decoration-brand-600 underline-offset-4 hover:text-brand-400">
                            {booking.carName}
                          </button>
                        ) : booking.carName}
                      </p>
                      <p className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-neutral-400">
                        <span className="inline-flex items-center gap-1">
                          <CalendarCheck className="h-3.5 w-3.5" aria-hidden="true" />
                          {formatDay(booking.date)} · {booking.time}
                        </span>
                        <span className="inline-flex items-center gap-1">
                          <MapPin className="h-3.5 w-3.5" aria-hidden="true" />
                          {booking.location === 'rumah' ? `Di rumah${booking.address ? ` · ${booking.address}` : ''}` : 'Di showroom'}
                        </span>
                      </p>
                      {booking.notes && <p className="mt-1 text-xs italic text-neutral-500">"{booking.notes}"</p>}
                    </div>

                    <div className="text-right text-xs text-neutral-400">
                      <p className="font-bold text-white">{booking.customerName}</p>
                      <a href={`tel:${String(booking.phone).replace(/[^\d+]/g, '')}`} className="inline-flex items-center gap-1 hover:text-white">
                        <Phone className="h-3 w-3" aria-hidden="true" />
                        {booking.phone}
                      </a>
                    </div>
                  </div>

                  <div className="mt-3 flex flex-wrap gap-2 border-t border-white/10 pt-3">
                    {booking.status === 'pending' && (
                      <button type="button" onClick={() => updateTestDrive(booking.id, { status: 'confirmed' })} className="a18-btn-primary px-3 py-1.5 text-xs">
                        Konfirmasi
                      </button>
                    )}
                    {['pending', 'confirmed'].includes(booking.status) && (
                      <>
                        <button type="button" onClick={() => updateTestDrive(booking.id, { status: 'done' })} className="a18-btn-outline px-3 py-1.5 text-xs">
                          Selesai
                        </button>
                        <button type="button" onClick={() => updateTestDrive(booking.id, { status: 'cancelled' })} className="a18-btn-ghost text-xs">
                          Batalkan
                        </button>
                      </>
                    )}
                    <button
                      type="button"
                      onClick={() => { if (confirm(`Hapus booking ${booking.code}?`)) deleteTestDrive(booking.id); }}
                      className="a18-btn-ghost text-xs text-neutral-500 hover:text-brand-400"
                    >
                      <Trash2 className="h-4 w-4" />
                      Hapus
                    </button>
                  </div>
                </div>
              );
            })}

            {filteredTestDrives.length === 0 && (
              <p className="a18-card p-10 text-center text-sm text-neutral-500">Belum ada jadwal test drive.</p>
            )}
          </div>
        </div>
      )}

      {isFormOpen && (
        <CarFormModal car={formCar} onClose={() => { setIsFormOpen(false); setFormCar(null); }} />
      )}
    </div>
  );
}
