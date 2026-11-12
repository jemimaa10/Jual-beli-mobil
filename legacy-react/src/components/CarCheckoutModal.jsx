import React, { useEffect, useMemo, useState } from 'react';
import { X, Banknote, Landmark, CreditCard, ArrowLeftRight, CheckCircle2, AlertTriangle } from 'lucide-react';
import { motion } from 'motion/react';
import { useApp } from '../context/AppContext';
import CarImage from './CarImage';
import CreditSimulator from './CreditSimulator';
import Money from './Money';
import { LEASING_PARTNERS } from '../data/initialData';
import { estimateCarPrice, getCarName } from '../utils/carUtils';
import { formatRupiah } from '../utils/formatters';
import { lockScroll, unlockScroll } from '../utils/scrollLock';

const METHODS = [
  { id: 'cash', label: 'Tunai', icon: Banknote },
  { id: 'transfer', label: 'Transfer', icon: Landmark },
  { id: 'credit', label: 'Kredit', icon: CreditCard },
];

const PILL_SPRING = { type: 'spring', bounce: 0.15, duration: 0.35 };

export default function CarCheckoutModal() {
  const { checkoutCar, closeCarCheckout, sellCar, sellRequests, storeInfo } = useApp();

  const [customerName, setCustomerName] = useState('');
  const [customerPhone, setCustomerPhone] = useState('');
  const [discount, setDiscount] = useState(0);
  const [paymentMethod, setPaymentMethod] = useState('cash');
  const [paidAmount, setPaidAmount] = useState(''); // '' = belum diubah kasir, ikuti sisa bayar
  const [leasing, setLeasing] = useState(LEASING_PARTNERS[0]);
  const [creditSim, setCreditSim] = useState(null);
  const [useTradeIn, setUseTradeIn] = useState(false);
  const [tradeInRequestId, setTradeInRequestId] = useState('');
  const [tradeInName, setTradeInName] = useState('');
  const [tradeInValue, setTradeInValue] = useState(0);
  const [notes, setNotes] = useState('');
  const [errors, setErrors] = useState({});

  const carId = checkoutCar?.id;
  const price = checkoutCar?.price || 0;

  // Permintaan tukar tambah yang masih aktif
  const tradeInOptions = useMemo(
    () => sellRequests.filter(r => r.type === 'trade-in' && ['new', 'scheduled', 'inspected'].includes(r.status)),
    [sellRequests]
  );

  // Reset form setiap mobil yang diproses berganti
  useEffect(() => {
    setCustomerName('');
    setCustomerPhone('');
    setDiscount(0);
    setPaymentMethod('cash');
    setPaidAmount('');
    setLeasing(LEASING_PARTNERS[0]);
    setCreditSim(null);
    setUseTradeIn(false);
    setTradeInRequestId('');
    setTradeInName('');
    setTradeInValue(0);
    setNotes('');
    setErrors({});
  }, [carId, checkoutCar?.price]);

  // Kunci scroll lewat lock bersama (aman ditumpuk dengan CarDetailModal) dan tutup
  // dengan Escape. Effect ini sengaja hanya bergantung pada carId, bukan closeCarCheckout,
  // supaya tidak dipasang ulang setiap AppContext me-render (fungsi itu dibuat baru tiap render).
  useEffect(() => {
    if (!carId) return undefined;
    const onKeyDown = (e) => { if (e.key === 'Escape') closeCarCheckout(); };
    window.addEventListener('keydown', onKeyDown);
    lockScroll();
    return () => {
      window.removeEventListener('keydown', onKeyDown);
      unlockScroll();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [carId]);

  // Nilai untuk TAMPILAN (ringkasan) boleh dipotong biar tidak minus; validasi & data
  // yang dikirim ke sellCar memakai nilai MENTAH supaya input yang salah ditolak dengan
  // pesan yang jelas, bukan dipotong diam-diam menjadi penjualan Rp 0.
  const rawDiscount = Number(discount);
  const safeDiscount = Math.min(price, Math.max(0, Number.isFinite(rawDiscount) ? rawDiscount : 0));
  const total = price - safeDiscount;
  const rawTradeInValue = Number(tradeInValue);
  const appliedTradeIn = useTradeIn ? Math.min(total, Math.max(0, Number.isFinite(rawTradeInValue) ? rawTradeInValue : 0)) : 0;
  const amountDue = total - appliedTradeIn;
  const effectivePaid = paidAmount === '' ? amountDue : Number(paidAmount);

  const selectTradeInRequest = (id) => {
    setTradeInRequestId(id);
    const request = tradeInOptions.find(r => r.id === id);
    if (!request) {
      setTradeInName('');
      setTradeInValue(0);
      return;
    }
    const estimate = request.offerPrice || request.estimateLow || estimateCarPrice({
      brand: request.brand,
      model: request.model,
      bodyType: request.bodyType,
      year: request.year,
      mileage: request.mileage,
      transmission: request.transmission,
      condition: request.condition,
    }).mid;
    setTradeInName(`${request.brand} ${request.model} ${request.variant || ''} ${request.year}`.replace(/\s+/g, ' ').trim());
    setTradeInValue(estimate);
  };

  if (!checkoutCar) return null;

  const car = checkoutCar;
  const profit = total - (car.buyPrice || 0);
  const quickAmounts = [amountDue, Math.ceil(amountDue / 1000000) * 1000000 + 1000000, Math.ceil(amountDue / 5000000) * 5000000 + 5000000];

  const submit = (e) => {
    e.preventDefault();
    const nextErrors = {};
    if (!customerName.trim()) nextErrors.customerName = 'Nama pembeli wajib diisi';
    if (!Number.isFinite(rawDiscount) || rawDiscount < 0 || rawDiscount >= price) {
      nextErrors.discount = 'Diskon tidak valid atau melebihi harga mobil';
    }
    if (paymentMethod === 'cash' && (!Number.isFinite(effectivePaid) || effectivePaid < amountDue)) {
      nextErrors.paidAmount = 'Uang diterima kurang dari sisa bayar';
    }
    if (paymentMethod === 'credit') {
      if (!creditSim) nextErrors.credit = 'Atur simulasi kredit terlebih dahulu';
      else if (creditSim.dpPercent < storeInfo.minDpPercent) nextErrors.credit = `DP minimal ${storeInfo.minDpPercent}%`;
    }
    if (useTradeIn) {
      if (!Number.isFinite(rawTradeInValue) || rawTradeInValue <= 0) nextErrors.tradeIn = 'Nilai tukar tambah belum diisi';
      else if (rawTradeInValue > total) nextErrors.tradeIn = 'Nilai tukar tambah melebihi total harga mobil';
      else if (!tradeInName.trim()) nextErrors.tradeIn = 'Mobil tukar tambah belum dipilih';
    }
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0) return;

    sellCar({
      carId: car.id,
      customerName: customerName.trim(),
      customerPhone: customerPhone.trim(),
      paymentMethod,
      discount: rawDiscount,
      paidAmount: paymentMethod === 'cash' ? effectivePaid : amountDue,
      credit: paymentMethod === 'credit' && creditSim
        ? {
            leasing,
            dpPercent: creditSim.dpPercent,
            tenorMonths: creditSim.tenorMonths,
            annualRate: creditSim.annualRate,
          }
        : null,
      tradeIn: useTradeIn && rawTradeInValue > 0
        ? { requestId: tradeInRequestId || null, carName: tradeInName.trim(), value: rawTradeInValue }
        : null,
      notes: notes.trim(),
    });
  };

  const summaryRows = [
    { label: 'Harga Mobil', value: formatRupiah(price) },
    safeDiscount > 0 && { label: 'Diskon', value: <>{'− '}<Money value={safeDiscount} /></>, accent: true },
    { label: 'Total', value: <Money value={total} />, bold: true },
    appliedTradeIn > 0 && { label: 'Tukar Tambah', value: <>{'− '}<Money value={appliedTradeIn} /></>, accent: true },
  ].filter(Boolean);

  return (
    <div
      className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/80 p-4 backdrop-blur-sm"
      onMouseDown={(e) => { if (e.target === e.currentTarget) closeCarCheckout(); }}
    >
      <form
        onSubmit={submit}
        noValidate
        role="dialog"
        aria-modal="true"
        aria-label="Proses penjualan mobil"
        className="my-4 w-full max-w-4xl overflow-hidden rounded-3xl border border-white/10 bg-ink-800"
      >
        {/* Header */}
        <div className="flex items-center justify-between gap-3 border-b border-white/10 p-4">
          <div className="flex min-w-0 items-center gap-3">
            <CarImage car={car} className="h-14 w-20 shrink-0 rounded-lg" />
            <div className="min-w-0">
              <p className="text-[10px] font-bold uppercase tracking-wider text-brand-500">Proses Penjualan · {car.code}</p>
              <h2 className="truncate font-display text-base font-black uppercase tracking-wide text-white">
                {getCarName(car, { withYear: true })}
              </h2>
              <p className="text-xs text-neutral-400">{formatRupiah(price)}</p>
            </div>
          </div>
          <button type="button" onClick={closeCarCheckout} aria-label="Tutup" className="a18-btn-ghost px-2">
            <X className="h-5 w-5" />
          </button>
        </div>

        <div className="grid gap-5 p-5 lg:grid-cols-[1.4fr_1fr]">
          {/* Form */}
          <div className="space-y-5">
            {/* Pembeli */}
            <div className="grid gap-3 sm:grid-cols-2">
              <div>
                <label htmlFor="co-name" className="a18-label">Nama Pembeli</label>
                <input
                  id="co-name"
                  value={customerName}
                  onChange={(e) => setCustomerName(e.target.value)}
                  className="a18-input"
                  placeholder="Nama sesuai KTP"
                />
                {errors.customerName && <p className="mt-1 text-xs text-brand-400">{errors.customerName}</p>}
              </div>
              <div>
                <label htmlFor="co-phone" className="a18-label">Nomor HP</label>
                <input
                  id="co-phone"
                  type="tel"
                  value={customerPhone}
                  onChange={(e) => setCustomerPhone(e.target.value)}
                  className="a18-input"
                  placeholder="0812-3456-7890"
                />
              </div>
            </div>

            {/* Diskon */}
            <div>
              <label htmlFor="co-discount" className="a18-label">Diskon (Rp)</label>
              <input
                id="co-discount"
                type="number"
                min={0}
                max={price}
                step={500000}
                value={discount}
                onChange={(e) => setDiscount(e.target.value)}
                className="a18-input"
              />
              {errors.discount && <p className="mt-1 text-xs text-brand-400">{errors.discount}</p>}
            </div>

            {/* Tukar tambah */}
            <div className="a18-card p-4">
              <label className="flex cursor-pointer items-center gap-2">
                <input
                  type="checkbox"
                  checked={useTradeIn}
                  onChange={(e) => setUseTradeIn(e.target.checked)}
                  className="h-4 w-4 accent-brand-600"
                />
                <span className="flex items-center gap-1.5 text-sm font-bold text-white">
                  <ArrowLeftRight className="h-4 w-4 text-brand-500" aria-hidden="true" />
                  Tukar Tambah Mobil Lama
                </span>
              </label>

              {useTradeIn && (
                <div className="mt-3 space-y-3">
                  <div>
                    <label htmlFor="co-tradein-req" className="a18-label">Ambil dari permintaan tukar tambah</label>
                    <select
                      id="co-tradein-req"
                      value={tradeInRequestId}
                      onChange={(e) => selectTradeInRequest(e.target.value)}
                      className="a18-input"
                    >
                      <option value="">— Isi manual —</option>
                      {tradeInOptions.map(request => (
                        <option key={request.id} value={request.id}>
                          {request.customerName} · {request.brand} {request.model} {request.year}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="grid gap-3 sm:grid-cols-2">
                    <div>
                      <label htmlFor="co-tradein-name" className="a18-label">Mobil Lama</label>
                      <input
                        id="co-tradein-name"
                        value={tradeInName}
                        onChange={(e) => setTradeInName(e.target.value)}
                        className="a18-input"
                        placeholder="Honda Jazz RS 2017"
                      />
                    </div>
                    <div>
                      <label htmlFor="co-tradein-value" className="a18-label">Nilai Tukar Tambah (Rp)</label>
                      <input
                        id="co-tradein-value"
                        type="number"
                        min={0}
                        step={500000}
                        value={tradeInValue}
                        onChange={(e) => setTradeInValue(e.target.value)}
                        className="a18-input"
                      />
                    </div>
                  </div>
                  {tradeInRequestId ? (
                    <p className="text-xs text-neutral-500">
                      Mobil ini otomatis masuk ke stok showroom setelah penjualan diselesaikan.
                    </p>
                  ) : (
                    <p className="text-xs text-amber-400">
                      Tukar tambah manual hanya mengurangi sisa bayar — mobil lama ini TIDAK otomatis masuk
                      stok. Tambahkan lewat Stok Mobil → Tambah Mobil, atau pilih dari daftar permintaan
                      tukar tambah yang sudah diinspeksi di atas.
                    </p>
                  )}
                  {errors.tradeIn && <p className="text-xs text-brand-400">{errors.tradeIn}</p>}
                </div>
              )}
            </div>

            {/* Metode pembayaran */}
            <div>
              <span className="a18-label">Metode Pembayaran</span>
              <div className="isolate grid grid-cols-3 gap-2">
                {METHODS.map(({ id, label, icon: Icon }) => {
                  const isSelected = paymentMethod === id;
                  return (
                    <button
                      key={id}
                      type="button"
                      onClick={() => setPaymentMethod(id)}
                      aria-pressed={isSelected}
                      className={`relative flex flex-col items-center gap-1.5 rounded-xl border px-3 py-3 text-xs font-bold transition-colors ${
                        isSelected
                          ? 'border-transparent text-white'
                          : 'border-white/15 text-neutral-300 hover:border-white/40 hover:text-white'
                      }`}
                    >
                      {isSelected && (
                        <motion.span
                          layoutId="checkout-method-pill"
                          layoutDependency={paymentMethod}
                          transition={PILL_SPRING}
                          aria-hidden="true"
                          className="absolute -inset-px z-[1] border border-brand-500 bg-brand-600"
                          style={{ borderRadius: 12 }}
                        />
                      )}
                      <Icon className="relative z-10 h-5 w-5" aria-hidden="true" />
                      <span className="relative z-10">{label}</span>
                    </button>
                  );
                })}
              </div>
            </div>

            {paymentMethod === 'cash' && (
              <div>
                <label htmlFor="co-paid" className="a18-label">Uang Diterima (Rp)</label>
                <input
                  id="co-paid"
                  type="number"
                  min={0}
                  step={100000}
                  value={paidAmount}
                  onChange={(e) => setPaidAmount(e.target.value)}
                  placeholder={`Pas ${formatRupiah(amountDue)}`}
                  className="a18-input"
                />
                <div className="mt-2 flex flex-wrap gap-2">
                  {[...new Set(quickAmounts)].map((amount, index) => (
                    <button
                      key={amount}
                      type="button"
                      onClick={() => setPaidAmount(amount)}
                      className="rounded-xl border border-white/15 px-3 py-1.5 text-xs font-semibold text-neutral-300 transition-colors hover:border-brand-500 hover:text-white"
                    >
                      {index === 0 ? 'Uang Pas' : formatRupiah(amount)}
                    </button>
                  ))}
                </div>
                {errors.paidAmount && <p className="mt-1 text-xs text-brand-400">{errors.paidAmount}</p>}
              </div>
            )}

            {paymentMethod === 'transfer' && (
              <div className="rounded-xl border border-white/10 bg-ink-900 p-4 text-sm text-neutral-300">
                <p className="font-bold text-white">Transfer ke rekening showroom</p>
                <p className="mt-1 text-xs text-neutral-400">
                  Pastikan dana sebesar <span className="font-bold text-white">{formatRupiah(amountDue)}</span> sudah diterima
                  sebelum menyelesaikan transaksi.
                </p>
              </div>
            )}

            {paymentMethod === 'credit' && (
              <div className="space-y-3">
                <div>
                  <label htmlFor="co-leasing" className="a18-label">Leasing</label>
                  <select id="co-leasing" value={leasing} onChange={(e) => setLeasing(e.target.value)} className="a18-input">
                    {LEASING_PARTNERS.map(partner => <option key={partner} value={partner}>{partner}</option>)}
                  </select>
                </div>
                <CreditSimulator price={amountDue} compact onChange={setCreditSim} />
                {errors.credit && <p className="text-xs text-brand-400">{errors.credit}</p>}
              </div>
            )}

            <div>
              <label htmlFor="co-notes" className="a18-label">Catatan (opsional)</label>
              <textarea
                id="co-notes"
                rows={2}
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                className="a18-input"
                placeholder="Misal: pelat nomor diurus showroom"
              />
            </div>
          </div>

          {/* Ringkasan */}
          <div className="lg:sticky lg:top-4 lg:self-start">
            <div className="a18-card p-5">
              <h3 className="font-display text-sm font-black uppercase tracking-wide text-white">Ringkasan</h3>

              <dl className="mt-4 space-y-2 text-sm">
                {summaryRows.map(row => (
                  <div key={row.label} className="flex items-center justify-between gap-3">
                    <dt className="text-neutral-400">{row.label}</dt>
                    <dd className={`${row.accent ? 'text-brand-400' : 'text-white'} ${row.bold ? 'font-bold' : 'font-semibold'}`}>
                      {row.value}
                    </dd>
                  </div>
                ))}
              </dl>

              <div className="mt-4 rounded-2xl border border-brand-600/40 bg-brand-950/40 p-4">
                <p className="text-[10px] font-bold uppercase tracking-wider text-brand-300">Sisa Bayar</p>
                <p className="font-display text-2xl font-black text-white"><Money value={amountDue} /></p>
              </div>

              {paymentMethod === 'cash' && (
                <div className="mt-3 flex items-center justify-between text-sm">
                  <span className="text-neutral-400">Kembalian</span>
                  <span className="font-bold text-white">
                    <Money value={Math.max(0, (Number.isFinite(effectivePaid) ? effectivePaid : 0) - amountDue)} />
                  </span>
                </div>
              )}

              {paymentMethod === 'credit' && creditSim && (
                <dl className="mt-3 space-y-2 text-sm">
                  <div className="flex items-center justify-between">
                    <dt className="text-neutral-400">DP ({creditSim.dpPercent}%)</dt>
                    <dd className="font-bold text-white"><Money value={creditSim.dpAmount} /></dd>
                  </div>
                  <div className="flex items-center justify-between">
                    <dt className="text-neutral-400">Cicilan</dt>
                    <dd className="font-bold text-white">
                      <Money value={creditSim.monthly} /> × {creditSim.tenorMonths} bln
                    </dd>
                  </div>
                  <div className="flex items-center justify-between">
                    <dt className="text-neutral-400">Leasing</dt>
                    <dd className="font-semibold text-white">{leasing}</dd>
                  </div>
                </dl>
              )}

              <div className="mt-4 flex items-center justify-between border-t border-white/10 pt-3 text-xs">
                <span className="text-neutral-500">Estimasi laba</span>
                <span className={`font-bold ${profit >= 0 ? 'text-emerald-400' : 'text-brand-400'}`}>
                  {formatRupiah(profit)}
                </span>
              </div>

              {profit < 0 && (
                <p className="mt-2 flex items-start gap-1.5 text-xs text-amber-400">
                  <AlertTriangle className="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                  Harga jual di bawah modal pembelian.
                </p>
              )}

              <button type="submit" className="a18-btn-primary mt-5 w-full py-3">
                <CheckCircle2 className="h-4 w-4" />
                Selesaikan Penjualan
              </button>
              <button type="button" onClick={closeCarCheckout} className="a18-btn-ghost mt-2 w-full justify-center">
                Batal
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  );
}
