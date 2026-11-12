import React, { useMemo, useState } from 'react';
import {
  Download,
  Printer,
  Search,
  CarFront,
  ShoppingBag,
  TrendingUp,
  Wallet,
  Coins,
  ReceiptText
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import { formatRupiah, formatDate } from '../utils/formatters';
import { formatRupiahShort, getCarName, PAYMENT_METHOD_LABEL } from '../utils/carUtils';
import { isActiveTransaction, netRevenue } from '../utils/transactions';
import { toast } from '../utils/toast';

// Kunci tanggal LOKAL (YYYY-MM-DD) — toISOString() memakai UTC dan meleset di WIB.
const localDateKey = (value) => {
  const d = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(d.getTime())) return '';
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const localTime = (value) => {
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return '';
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};

const isCarSale = (trx) => trx.saleType === 'car';

// Modal (HPP) transaksi = harga beli x qty setiap item
const hppOf = (trx) => (trx.items || []).reduce((acc, item) => acc + (Number(item.buyPrice) || 0) * (Number(item.qty) || 0), 0);

const profitOf = (trx) => (
  typeof trx.totalProfit === 'number' ? trx.totalProfit : (Number(trx.total) || 0) - hppOf(trx)
);

const unitsOf = (trx) => (trx.items || []).reduce((acc, item) => acc + (Number(item.qty) || 0), 0);

// Ringkasan item untuk tabel: mobil pakai nama mobil, retail pakai item pertama
const summaryOf = (trx) => {
  if (isCarSale(trx)) return getCarName(trx.car, { withYear: true }) || trx.items?.[0]?.name || 'Mobil';
  const items = trx.items || [];
  if (items.length === 0) return '-';
  return items.length > 1 ? `${items[0].name} +${items.length - 1} item lain` : items[0].name;
};

// Escape CSV: bungkus kutip ganda & gandakan kutip di dalam nilai
// (nama produk memuat koma dan kutip, mis. 'Wiper Bosch Clear Advantage 22"')
const csvCell = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`;

function SummaryCard({ icon: Icon, label, value, hint, valueClassName = 'text-white', hintClassName = 'text-neutral-500', accent = false }) {
  return (
    <div className="a18-card p-4 sm:p-5">
      <div className="flex items-start justify-between gap-2">
        <span className="text-[10px] font-bold uppercase tracking-wider text-neutral-500">{label}</span>
        <span className={`rounded-xl p-2 ${accent ? 'bg-brand-600/15 text-brand-400' : 'bg-white/5 text-neutral-300'}`}>
          <Icon className="h-4 w-4" aria-hidden="true" />
        </span>
      </div>
      <p className={`mt-2 font-display text-lg font-black leading-tight sm:text-xl ${valueClassName}`}>{value}</p>
      {hint && <p className={`mt-1 text-xs ${hintClassName}`}>{hint}</p>}
    </div>
  );
}

function TypeBadge({ carSale }) {
  return (
    <span className={`inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase ${
      carSale ? 'border-brand-500/40 bg-brand-600/20 text-brand-300' : 'border-white/15 bg-white/5 text-neutral-300'
    }`}>
      {carSale ? <CarFront className="h-3 w-3" aria-hidden="true" /> : <ShoppingBag className="h-3 w-3" aria-hidden="true" />}
      {carSale ? 'Mobil' : 'Aksesoris'}
    </span>
  );
}

function MethodCell({ trx }) {
  const label = PAYMENT_METHOD_LABEL[trx.paymentMethod] || trx.paymentMethod || '-';
  return (
    <div>
      <span className="text-xs font-semibold text-white">{label}</span>
      {trx.paymentMethod === 'credit' && trx.credit && (
        <span className="block text-[10px] text-neutral-500">
          {trx.credit.leasing} · {trx.credit.tenorMonths} bln · {formatRupiahShort(trx.credit.monthly)}/bln
        </span>
      )}
    </div>
  );
}

export default function SalesReports() {
  const { transactions, setCurrentReceipt, setIsReceiptModalOpen } = useApp();

  const [dateFilter, setDateFilter] = useState('all'); // 'today' | 'week' | 'month' | 'all'
  const [typeFilter, setTypeFilter] = useState('all'); // 'all' | 'car' | 'retail'
  const [methodFilter, setMethodFilter] = useState('all');
  const [search, setSearch] = useState('');

  const todayKey = localDateKey(new Date());

  // Awal jendela 7 hari (hari ini + 6 hari ke belakang), dihitung lokal
  const weekStart = new Date();
  weekStart.setHours(0, 0, 0, 0);
  weekStart.setDate(weekStart.getDate() - 6);
  const weekStartKey = localDateKey(weekStart);

  const filtered = useMemo(() => {
    const keyword = search.trim().toLowerCase();
    const now = new Date();

    return transactions.filter(t => {
      // Pencarian: kode nota, nama pelanggan, atau nama item / mobil
      if (keyword) {
        const haystack = [
          t.code,
          t.customerName,
          ...(t.items || []).map(i => i.name),
          isCarSale(t) ? getCarName(t.car, { withYear: true }) : ''
        ].filter(Boolean).join(' ').toLowerCase();
        if (!haystack.includes(keyword)) return false;
      }

      if (typeFilter === 'car' && !isCarSale(t)) return false;
      if (typeFilter === 'retail' && isCarSale(t)) return false;
      if (methodFilter !== 'all' && t.paymentMethod !== methodFilter) return false;

      const key = localDateKey(t.date);
      if (dateFilter === 'today') return key === todayKey;
      if (dateFilter === 'week') return key >= weekStartKey && key <= todayKey;
      if (dateFilter === 'month') {
        const d = new Date(t.date);
        if (Number.isNaN(d.getTime())) return false;
        return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
      }
      return true;
    });
  }, [transactions, search, typeFilter, methodFilter, dateFilter, todayKey, weekStartKey]);

  // Transaksi 'void' (dibatalkan lewat "Batalkan status terjual") tetap tampil di tabel
  // dengan badge, tapi tidak boleh ikut dihitung di ringkasan omset/laba/unit. netRevenue
  // membuang PPN dari omset supaya Omset - HPP = Laba.
  const summary = useMemo(() => {
    const active = filtered.filter(isActiveTransaction);
    const carTrx = active.filter(isCarSale);
    const retailTrx = active.filter(t => !isCarSale(t));
    const revenue = active.reduce((acc, t) => acc + netRevenue(t), 0);
    return {
      count: active.length,
      revenue,
      cost: active.reduce((acc, t) => acc + hppOf(t), 0),
      profit: active.reduce((acc, t) => acc + profitOf(t), 0),
      average: active.length > 0 ? Math.round(revenue / active.length) : 0,
      carRevenue: carTrx.reduce((acc, t) => acc + netRevenue(t), 0),
      carUnits: carTrx.reduce((acc, t) => acc + unitsOf(t), 0),
      retailRevenue: retailTrx.reduce((acc, t) => acc + netRevenue(t), 0),
      retailCount: retailTrx.length
    };
  }, [filtered]);

  const margin = summary.revenue > 0 ? Math.round((summary.profit / summary.revenue) * 100) : 0;

  const openReceipt = (trx) => {
    setCurrentReceipt(trx);
    setIsReceiptModalOpen(true);
  };

  const handleExportCSV = () => {
    if (filtered.length === 0) {
      toast.error('Tidak ada data transaksi untuk diekspor!');
      return;
    }

    const headers = [
      'No. Transaksi', 'Tanggal', 'Waktu', 'Status', 'Jenis', 'Kasir', 'Pelanggan',
      'Rincian Item', 'Detail Mobil', 'Metode Bayar', 'Leasing', 'DP', 'Tenor (bulan)', 'Cicilan / Bulan',
      'Subtotal', 'Diskon', 'Pajak (PPN)', 'Total', 'Tukar Tambah', 'Sisa Bayar', 'Modal (HPP)', 'Laba'
    ];

    const rows = filtered.map(t => {
      const carSale = isCarSale(t);
      const car = t.car;
      const credit = t.credit;
      return [
        t.code,
        localDateKey(t.date),
        localTime(t.date),
        isActiveTransaction(t) ? 'Selesai' : 'Dibatalkan',
        carSale ? 'Penjualan Mobil' : 'Aksesoris & Jasa',
        t.cashier || 'Kasir',
        t.customerName,
        (t.items || []).map(i => `${i.name} x${i.qty}`).join('; '),
        carSale && car ? `${getCarName(car, { withYear: true })} | ${car.plate || 'tanpa plat'} | ${car.year || '-'}` : '',
        PAYMENT_METHOD_LABEL[t.paymentMethod] || t.paymentMethod || '',
        credit?.leasing || '',
        credit ? credit.dpAmount : '',
        credit ? credit.tenorMonths : '',
        credit ? credit.monthly : '',
        Number(t.subtotal) || 0,
        Number(t.discount) || 0,
        Number(t.tax) || 0,
        Number(t.total) || 0,
        t.tradeIn?.value || '',
        Number.isFinite(t.amountDue) ? t.amountDue : '',
        hppOf(t),
        profitOf(t)
      ];
    });

    // BOM UTF-8 supaya Excel membaca teks Indonesia dengan benar
    const csv = '﻿' + [headers, ...rows].map(row => row.map(csvCell).join(',')).join('\r\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `Laporan_Penjualan_Auto18_${todayKey}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  return (
    <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

      {/* Banner */}
      <div className="a18-stripes-soft flex flex-col gap-4 rounded-3xl p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Panel Showroom</span>
          <h1 className="a18-heading mt-1 text-2xl sm:text-3xl">Laporan Penjualan</h1>
          <p className="mt-1.5 max-w-xl text-sm text-neutral-400">
            Riwayat penjualan mobil dan aksesoris, laba bersih, serta cetak ulang nota.
          </p>
        </div>
        <button type="button" onClick={handleExportCSV} className="a18-btn-primary shrink-0 px-5 py-3">
          <Download className="h-4 w-4" aria-hidden="true" />
          Export CSV
        </button>
      </div>

      {/* Filter */}
      <div className="a18-card p-4 sm:p-5">
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div className="sm:col-span-2 lg:col-span-1">
            <label htmlFor="rep-search" className="a18-label">Cari</label>
            <div className="relative">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" aria-hidden="true" />
              <input
                id="rep-search"
                type="search"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Kode nota, pelanggan, atau item"
                className="a18-input pl-9"
              />
            </div>
          </div>

          <div>
            <label htmlFor="rep-date" className="a18-label">Periode</label>
            <select id="rep-date" value={dateFilter} onChange={(e) => setDateFilter(e.target.value)} className="a18-input">
              <option value="today">Hari Ini</option>
              <option value="week">7 Hari Terakhir</option>
              <option value="month">Bulan Ini</option>
              <option value="all">Semua Waktu</option>
            </select>
          </div>

          <div>
            <label htmlFor="rep-type" className="a18-label">Jenis</label>
            <select id="rep-type" value={typeFilter} onChange={(e) => setTypeFilter(e.target.value)} className="a18-input">
              <option value="all">Semua</option>
              <option value="car">Penjualan Mobil</option>
              <option value="retail">Aksesoris &amp; Jasa</option>
            </select>
          </div>

          <div>
            <label htmlFor="rep-method" className="a18-label">Metode Bayar</label>
            <select id="rep-method" value={methodFilter} onChange={(e) => setMethodFilter(e.target.value)} className="a18-input">
              <option value="all">Semua Metode</option>
              <option value="cash">Tunai</option>
              <option value="qris">QRIS</option>
              <option value="transfer">Transfer</option>
              <option value="credit">Kredit</option>
            </select>
          </div>
        </div>
        <p className="mt-3 text-xs text-neutral-500">{filtered.length} transaksi ditampilkan</p>
      </div>

      {/* Ringkasan */}
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <SummaryCard
          icon={TrendingUp}
          accent
          label="Total Omset"
          value={formatRupiah(summary.revenue)}
          hint={`${summary.count} transaksi aktif${summary.count !== filtered.length ? ` · ${filtered.length - summary.count} dibatalkan` : ''}`}
        />
        <SummaryCard
          icon={Coins}
          label="Total Modal (HPP)"
          value={formatRupiah(summary.cost)}
          hint="harga beli seluruh item terjual"
        />
        <SummaryCard
          icon={Wallet}
          label="Laba Bersih"
          value={formatRupiah(summary.profit)}
          valueClassName={summary.profit >= 0 ? 'text-emerald-400' : 'text-brand-400'}
          hint={`Margin ${margin}% dari omset`}
          hintClassName={summary.profit >= 0 ? 'text-emerald-400' : 'text-brand-400'}
        />
        <SummaryCard
          icon={ReceiptText}
          label="Rata-Rata Transaksi"
          value={formatRupiah(summary.average)}
          hint="nilai per nota"
        />
      </div>

      {/* Pembagian omset */}
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div className="a18-card flex items-center justify-between gap-3 p-4 sm:p-5">
          <div className="flex min-w-0 items-center gap-3">
            <span className="rounded-xl bg-brand-600/15 p-2.5 text-brand-400">
              <CarFront className="h-5 w-5" aria-hidden="true" />
            </span>
            <div className="min-w-0">
              <p className="text-[10px] font-bold uppercase tracking-wider text-neutral-500">Omset Mobil</p>
              <p className="font-display text-lg font-black text-white sm:text-xl">{formatRupiah(summary.carRevenue)}</p>
            </div>
          </div>
          <span className="shrink-0 rounded-lg bg-white/5 px-2.5 py-1 text-xs font-bold text-neutral-300">
            {summary.carUnits} unit
          </span>
        </div>

        <div className="a18-card flex items-center justify-between gap-3 p-4 sm:p-5">
          <div className="flex min-w-0 items-center gap-3">
            <span className="rounded-xl bg-white/5 p-2.5 text-neutral-300">
              <ShoppingBag className="h-5 w-5" aria-hidden="true" />
            </span>
            <div className="min-w-0">
              <p className="text-[10px] font-bold uppercase tracking-wider text-neutral-500">Omset Aksesoris</p>
              <p className="font-display text-lg font-black text-white sm:text-xl">{formatRupiah(summary.retailRevenue)}</p>
            </div>
          </div>
          <span className="shrink-0 rounded-lg bg-white/5 px-2.5 py-1 text-xs font-bold text-neutral-300">
            {summary.retailCount} transaksi
          </span>
        </div>
      </div>

      {/* Tabel desktop */}
      <div className="hidden overflow-x-auto rounded-2xl border border-white/10 md:block">
        <table className="w-full min-w-[1000px] text-left text-sm">
          <thead className="bg-ink-900 text-[10px] uppercase tracking-wider text-neutral-500">
            <tr>
              <th className="px-4 py-3">Tanggal</th>
              <th className="px-4 py-3">Kode</th>
              <th className="px-4 py-3">Jenis</th>
              <th className="px-4 py-3">Pelanggan</th>
              <th className="px-4 py-3">Rincian</th>
              <th className="px-4 py-3">Metode</th>
              <th className="px-4 py-3 text-right">Total</th>
              <th className="px-4 py-3 text-right">Laba</th>
              <th className="px-4 py-3 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {filtered.length === 0 ? (
              <tr>
                <td colSpan={9} className="px-4 py-10 text-center text-sm text-neutral-500">
                  Tidak ada transaksi pada filter ini.
                </td>
              </tr>
            ) : (
              filtered.map(t => {
                const profit = profitOf(t);
                return (
                  <tr key={t.id} className="border-t border-white/10 transition-colors hover:bg-white/5">
                    <td className="px-4 py-3 text-xs text-neutral-400">{formatDate(t.date)}</td>
                    <td className="px-4 py-3 font-mono text-xs font-bold text-white">{t.code}</td>
                    <td className="px-4 py-3">
                      <div className="flex flex-wrap items-center gap-1.5">
                        <TypeBadge carSale={isCarSale(t)} />
                        {!isActiveTransaction(t) && (
                          <span className="rounded-full border border-brand-500/40 bg-brand-600/20 px-2 py-0.5 text-[10px] font-bold uppercase text-brand-300">
                            Dibatalkan
                          </span>
                        )}
                      </div>
                    </td>
                    <td className="px-4 py-3 text-xs font-medium text-white">{t.customerName}</td>
                    <td className="max-w-[260px] px-4 py-3">
                      <p className="truncate text-xs text-neutral-300" title={summaryOf(t)}>{summaryOf(t)}</p>
                      {!isCarSale(t) && (
                        <span className="text-[10px] text-neutral-500">{(t.items || []).length} item</span>
                      )}
                    </td>
                    <td className="px-4 py-3"><MethodCell trx={t} /></td>
                    <td className="px-4 py-3 text-right font-mono text-xs font-bold text-white">{formatRupiah(t.total)}</td>
                    <td className={`px-4 py-3 text-right font-mono text-xs font-bold ${profit >= 0 ? 'text-emerald-400' : 'text-brand-400'}`}>
                      {formatRupiah(profit)}
                    </td>
                    <td className="px-4 py-3 text-center">
                      <button
                        type="button"
                        onClick={() => openReceipt(t)}
                        title="Cetak ulang nota"
                        className="a18-btn-ghost mx-auto px-2.5 py-1.5 text-xs"
                      >
                        <Printer className="h-3.5 w-3.5" aria-hidden="true" />
                        Nota
                      </button>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

      {/* Kartu mobile */}
      <div className="space-y-3 md:hidden">
        {filtered.length === 0 ? (
          <p className="a18-card p-10 text-center text-sm text-neutral-500">Tidak ada transaksi pada filter ini.</p>
        ) : (
          filtered.map(t => {
            const profit = profitOf(t);
            return (
              <div key={t.id} className="a18-card p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <div className="flex flex-wrap items-center gap-1.5">
                    <TypeBadge carSale={isCarSale(t)} />
                    {!isActiveTransaction(t) && (
                      <span className="rounded-full border border-brand-500/40 bg-brand-600/20 px-2 py-0.5 text-[10px] font-bold uppercase text-brand-300">
                        Dibatalkan
                      </span>
                    )}
                  </div>
                  <span className="font-mono text-[11px] text-neutral-500">{t.code}</span>
                </div>
                <p className="mt-2 text-sm font-bold text-white">{summaryOf(t)}</p>
                <p className="text-xs text-neutral-400">{t.customerName}</p>
                <p className="text-[11px] text-neutral-500">{formatDate(t.date)}</p>

                <div className="mt-3 flex items-end justify-between gap-3 border-t border-white/10 pt-3">
                  <MethodCell trx={t} />
                  <div className="text-right">
                    <p className="font-mono text-sm font-bold text-white">{formatRupiah(t.total)}</p>
                    <p className={`font-mono text-[11px] font-bold ${profit >= 0 ? 'text-emerald-400' : 'text-brand-400'}`}>
                      Laba {formatRupiah(profit)}
                    </p>
                  </div>
                </div>

                <button type="button" onClick={() => openReceipt(t)} className="a18-btn-outline mt-3 w-full py-2 text-xs">
                  <Printer className="h-3.5 w-3.5" aria-hidden="true" />
                  Cetak Ulang Nota
                </button>
              </div>
            );
          })
        )}
      </div>

    </div>
  );
}
