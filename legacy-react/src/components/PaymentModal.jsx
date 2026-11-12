import React, { useState, useEffect } from 'react';
import {
  X,
  Banknote,
  QrCode,
  Building2,
  CheckCircle,
  AlertCircle,
  ArrowRight
} from 'lucide-react';
import { motion } from 'motion/react';
import { useApp } from '../context/AppContext';
import { formatRupiah } from '../utils/formatters';
import { toast } from '../utils/toast';
import Money from './Money';

const PILL_SPRING = { type: 'spring', bounce: 0.15, duration: 0.35 };

export default function PaymentModal() {
  const {
    isPaymentModalOpen,
    setIsPaymentModalOpen,
    cartTotal,
    completeTransaction,
    storeInfo
  } = useApp();

  const [paymentMethod, setPaymentMethod] = useState('cash'); // 'cash', 'qris', 'transfer'
  const [customerName, setCustomerName] = useState('Pelanggan Umum');
  const [cashAmount, setCashAmount] = useState('');
  const [selectedBank, setSelectedBank] = useState('BCA');

  // Reset inputs when modal opens
  useEffect(() => {
    if (isPaymentModalOpen) {
      setCashAmount('');
      setPaymentMethod('cash');
      setCustomerName('Pelanggan Umum');
    }
  }, [isPaymentModalOpen, cartTotal]);

  if (!isPaymentModalOpen) return null;

  const numericCash = Number(cashAmount) || 0;
  const change = numericCash - cartTotal;
  const isCashSufficient = numericCash >= cartTotal;

  // Nominal cepat dihitung dari total transaksi (bukan daftar tetap), supaya keranjang
  // di atas Rp 200.000 tetap punya pilihan selain "Uang Pas".
  const roundUpTo = (n, step) => Math.ceil(n / step) * step;
  const quickCashOptions = cartTotal > 0
    ? [
        { label: 'Uang Pas', value: cartTotal },
        ...[...new Set([50000, 100000, 500000, 1000000].map(step => roundUpTo(cartTotal, step)))]
          .filter(value => value > cartTotal)
          .map(value => ({ label: formatRupiah(value), value })),
      ]
    : [{ label: 'Uang Pas', value: 0 }];

  const handleSubmit = (e) => {
    e?.preventDefault();
    if (paymentMethod === 'cash') {
      if (!isCashSufficient) {
        toast.error('Nominal uang bayar masih kurang!');
        return;
      }
      completeTransaction({
        paymentMethod: 'cash',
        paidAmount: numericCash,
        customerName
      });
    } else {
      completeTransaction({
        paymentMethod,
        paidAmount: cartTotal,
        customerName
      });
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
      <div
        role="dialog"
        aria-modal="true"
        aria-label="Pembayaran kasir"
        className="bg-ink-800 border border-white/10 rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden flex flex-col max-h-[92vh]"
      >

        {/* Header modal */}
        <div className="p-5 border-b border-white/10 flex items-center justify-between gap-2">
          <div className="min-w-0">
            <h3 className="a18-heading text-lg">Pembayaran Kasir</h3>
            <p className="text-xs text-neutral-400">Pilih metode &amp; selesaikan transaksi</p>
          </div>
          <button
            type="button"
            onClick={() => setIsPaymentModalOpen(false)}
            aria-label="Tutup pembayaran"
            className="p-2 rounded-xl text-neutral-400 hover:text-white hover:bg-white/10 transition-colors"
          >
            <X className="w-5 h-5" aria-hidden="true" />
          </button>
        </div>

        {/* Total tagihan */}
        <div className="a18-stripes-soft p-6 text-center">
          <span className="text-[10px] uppercase font-black tracking-[0.2em] text-brand-500">Total Tagihan</span>
          <div className="font-display text-3xl font-black text-white mt-1">
            <Money value={cartTotal} />
          </div>
        </div>

        <div className="p-6 space-y-5 overflow-y-auto flex-1">

          {/* Nama pelanggan */}
          <div>
            <label htmlFor="pay-customer" className="a18-label">Nama Pelanggan (Opsional)</label>
            <input
              id="pay-customer"
              type="text"
              value={customerName}
              onChange={(e) => setCustomerName(e.target.value)}
              placeholder="Contoh: Pelanggan Umum, Bpk. Rizky, dll."
              className="a18-input"
            />
          </div>

          {/* Metode pembayaran */}
          <div>
            <span className="a18-label">Metode Pembayaran</span>
            <div className="isolate grid grid-cols-3 gap-2.5">
              {[
                { id: 'cash', label: 'Tunai', icon: Banknote },
                { id: 'qris', label: 'QRIS', icon: QrCode },
                { id: 'transfer', label: 'Transfer', icon: Building2 },
              ].map(m => {
                const Icon = m.icon;
                const isSel = paymentMethod === m.id;
                return (
                  <button
                    key={m.id}
                    type="button"
                    onClick={() => setPaymentMethod(m.id)}
                    aria-pressed={isSel}
                    className={`relative p-3 rounded-2xl border flex flex-col items-center justify-center gap-1.5 transition-colors ${
                      isSel
                        ? 'border-transparent text-white font-bold'
                        : 'border-white/10 bg-ink-900 text-neutral-400 hover:border-white/25 hover:text-white'
                    }`}
                  >
                    {isSel && (
                      <motion.span
                        layoutId="pos-payment-method-pill"
                        layoutDependency={paymentMethod}
                        transition={PILL_SPRING}
                        aria-hidden="true"
                        className="absolute -inset-px z-[1] border border-brand-600 bg-brand-600/15"
                        style={{ borderRadius: 16 }}
                      />
                    )}
                    <Icon className={`relative z-10 w-5 h-5 ${isSel ? 'text-brand-500' : 'text-neutral-500'}`} aria-hidden="true" />
                    <span className="relative z-10 text-xs">{m.label}</span>
                  </button>
                );
              })}
            </div>
          </div>

          {/* METODE: TUNAI */}
          {paymentMethod === 'cash' && (
            <div className="space-y-4 pt-2">
              <div>
                <label htmlFor="pay-cash" className="a18-label">Uang Diterima (Rp)</label>
                <div className="relative">
                  <span className="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-neutral-500 text-sm pointer-events-none">Rp</span>
                  <input
                    id="pay-cash"
                    type="number"
                    value={cashAmount}
                    onChange={(e) => setCashAmount(e.target.value)}
                    placeholder="0"
                    autoFocus
                    className="a18-input pl-10 text-base font-bold"
                  />
                </div>
              </div>

              {/* Nominal cepat */}
              <div className="flex flex-wrap gap-2">
                {quickCashOptions.map((opt) => (
                  <button
                    key={opt.value}
                    type="button"
                    onClick={() => setCashAmount(String(opt.value))}
                    className="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-neutral-200 text-xs font-semibold transition-colors"
                  >
                    {opt.label}
                  </button>
                ))}
              </div>

              {/* Pratinjau kembalian */}
              {numericCash > 0 && (
                <div className={`p-3.5 rounded-xl border flex items-center justify-between gap-2 ${
                  isCashSufficient
                    ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300'
                    : 'bg-brand-600/15 border-brand-500/40 text-brand-300'
                }`}>
                  <div className="flex items-center gap-2">
                    {isCashSufficient ? (
                      <CheckCircle className="w-5 h-5 text-emerald-400" aria-hidden="true" />
                    ) : (
                      <AlertCircle className="w-5 h-5 text-brand-400" aria-hidden="true" />
                    )}
                    <span className="text-xs font-semibold">
                      {isCashSufficient ? 'Kembalian:' : 'Uang Kurang:'}
                    </span>
                  </div>
                  <span className="font-display font-black text-base">
                    <Money value={Math.abs(change)} />
                  </span>
                </div>
              )}
            </div>
          )}

          {/* METODE: QRIS */}
          {paymentMethod === 'qris' && (
            <div className="p-4 rounded-2xl bg-ink-900 border border-white/10 text-center space-y-3">
              <div className="w-44 h-44 mx-auto bg-white p-3 rounded-2xl flex flex-col items-center justify-center">
                {/* Simulasi QR code */}
                <svg className="w-full h-full text-slate-900" viewBox="0 0 100 100" fill="currentColor" role="img" aria-label="Simulasi kode QRIS">
                  <path d="M10 10h30v30H10zM50 10h10v10H50zM70 10h20v20H70zM50 30h10v10H50zM15 15v20h20V15H15zM20 20h10v10H20zM75 15v10h10V15H75zM10 50h10v20H10zM30 50h20v10H30zM70 50h20v10H70zM10 80h30v10H10zM60 70h10v20H60zM80 80h10v10H80zM30 70h10v10H30zM50 80h10v10H50z" />
                </svg>
              </div>
              <div>
                <p className="font-bold text-sm text-white">Scan QRIS Nasional</p>
                <p className="text-xs text-neutral-400">BCA, Mandiri, BRI, GoPay, OVO, Dana, ShopeePay</p>
                <p className="text-xs font-bold text-brand-400 mt-1">Total: {formatRupiah(cartTotal)}</p>
              </div>
            </div>
          )}

          {/* METODE: TRANSFER */}
          {paymentMethod === 'transfer' && (
            <div className="space-y-3">
              <div className="grid grid-cols-3 gap-2">
                {['BCA', 'Mandiri', 'BRI'].map(bank => (
                  <button
                    key={bank}
                    type="button"
                    onClick={() => setSelectedBank(bank)}
                    aria-pressed={selectedBank === bank}
                    className={`py-2 rounded-xl text-xs font-bold border transition-colors ${
                      selectedBank === bank
                        ? 'border-brand-600 bg-brand-600/15 text-white'
                        : 'border-white/10 bg-ink-900 text-neutral-400 hover:border-white/25 hover:text-white'
                    }`}
                  >
                    {bank}
                  </button>
                ))}
              </div>

              <div className="p-4 rounded-xl bg-ink-900 border border-white/10 text-xs space-y-1.5">
                <div className="flex justify-between gap-2">
                  <span className="text-neutral-500">Bank Tujuan:</span>
                  <span className="font-bold text-white">Bank {selectedBank}</span>
                </div>
                <div className="flex justify-between gap-2">
                  <span className="text-neutral-500">Nomor Rekening:</span>
                  <span className="font-mono font-bold text-white">
                    {selectedBank === 'BCA' ? '8830-1234-56' : selectedBank === 'Mandiri' ? '1310-0099-8822' : '0021-9988-7766-50'}
                  </span>
                </div>
                <div className="flex justify-between gap-2">
                  <span className="text-neutral-500">Atas Nama:</span>
                  <span className="font-semibold text-white">{storeInfo.name}</span>
                </div>
              </div>
            </div>
          )}

        </div>

        {/* Footer modal */}
        <div className="p-4 border-t border-white/10 bg-ink-900 flex flex-col-reverse sm:flex-row items-stretch sm:items-center sm:justify-end gap-2">
          <button
            type="button"
            onClick={() => setIsPaymentModalOpen(false)}
            className="a18-btn-ghost"
          >
            Batal
          </button>
          <button
            type="button"
            onClick={handleSubmit}
            disabled={paymentMethod === 'cash' && !isCashSufficient}
            className="a18-btn-primary px-6"
          >
            <span>Selesaikan &amp; Cetak Struk</span>
            <ArrowRight className="w-4 h-4" aria-hidden="true" />
          </button>
        </div>

      </div>
    </div>
  );
}
