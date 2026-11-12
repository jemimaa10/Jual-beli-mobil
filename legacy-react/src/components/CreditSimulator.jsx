import React, { useEffect, useId, useMemo, useRef, useState } from 'react';
import { Calculator, Info } from 'lucide-react';
import { motion } from 'motion/react';
import { useApp } from '../context/AppContext';
import { TENOR_OPTIONS } from '../data/initialData';
import { calcInstallment } from '../utils/carUtils';
import { formatRupiah } from '../utils/formatters';
import Money from './Money';

const PILL_SPRING = { type: 'spring', bounce: 0.15, duration: 0.35 };

// `|| fallback` membuang 0 yang valid (promo DP 0% / bunga 0%); ini menjaga 0 dan
// hanya jatuh ke fallback saat nilainya benar-benar kosong/tidak berupa angka.
const toSetting = (value, fallback) => {
  const n = Number(value);
  return value !== '' && value != null && Number.isFinite(n) ? n : fallback;
};

/**
 * Simulasi kredit mobil (bunga flat).
 * onChange dipanggil setiap hasil berubah: { dpPercent, tenorMonths, annualRate, dpAmount, principal, totalInterest, monthly, totalPayment }
 */
export default function CreditSimulator({ price, onChange, initialDpPercent, initialTenor = 36, compact = false }) {
  const { storeInfo } = useApp();
  const uid = useId();
  const minDp = Math.min(70, toSetting(storeInfo.minDpPercent, 20));
  const defaultRate = toSetting(storeInfo.creditRate, 7);

  const [dpPercent, setDpPercent] = useState(() => Math.max(minDp, toSetting(initialDpPercent, minDp)));
  const [tenorMonths, setTenorMonths] = useState(initialTenor);
  const [annualRate, setAnnualRate] = useState(defaultRate);

  // Settings.minDpPercent bisa sampai 90%, di atas batas slider lama (70%); dan DP yang
  // sedang dipilih tidak boleh berada di bawah minimum baru kalau pengaturan berubah.
  const sliderMax = Math.max(70, minDp);
  useEffect(() => { setDpPercent(p => Math.max(minDp, p)); }, [minDp]);
  // Hanya ikut berubah kalau creditRate di Pengaturan benar-benar berganti nilai (bukan
  // setiap kali storeInfo berubah untuk alasan lain), jadi tidak menimpa angka yang
  // sedang diketik pengguna di kolom bunga.
  useEffect(() => { setAnnualRate(defaultRate); }, [defaultRate]);

  const result = useMemo(
    () => calcInstallment({ price, dpPercent, tenorMonths, annualRate }),
    [price, dpPercent, tenorMonths, annualRate]
  );

  // Simpan callback di ref agar parent tanpa useCallback tidak memicu render berulang
  const onChangeRef = useRef(onChange);
  useEffect(() => {
    onChangeRef.current = onChange;
  }, [onChange]);

  useEffect(() => {
    onChangeRef.current?.({ dpPercent, tenorMonths, annualRate, ...result });
  }, [dpPercent, tenorMonths, annualRate, result]);

  const rows = [
    { label: 'Uang Muka (DP)', value: result.dpAmount },
    { label: 'Pokok Pinjaman', value: result.principal },
    { label: 'Total Bunga', value: result.totalInterest },
    { label: 'Total Pembayaran', value: result.totalPayment },
  ];

  return (
    <div className={`a18-card ${compact ? 'p-4' : 'p-5 sm:p-6'}`}>
      <div className="flex items-center gap-2">
        <Calculator className="h-4 w-4 text-brand-500" aria-hidden="true" />
        <h3 className="font-display text-sm font-black uppercase tracking-wide text-white">Simulasi Kredit</h3>
      </div>

      <p className="mt-1 text-xs text-neutral-500">
        Harga kendaraan <span className="font-semibold text-neutral-300">{formatRupiah(price)}</span>
      </p>

      <div className="mt-4 space-y-4">
        {/* DP */}
        <div>
          <div className="flex items-center justify-between">
            <label htmlFor={`${uid}-dp`} className="a18-label mb-0">Uang Muka</label>
            <span className="text-xs font-bold text-white">
              {dpPercent}% · <Money value={result.dpAmount} />
            </span>
          </div>
          <input
            id={`${uid}-dp`}
            type="range"
            min={minDp}
            max={sliderMax}
            step={5}
            value={dpPercent}
            onChange={(e) => setDpPercent(Number(e.target.value))}
            className="mt-2 w-full accent-brand-600"
          />
          <div className="flex justify-between text-[10px] text-neutral-500">
            <span>{minDp}%</span>
            <span>{sliderMax}%</span>
          </div>
        </div>

        {/* Tenor */}
        <div>
          <span className="a18-label">Tenor</span>
          {/* isolate: z-index pil & teks tidak bocor ke header sticky modal */}
          <div className="isolate flex flex-wrap gap-2">
            {TENOR_OPTIONS.map(tenor => {
              const isSelected = tenorMonths === tenor;
              return (
                <button
                  key={tenor}
                  type="button"
                  onClick={() => setTenorMonths(tenor)}
                  aria-pressed={isSelected}
                  className={`relative rounded-xl border px-3 py-1.5 text-xs font-bold transition-colors ${
                    isSelected
                      ? 'border-transparent text-white'
                      : 'border-white/15 text-neutral-300 hover:border-white/40 hover:text-white'
                  }`}
                >
                  {isSelected && (
                    // layoutId unik per instance (useId): simulator bisa tampil dua kali sekaligus.
                    // layoutDependency: hanya bergeser saat tenor diganti, bukan saat layout halaman berubah.
                    <motion.span
                      layoutId={`${uid}-tenor-pill`}
                      layoutDependency={tenorMonths}
                      transition={PILL_SPRING}
                      aria-hidden="true"
                      className="absolute -inset-px z-[1] border border-brand-500 bg-brand-600"
                      style={{ borderRadius: 12 }}
                    />
                  )}
                  <span className="relative z-10">{tenor} bln</span>
                </button>
              );
            })}
          </div>
        </div>

        {/* Bunga */}
        <div>
          <label htmlFor={`${uid}-rate`} className="a18-label">Bunga Flat (% / tahun)</label>
          <input
            id={`${uid}-rate`}
            type="number"
            min={0}
            max={30}
            step={0.1}
            value={annualRate}
            onChange={(e) => setAnnualRate(Math.min(30, Math.max(0, toSetting(e.target.value, 0))))}
            className="a18-input"
          />
        </div>
      </div>

      {/* Hasil */}
      <div className="mt-5 rounded-2xl border border-brand-600/40 bg-brand-950/40 p-4">
        <p className="text-[10px] font-bold uppercase tracking-wider text-brand-300">Cicilan per Bulan</p>
        <p className="font-display text-2xl font-black text-white sm:text-3xl"><Money value={result.monthly} /></p>
        <p className="mt-0.5 text-xs text-neutral-400">selama {tenorMonths} bulan</p>
      </div>

      <dl className="mt-4 space-y-2">
        {rows.map(row => (
          <div key={row.label} className="flex items-center justify-between gap-3 text-sm">
            <dt className="text-neutral-400">{row.label}</dt>
            <dd className="font-semibold text-white"><Money value={row.value} /></dd>
          </div>
        ))}
      </dl>

      <p className="mt-4 flex items-start gap-1.5 text-[11px] leading-relaxed text-neutral-500">
        <Info className="mt-0.5 h-3 w-3 shrink-0" aria-hidden="true" />
        Estimasi memakai bunga flat. Angka final, biaya admin, dan asuransi mengikuti ketentuan leasing.
      </p>
    </div>
  );
}
