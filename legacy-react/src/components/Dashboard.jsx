import React, { useMemo, useState } from 'react';
import {
  TrendingUp,
  Wallet,
  ReceiptText,
  CarFront,
  AlertTriangle,
  ShoppingBag,
  CalendarCheck,
  ArrowLeftRight,
  CheckCircle2,
  Banknote,
  QrCode,
  CreditCard,
  Clock,
  ChevronRight,
  Package
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import { formatRupiah, formatDate } from '../utils/formatters';
import {
  formatRupiahShort,
  getCarName,
  PAYMENT_METHOD_LABEL,
  SELL_REQUEST_STATUS,
  TEST_DRIVE_STATUS
} from '../utils/carUtils';
import { isActiveTransaction, netRevenue } from '../utils/transactions';

// Kunci tanggal LOKAL (YYYY-MM-DD). toISOString() memakai UTC dan meleset di WIB.
const localDateKey = (value) => {
  const d = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(d.getTime())) return '';
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const isCarSale = (trx) => trx.saleType === 'car';

const formatDay = (value) => {
  if (!value) return '-';
  const [y, m, d] = String(value).split('-').map(Number);
  if (!y || !m || !d) return value;
  return new Intl.DateTimeFormat('id-ID', { weekday: 'short', day: 'numeric', month: 'short' }).format(new Date(y, m - 1, d));
};

const CHART_MODES = [
  { id: 'all', label: 'Semua' },
  { id: 'car', label: 'Mobil' },
  { id: 'retail', label: 'Aksesoris' }
];

function KpiCard({ icon: Icon, label, value, hint, hintClassName = 'text-neutral-500', onClick, accent = false }) {
  const Wrapper = onClick ? 'button' : 'div';
  return (
    <Wrapper
      {...(onClick ? { type: 'button', onClick } : {})}
      className={`a18-card w-full p-4 text-left sm:p-5 ${onClick ? 'transition-colors hover:border-brand-600' : ''}`}
    >
      <div className="flex items-start justify-between gap-2">
        <span className="text-[10px] font-bold uppercase tracking-wider text-neutral-500">{label}</span>
        <span className={`rounded-xl p-2 ${accent ? 'bg-brand-600/15 text-brand-400' : 'bg-white/5 text-neutral-300'}`}>
          <Icon className="h-4 w-4" aria-hidden="true" />
        </span>
      </div>
      <p className="mt-2 font-display text-xl font-black leading-tight text-white sm:text-2xl">{value}</p>
      {hint && <p className={`mt-1 text-xs ${hintClassName}`}>{hint}</p>}
    </Wrapper>
  );
}

function PaymentIcon({ method }) {
  if (method === 'cash') return <Banknote className="h-4 w-4 text-neutral-200" aria-hidden="true" />;
  if (method === 'qris') return <QrCode className="h-4 w-4 text-neutral-200" aria-hidden="true" />;
  return <CreditCard className="h-4 w-4 text-neutral-200" aria-hidden="true" />;
}

function PanelHeader({ title, subtitle, actionLabel, onAction }) {
  return (
    <div className="flex flex-wrap items-center justify-between gap-2">
      <div className="min-w-0">
        <h3 className="a18-heading text-sm sm:text-base">{title}</h3>
        {subtitle && <p className="mt-0.5 text-xs text-neutral-500">{subtitle}</p>}
      </div>
      {actionLabel && onAction && (
        <button type="button" onClick={onAction} className="inline-flex shrink-0 items-center gap-1 text-xs font-bold text-brand-400 transition-colors hover:text-brand-300">
          {actionLabel}
          <ChevronRight className="h-3.5 w-3.5" aria-hidden="true" />
        </button>
      )}
    </div>
  );
}

export default function Dashboard() {
  const {
    transactions,
    products,
    cars,
    sellRequests,
    testDrives,
    setActiveTab,
    setCurrentReceipt,
    setIsReceiptModalOpen
  } = useApp();

  const [chartMode, setChartMode] = useState('all');

  const todayKey = localDateKey(new Date());
  const now = new Date();

  const openReceipt = (trx) => {
    setCurrentReceipt(trx);
    setIsReceiptModalOpen(true);
  };

  // ------------------------------------------------------------- Hari ini
  // Transaksi yang dibatalkan ("Batalkan status terjual") tidak dihitung di omset/laba —
  // netRevenue juga mengeluarkan PPN, jadi Omset - HPP = Laba.
  const todayStats = useMemo(() => {
    const todayTrx = transactions.filter(t => isActiveTransaction(t) && localDateKey(t.date) === todayKey);
    const carTrx = todayTrx.filter(isCarSale);
    return {
      revenue: todayTrx.reduce((acc, t) => acc + netRevenue(t), 0),
      profit: todayTrx.reduce((acc, t) => acc + (Number(t.totalProfit) || 0), 0),
      count: todayTrx.length,
      carCount: carTrx.length,
      retailCount: todayTrx.length - carTrx.length
    };
  }, [transactions, todayKey]);

  // ------------------------------------------------------------- Showroom
  const showroomStats = useMemo(() => {
    const ref = new Date();
    const carSalesThisMonth = transactions.filter(t => {
      if (!isActiveTransaction(t) || !isCarSale(t)) return false;
      const d = new Date(t.date);
      return !Number.isNaN(d.getTime()) && d.getFullYear() === ref.getFullYear() && d.getMonth() === ref.getMonth();
    });
    return {
      available: cars.filter(c => c.status === 'available').length,
      booked: cars.filter(c => c.status === 'booked').length,
      soldThisMonth: carSalesThisMonth.length,
      carRevenueThisMonth: carSalesThisMonth.reduce((acc, t) => acc + netRevenue(t), 0),
      newRequests: sellRequests.filter(r => r.status === 'new').length,
      totalRequests: sellRequests.length,
      pendingTestDrives: testDrives.filter(t => t.status === 'pending').length,
      todayTestDrives: testDrives.filter(t => t.date === todayKey && t.status !== 'cancelled').length
    };
  }, [transactions, cars, sellRequests, testDrives, todayKey]);

  // ------------------------------------------------------------- Grafik 7 hari
  const chartDays = useMemo(() => {
    const days = [];
    for (let i = 6; i >= 0; i -= 1) {
      const d = new Date();
      d.setHours(0, 0, 0, 0);
      d.setDate(d.getDate() - i);
      days.push({
        key: localDateKey(d),
        dayName: new Intl.DateTimeFormat('id-ID', { weekday: 'short', day: 'numeric' }).format(d),
        all: 0,
        car: 0,
        retail: 0
      });
    }
    const byKey = new Map(days.map(d => [d.key, d]));
    transactions.filter(isActiveTransaction).forEach(t => {
      const bucket = byKey.get(localDateKey(t.date));
      if (!bucket) return;
      const amount = netRevenue(t);
      bucket.all += amount;
      if (isCarSale(t)) bucket.car += amount;
      else bucket.retail += amount;
    });
    return days;
  }, [transactions]);

  // Omset mobil jauh lebih besar dari retail — tiap tampilan pakai skala sendiri
  const chartMax = Math.max(...chartDays.map(d => d[chartMode]), 1);
  const chartTotal = chartDays.reduce((acc, d) => acc + d[chartMode], 0);

  // ------------------------------------------------------------- Daftar ringkas
  const topAccessories = useMemo(() => {
    const map = new Map();
    transactions.filter(t => isActiveTransaction(t) && !isCarSale(t)).forEach(trx => {
      (trx.items || []).forEach(item => {
        const current = map.get(item.id) || { name: item.name, qty: 0, revenue: 0 };
        current.qty += Number(item.qty) || 0;
        current.revenue += Number(item.subtotal) || 0;
        map.set(item.id, current);
      });
    });
    return Array.from(map.values()).sort((a, b) => b.qty - a.qty).slice(0, 5);
  }, [transactions]);

  const recentTransactions = useMemo(
    () => [...transactions].sort((a, b) => new Date(b.date) - new Date(a.date)).slice(0, 5),
    [transactions]
  );

  const recentCarSales = useMemo(
    () => transactions.filter(isCarSale).sort((a, b) => new Date(b.date) - new Date(a.date)).slice(0, 5),
    [transactions]
  );

  const upcomingTestDrives = useMemo(
    () => testDrives
      .filter(t => ['pending', 'confirmed'].includes(t.status) && String(t.date || '') >= todayKey)
      .sort((a, b) => `${a.date} ${a.time}`.localeCompare(`${b.date} ${b.time}`))
      .slice(0, 5),
    [testDrives, todayKey]
  );

  const recentRequests = useMemo(
    () => [...sellRequests].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt)).slice(0, 5),
    [sellRequests]
  );

  const lowStockProducts = products.filter(p => p.stock <= p.minStock);

  const todayMargin = todayStats.revenue > 0 ? Math.round((todayStats.profit / todayStats.revenue) * 100) : 0;

  return (
    <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

      {/* Banner */}
      <div className="a18-stripes-soft flex flex-col gap-5 rounded-3xl p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Panel Showroom</span>
          <h1 className="a18-heading mt-1 text-2xl sm:text-3xl">Dashboard Showroom Auto18</h1>
          <p className="mt-1.5 flex items-center gap-1.5 text-sm text-neutral-400">
            <Clock className="h-3.5 w-3.5" aria-hidden="true" />
            {formatDate(now, false)}
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <button type="button" onClick={() => setActiveTab('pos')} className="a18-btn-primary">
            <ShoppingBag className="h-4 w-4" aria-hidden="true" />
            Kasir Aksesoris
          </button>
          <button type="button" onClick={() => setActiveTab('cars')} className="a18-btn-outline">
            <CarFront className="h-4 w-4" aria-hidden="true" />
            Stok Mobil
          </button>
          <button type="button" onClick={() => setActiveTab('buy-car')} className="a18-btn-outline">
            <Wallet className="h-4 w-4" aria-hidden="true" />
            Beli Mobil
          </button>
        </div>
      </div>

      {/* KPI hari ini */}
      <div>
        <h2 className="mb-3 text-[10px] font-black uppercase tracking-[0.2em] text-neutral-500">Performa Hari Ini</h2>
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <KpiCard
            icon={TrendingUp}
            accent
            label="Omset Hari Ini"
            value={formatRupiah(todayStats.revenue)}
            hint={`${todayStats.count} transaksi tercatat`}
          />
          <KpiCard
            icon={Wallet}
            label="Laba Hari Ini"
            value={formatRupiah(todayStats.profit)}
            hint={`Margin ${todayMargin}% dari omset`}
            hintClassName={todayStats.profit >= 0 ? 'text-emerald-400' : 'text-brand-400'}
          />
          <KpiCard
            icon={ReceiptText}
            label="Jumlah Transaksi"
            value={todayStats.count}
            hint={`${todayStats.carCount} mobil · ${todayStats.retailCount} aksesoris`}
            onClick={() => setActiveTab('reports')}
          />
        </div>
      </div>

      {/* KPI showroom */}
      <div>
        <h2 className="mb-3 text-[10px] font-black uppercase tracking-[0.2em] text-neutral-500">Ringkasan Showroom</h2>
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <KpiCard
            icon={CarFront}
            accent
            label="Mobil Tersedia / Dipesan"
            value={`${showroomStats.available} / ${showroomStats.booked}`}
            hint="unit siap dijual · unit dipesan"
            onClick={() => setActiveTab('cars')}
          />
          <KpiCard
            icon={CheckCircle2}
            label="Mobil Terjual Bulan Ini"
            value={`${showroomStats.soldThisMonth} unit`}
            hint={`Omset mobil ${formatRupiahShort(showroomStats.carRevenueThisMonth)}`}
            onClick={() => setActiveTab('cars')}
          />
          <KpiCard
            icon={ArrowLeftRight}
            label="Permintaan Jual Baru"
            value={showroomStats.newRequests}
            hint={`dari ${showroomStats.totalRequests} permintaan masuk`}
            hintClassName={showroomStats.newRequests > 0 ? 'text-amber-400' : 'text-neutral-500'}
            onClick={() => setActiveTab('cars')}
          />
          <KpiCard
            icon={CalendarCheck}
            label="Test Drive"
            value={`${showroomStats.pendingTestDrives} pending`}
            hint={`${showroomStats.todayTestDrives} jadwal hari ini`}
            hintClassName={showroomStats.todayTestDrives > 0 ? 'text-amber-400' : 'text-neutral-500'}
            onClick={() => setActiveTab('cars')}
          />
        </div>
      </div>

      {/* Grafik & aksesoris terlaris */}
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-12">
        <div className="a18-card space-y-4 p-5 sm:p-6 lg:col-span-8">
          <div className="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h3 className="a18-heading text-sm sm:text-base">Tren Omset 7 Hari</h3>
              <p className="mt-0.5 text-xs text-neutral-500">
                Total {formatRupiah(chartTotal)} · skala mengikuti tampilan terpilih
              </p>
            </div>
            <div role="group" aria-label="Filter jenis omset" className="inline-flex rounded-xl border border-white/10 bg-ink-900 p-1">
              {CHART_MODES.map(mode => (
                <button
                  key={mode.id}
                  type="button"
                  onClick={() => setChartMode(mode.id)}
                  aria-pressed={chartMode === mode.id}
                  className={`rounded-lg px-3 py-1.5 text-xs font-bold transition-colors ${
                    chartMode === mode.id ? 'bg-brand-600 text-white' : 'text-neutral-400 hover:text-white'
                  }`}
                >
                  {mode.label}
                </button>
              ))}
            </div>
          </div>

          <div className="flex h-56 items-end justify-between gap-1.5 pt-4 sm:gap-3">
            {chartDays.map((d, idx) => {
              const value = d[chartMode];
              const heightPct = value > 0 ? Math.max(6, Math.round((value / chartMax) * 100)) : 0;
              const isToday = idx === chartDays.length - 1;
              return (
                <div key={d.key} className="group flex min-w-0 flex-1 flex-col items-center gap-2">
                  <div className="text-[9px] font-bold text-neutral-300 opacity-0 transition-opacity group-hover:opacity-100 sm:text-[10px]">
                    {formatRupiahShort(value)}
                  </div>
                  <div className="flex h-36 w-full max-w-[52px] items-end rounded-xl bg-white/5 p-1">
                    <div
                      style={{ height: `${heightPct}%` }}
                      title={`${d.dayName}: ${formatRupiah(value)}`}
                      className={`w-full rounded-lg transition-all duration-300 ${
                        isToday
                          ? 'bg-gradient-to-t from-brand-700 to-brand-500 shadow-lg shadow-brand-600/30'
                          : 'bg-neutral-700 group-hover:bg-brand-600'
                      }`}
                    />
                  </div>
                  <span className={`whitespace-nowrap text-[10px] ${isToday ? 'font-bold text-brand-400' : 'font-medium text-neutral-500'}`}>
                    {d.dayName}
                  </span>
                </div>
              );
            })}
          </div>
        </div>

        <div className="a18-card space-y-4 p-5 sm:p-6 lg:col-span-4">
          <PanelHeader title="Aksesoris Terlaris" subtitle="Top 5 item kasir" actionLabel="Produk" onAction={() => setActiveTab('products')} />
          <div className="space-y-2">
            {topAccessories.length === 0 ? (
              <p className="py-8 text-center text-xs text-neutral-500">Belum ada penjualan aksesoris.</p>
            ) : (
              topAccessories.map((p, idx) => (
                <div key={`${p.name}-${idx}`} className="flex items-center justify-between gap-2 rounded-xl p-2 transition-colors hover:bg-white/5">
                  <div className="flex min-w-0 items-center gap-3">
                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-brand-600/20 font-display text-xs font-black text-brand-400">
                      {idx + 1}
                    </span>
                    <div className="min-w-0">
                      <p className="truncate text-xs font-semibold text-white" title={p.name}>{p.name}</p>
                      <p className="text-[10px] text-neutral-500">{formatRupiah(p.revenue)}</p>
                    </div>
                  </div>
                  <span className="shrink-0 rounded-lg bg-white/5 px-2 py-1 text-[10px] font-bold text-neutral-300">
                    {p.qty} terjual
                  </span>
                </div>
              ))
            )}
          </div>
        </div>
      </div>

      {/* Transaksi terakhir & mobil terjual */}
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-12">
        <div className="a18-card space-y-4 p-5 sm:p-6 lg:col-span-7">
          <PanelHeader
            title="Transaksi Terakhir"
            subtitle={`${transactions.length} transaksi tercatat`}
            actionLabel="Lihat laporan"
            onAction={() => setActiveTab('reports')}
          />
          <div className="divide-y divide-white/10">
            {recentTransactions.length === 0 ? (
              <p className="py-8 text-center text-xs text-neutral-500">Belum ada transaksi.</p>
            ) : (
              recentTransactions.map(trx => {
                const carSale = isCarSale(trx);
                const carLabel = carSale ? (getCarName(trx.car, { withYear: true }) || trx.items?.[0]?.name || 'Mobil') : '';
                const methodLabel = PAYMENT_METHOD_LABEL[trx.paymentMethod] || trx.paymentMethod;
                return (
                  <button
                    key={trx.id}
                    type="button"
                    onClick={() => openReceipt(trx)}
                    className="flex w-full items-center justify-between gap-3 rounded-xl p-2 text-left transition-colors hover:bg-white/5"
                  >
                    <div className="flex min-w-0 items-center gap-3">
                      <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${carSale ? 'bg-brand-600/20' : 'bg-white/5'}`}>
                        {carSale
                          ? <CarFront className="h-4 w-4 text-brand-400" aria-hidden="true" />
                          : <PaymentIcon method={trx.paymentMethod} />}
                      </span>
                      <div className="min-w-0">
                        <p className="flex items-center gap-1.5 truncate font-mono text-xs font-bold text-white">
                          {trx.code}
                          {!isActiveTransaction(trx) && (
                            <span className="rounded-full border border-brand-500/40 bg-brand-600/20 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-brand-300">
                              Dibatalkan
                            </span>
                          )}
                        </p>
                        <p className="truncate text-[11px] text-neutral-500">
                          {carSale ? carLabel : trx.customerName}
                        </p>
                        <p className="truncate text-[10px] text-neutral-600">
                          {formatDate(trx.date)}
                          {carSale && ` · ${trx.customerName}`}
                        </p>
                      </div>
                    </div>
                    <div className="shrink-0 text-right">
                      <p className="font-mono text-xs font-bold text-white">{formatRupiah(trx.total)}</p>
                      <p className="text-[10px] font-semibold uppercase tracking-wide text-neutral-500">
                        {trx.paymentMethod === 'credit' && trx.credit?.leasing
                          ? `${methodLabel} · ${trx.credit.leasing}`
                          : methodLabel}
                      </p>
                    </div>
                  </button>
                );
              })
            )}
          </div>
        </div>

        <div className="a18-card space-y-4 p-5 sm:p-6 lg:col-span-5">
          <PanelHeader title="Mobil Terjual Terbaru" actionLabel="Stok mobil" onAction={() => setActiveTab('cars')} />
          <div className="space-y-2">
            {recentCarSales.length === 0 ? (
              <p className="py-8 text-center text-xs text-neutral-500">Belum ada penjualan mobil.</p>
            ) : (
              recentCarSales.map(trx => (
                <button
                  key={trx.id}
                  type="button"
                  onClick={() => openReceipt(trx)}
                  className="flex w-full items-start justify-between gap-3 rounded-xl border border-white/10 p-3 text-left transition-colors hover:border-brand-600 hover:bg-white/5"
                >
                  <div className="min-w-0">
                    <p className="flex items-center gap-1.5 truncate text-xs font-bold text-white">
                      {getCarName(trx.car, { withYear: true }) || trx.items?.[0]?.name || 'Mobil'}
                      {!isActiveTransaction(trx) && (
                        <span className="rounded-full border border-brand-500/40 bg-brand-600/20 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-brand-300">
                          Dibatalkan
                        </span>
                      )}
                    </p>
                    <p className="truncate text-[11px] text-neutral-500">{trx.customerName}</p>
                    <p className="text-[10px] text-neutral-600">{formatDate(trx.date, false)}</p>
                  </div>
                  <div className="shrink-0 text-right">
                    <p className="font-display text-sm font-black text-white">{formatRupiahShort(trx.total)}</p>
                    <p className="text-[10px] font-semibold text-neutral-500">
                      {PAYMENT_METHOD_LABEL[trx.paymentMethod] || trx.paymentMethod}
                    </p>
                    {(Number(trx.totalProfit) || 0) !== 0 && (
                      <p className={`text-[10px] font-bold ${trx.totalProfit >= 0 ? 'text-emerald-400' : 'text-brand-400'}`}>
                        {formatRupiahShort(trx.totalProfit)}
                      </p>
                    )}
                  </div>
                </button>
              ))
            )}
          </div>
        </div>
      </div>

      {/* Test drive, permintaan jual, stok menipis */}
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div className="a18-card space-y-4 p-5 sm:p-6">
          <PanelHeader title="Test Drive Mendatang" actionLabel="Kelola" onAction={() => setActiveTab('cars')} />
          <div className="space-y-2">
            {upcomingTestDrives.length === 0 ? (
              <p className="py-8 text-center text-xs text-neutral-500">Tidak ada jadwal test drive mendatang.</p>
            ) : (
              upcomingTestDrives.map(booking => {
                const status = TEST_DRIVE_STATUS[booking.status];
                const isToday = booking.date === todayKey;
                return (
                  <div key={booking.id} className={`rounded-xl border p-3 ${isToday ? 'border-brand-600/60 bg-brand-600/5' : 'border-white/10'}`}>
                    <div className="flex flex-wrap items-center gap-1.5">
                      {status && (
                        <span className={`rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase ${status.className}`}>
                          {status.label}
                        </span>
                      )}
                      {isToday && (
                        <span className="rounded-full bg-brand-600 px-2 py-0.5 text-[10px] font-black uppercase text-white">Hari Ini</span>
                      )}
                    </div>
                    <p className="mt-1.5 truncate text-xs font-bold text-white">{booking.carName}</p>
                    <p className="truncate text-[11px] text-neutral-500">{booking.customerName}</p>
                    <p className="mt-1 inline-flex items-center gap-1 text-[10px] text-neutral-400">
                      <CalendarCheck className="h-3 w-3" aria-hidden="true" />
                      {formatDay(booking.date)} · {booking.time}
                    </p>
                  </div>
                );
              })
            )}
          </div>
        </div>

        <div className="a18-card space-y-4 p-5 sm:p-6">
          <PanelHeader title="Permintaan Jual Terbaru" actionLabel="Kelola" onAction={() => setActiveTab('cars')} />
          <div className="space-y-2">
            {recentRequests.length === 0 ? (
              <p className="py-8 text-center text-xs text-neutral-500">Belum ada permintaan jual mobil.</p>
            ) : (
              recentRequests.map(request => {
                const status = SELL_REQUEST_STATUS[request.status];
                return (
                  <div key={request.id} className="rounded-xl border border-white/10 p-3">
                    <div className="flex flex-wrap items-center gap-1.5">
                      {status && (
                        <span className={`rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase ${status.className}`}>
                          {status.label}
                        </span>
                      )}
                      <span className="rounded-full border border-white/15 px-2 py-0.5 text-[10px] font-bold uppercase text-neutral-400">
                        {request.type === 'trade-in' ? 'Tukar Tambah' : 'Jual'}
                      </span>
                    </div>
                    <p className="mt-1.5 truncate text-xs font-bold text-white">
                      {[request.brand, request.model, request.variant, request.year].filter(Boolean).join(' ')}
                    </p>
                    <p className="truncate text-[11px] text-neutral-500">{request.customerName}</p>
                    <p className="mt-1 text-[10px] text-neutral-400">
                      Harga diharapkan {request.askingPrice > 0 ? formatRupiahShort(request.askingPrice) : '-'}
                    </p>
                  </div>
                );
              })
            )}
          </div>
        </div>

        <div className="a18-card space-y-4 p-5 sm:p-6">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <div className="flex items-center gap-2">
              <AlertTriangle className="h-4 w-4 text-amber-400" aria-hidden="true" />
              <h3 className="a18-heading text-sm sm:text-base">Stok Menipis</h3>
            </div>
            <button type="button" onClick={() => setActiveTab('suppliers')} className="inline-flex items-center gap-1 text-xs font-bold text-brand-400 transition-colors hover:text-brand-300">
              Kulakan
              <ChevronRight className="h-3.5 w-3.5" aria-hidden="true" />
            </button>
          </div>

          {lowStockProducts.length === 0 ? (
            <p className="py-8 text-center text-xs text-neutral-500">Semua stok aksesoris aman.</p>
          ) : (
            <div className="space-y-2">
              {lowStockProducts.slice(0, 5).map(prod => (
                <div key={prod.id} className="flex items-center justify-between gap-2 rounded-xl border border-amber-500/25 bg-amber-500/5 p-3">
                  <div className="min-w-0">
                    <p className="truncate text-xs font-semibold text-white">{prod.name}</p>
                    <p className="text-[10px] text-neutral-500">Min. stok {prod.minStock} {prod.unit}</p>
                  </div>
                  <span className={`shrink-0 rounded-lg px-2 py-1 text-[10px] font-bold ${
                    prod.stock <= 0 ? 'bg-brand-600/20 text-brand-300' : 'bg-amber-500/15 text-amber-300'
                  }`}>
                    Sisa {prod.stock} {prod.unit}
                  </span>
                </div>
              ))}
              {lowStockProducts.length > 5 && (
                <button type="button" onClick={() => setActiveTab('products')} className="inline-flex items-center gap-1 text-[11px] font-bold text-neutral-400 hover:text-white">
                  <Package className="h-3.5 w-3.5" aria-hidden="true" />
                  {lowStockProducts.length - 5} produk lainnya
                </button>
              )}
            </div>
          )}
        </div>
      </div>

    </div>
  );
}
