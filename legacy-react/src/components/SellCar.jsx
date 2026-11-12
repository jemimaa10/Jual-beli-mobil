import React, { useEffect, useMemo, useRef, useState } from 'react';
import { AnimatePresence, motion } from 'motion/react';
import {
  ArrowLeft, ArrowRight, CheckCircle2, ClipboardList, CalendarCheck, UserRound, Sparkles, ArrowLeftRight, Info
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import CarImage from './CarImage';
import Money from './Money';
import { FaqSection, HowItWorksSection, SocialLinks } from './Auto18Sections';
import {
  BODY_TYPES, CAR_BRANDS, CAR_COLORS, CAR_CONDITIONS, CAR_LOCATIONS,
  FAQS_SELL, FUEL_TYPES, HOW_IT_WORKS_SELL, TRANSMISSIONS
} from '../data/initialData';
import { estimateCarPrice, getCarName, isValidPhone } from '../utils/carUtils';
import { formatRupiah } from '../utils/formatters';

const TIME_SLOTS = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];

const todayLocal = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const currentYear = new Date().getFullYear();
const YEARS = Array.from({ length: 26 }, (_, i) => currentYear - i);

const STEPS = [
  { no: 1, label: 'Data Mobil', icon: ClipboardList },
  { no: 2, label: 'Jadwal Inspeksi', icon: CalendarCheck },
  { no: 3, label: 'Data Kontak', icon: UserRound },
];

// Isi langkah bergeser searah navigasi: maju = masuk dari kanan & keluar ke kiri,
// mundur = sebaliknya. `dir` datang dari prop custom AnimatePresence (1 / -1), jadi
// langkah yang sedang keluar pun memakai arah terbaru.
const STEP_OFFSET = 24;
const STEP_VARIANTS = {
  enter: (dir) => ({ x: dir * STEP_OFFSET, opacity: 0 }),
  center: { x: 0, opacity: 1, transition: { duration: 0.2, ease: [0.22, 1, 0.36, 1] } },
  exit: (dir) => ({ x: dir * -STEP_OFFSET, opacity: 0, transition: { duration: 0.15, ease: 'easeIn' } }),
};

// Garis penghubung stepper terisi (dari kiri) saat langkah selesai, kosong lagi saat mundur
const LINE_TRANSITION = { duration: 0.3, ease: [0.22, 1, 0.36, 1] };

// Ikon di lingkaran stepper: ikon langkah <-> centang saling berganti dengan "pop" kecil
const STEP_ICON_SHOWN = {
  scale: 1,
  opacity: 1,
  transition: { scale: { type: 'spring', bounce: 0.3, duration: 0.3 }, opacity: { duration: 0.12, ease: 'easeOut' } },
};
const STEP_ICON_HIDDEN = { scale: 0.5, opacity: 0, transition: { duration: 0.1, ease: 'easeIn' } };

const emptyForm = {
  brand: 'Toyota',
  model: '',
  variant: '',
  year: currentYear - 5,
  bodyType: 'MPV',
  transmission: 'Otomatis',
  fuel: 'Bensin',
  mileage: '',
  color: 'Putih',
  location: 'Jakarta Barat',
  condition: 'Baik',
  askingPrice: '',
  inspectionDate: todayLocal(),
  inspectionTime: '10:00',
  tradeInCarId: '',
  customerName: '',
  phone: '',
  email: '',
  notes: '',
};

export default function SellCar() {
  const { cars, addSellRequest, setActiveTab } = useApp();

  const [type, setType] = useState('sell'); // 'sell' | 'trade-in'
  const [step, setStep] = useState(1);
  const [form, setForm] = useState(emptyForm);
  const [errors, setErrors] = useState({});
  const [submitted, setSubmitted] = useState(null);

  // Arah animasi perpindahan langkah (1 = maju, -1 = mundur), diturunkan dari perubahan
  // `step` saat render. Dengan begitu semua jalur — Lanjut, Kembali, maupun submit yang
  // melompat balik ke langkah pertama yang gagal — otomatis mendapat arah yang benar.
  const [stepMotion, setStepMotion] = useState({ step, direction: 1 });
  if (stepMotion.step !== step) {
    setStepMotion({ step, direction: step > stepMotion.step ? 1 : -1 });
  }
  const stepDirection = stepMotion.direction;

  // Tinggi isi langkah diukur, lalu dipakai sebagai tinggi pembungkusnya (dengan transisi
  // CSS) supaya kartu form memanjang/memendek halus saat langkah berganti, tanpa men-skala
  // teks. null = tinggi otomatis (belum terukur).
  const isFormView = !submitted;
  const stepBodyRef = useRef(null);
  const [stepBodyHeight, setStepBodyHeight] = useState(null);
  useEffect(() => {
    if (!isFormView) return undefined;
    const el = stepBodyRef.current;
    if (!el || typeof ResizeObserver === 'undefined') return undefined;
    const observer = new ResizeObserver(([entry]) => {
      const blockSize = entry?.borderBoxSize?.[0]?.blockSize;
      setStepBodyHeight(typeof blockSize === 'number' ? blockSize : el.getBoundingClientRect().height);
    });
    observer.observe(el);
    return () => {
      observer.disconnect();
      // Form dilepas (layar sukses): lupakan tinggi lama agar saat "Ajukan Lagi" kartu
      // tidak beranimasi dari tinggi langkah 3 yang sudah basi.
      setStepBodyHeight(null);
    };
  }, [isFormView]);

  const setField = (patch) => setForm(prev => ({ ...prev, ...patch }));

  const availableCars = useMemo(() => cars.filter(c => c.status === 'available'), [cars]);

  const estimate = useMemo(() => estimateCarPrice({
    brand: form.brand,
    model: form.model,
    bodyType: form.bodyType,
    year: form.year,
    mileage: form.mileage,
    transmission: form.transmission,
    condition: form.condition,
  }), [form.brand, form.model, form.bodyType, form.year, form.mileage, form.transmission, form.condition]);

  const targetCar = availableCars.find(c => c.id === form.tradeInCarId) || null;
  const priceGap = targetCar ? Math.max(0, targetCar.price - estimate.mid) : 0;

  const validateStep = (currentStep) => {
    const next = {};
    if (currentStep === 1) {
      if (!form.model.trim()) next.model = 'Model mobil wajib diisi';
      if (form.mileage === '' || Number(form.mileage) < 0) next.mileage = 'Kilometer wajib diisi';
      else if (Number(form.mileage) > 1000000) next.mileage = 'Kilometer tidak wajar';
    }
    if (currentStep === 2) {
      if (!form.inspectionDate) next.inspectionDate = 'Tanggal inspeksi wajib dipilih';
      else if (form.inspectionDate < todayLocal()) next.inspectionDate = 'Tanggal sudah lewat';
      if (type === 'trade-in' && !form.tradeInCarId) next.tradeInCarId = 'Pilih mobil yang ingin ditukar';
    }
    if (currentStep === 3) {
      if (!form.customerName.trim()) next.customerName = 'Nama wajib diisi';
      if (!isValidPhone(form.phone)) next.phone = 'Nomor HP tidak valid';
      if (form.email.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) next.email = 'Email tidak valid';
    }
    setErrors(next);
    return Object.keys(next).length === 0;
  };

  const goNext = () => {
    if (validateStep(step)) setStep(s => Math.min(3, s + 1));
  };

  const submit = (e) => {
    e.preventDefault();
    // Validasi ketiga langkah, bukan cuma langkah 3 — kalau kasir sempat mundur ke
    // langkah 1/2 setelah mengisi langkah 3, data yang belum lengkap tidak boleh lolos.
    for (const s of [1, 2, 3]) {
      if (!validateStep(s)) {
        setStep(s);
        return;
      }
    }

    const created = addSellRequest({
      type,
      customerName: form.customerName.trim(),
      phone: form.phone.trim(),
      email: form.email.trim(),
      brand: form.brand,
      model: form.model.trim(),
      variant: form.variant.trim(),
      year: Number(form.year),
      bodyType: form.bodyType,
      transmission: form.transmission,
      fuel: form.fuel,
      mileage: Number(form.mileage),
      color: form.color,
      location: form.location,
      condition: form.condition,
      askingPrice: Number(form.askingPrice) || 0,
      estimateLow: estimate.low,
      estimateHigh: estimate.high,
      inspectionDate: form.inspectionDate,
      inspectionTime: form.inspectionTime,
      tradeInCarId: type === 'trade-in' ? form.tradeInCarId : null,
      notes: form.notes.trim(),
    });

    setSubmitted({ request: created, estimate, targetCar });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const resetAll = () => {
    setForm({ ...emptyForm, inspectionDate: todayLocal() });
    setErrors({});
    setStep(1);
    setSubmitted(null);
  };

  // ---------------------------------------------------------------- Sukses
  if (submitted) {
    const { request } = submitted;
    return (
      <div>
        <section className="a18-stripes py-14">
          <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="a18-card p-6 sm:p-8">
              <div className="flex items-center gap-3">
                {/* Satu-satunya gerak di layar sukses: lencana centang "pop" sekali saat muncul */}
                <motion.span
                  initial={{ scale: 0.6 }}
                  animate={{ scale: 1 }}
                  transition={{ type: 'spring', bounce: 0.35, duration: 0.4 }}
                  className="flex h-12 w-12 items-center justify-center rounded-full bg-brand-600 text-white"
                >
                  <CheckCircle2 className="h-6 w-6" aria-hidden="true" />
                </motion.span>
                <div>
                  <h1 className="a18-heading text-xl sm:text-2xl">Permintaan Terkirim</h1>
                  <p className="text-sm text-neutral-400">
                    Kode permintaan <span className="font-mono font-bold text-white">{request.code}</span>
                  </p>
                </div>
              </div>

              <dl className="mt-6 space-y-3 border-t border-white/10 pt-5 text-sm">
                <div className="flex justify-between gap-4">
                  <dt className="text-neutral-400">Mobil</dt>
                  <dd className="text-right font-semibold text-white">
                    {request.brand} {request.model} {request.variant} {request.year}
                  </dd>
                </div>
                <div className="flex justify-between gap-4">
                  <dt className="text-neutral-400">Jenis</dt>
                  <dd className="font-semibold text-white">{request.type === 'trade-in' ? 'Tukar Tambah' : 'Jual Langsung'}</dd>
                </div>
                <div className="flex justify-between gap-4">
                  <dt className="text-neutral-400">Estimasi Harga</dt>
                  <dd className="text-right font-semibold text-white">
                    {formatRupiah(request.estimateLow)} – {formatRupiah(request.estimateHigh)}
                  </dd>
                </div>
                <div className="flex justify-between gap-4">
                  <dt className="text-neutral-400">Jadwal Inspeksi</dt>
                  <dd className="font-semibold text-white">{request.inspectionDate} · {request.inspectionTime}</dd>
                </div>
                {submitted.targetCar && (
                  <div className="flex justify-between gap-4">
                    <dt className="text-neutral-400">Ditukar dengan</dt>
                    <dd className="text-right font-semibold text-white">{getCarName(submitted.targetCar, { withYear: true })}</dd>
                  </div>
                )}
              </dl>

              <p className="mt-5 text-sm text-neutral-400">
                Tim Auto18 akan menghubungi Anda untuk konfirmasi jadwal inspeksi. Harga final ditentukan setelah pengecekan kondisi mobil.
              </p>

              <div className="mt-6 flex flex-wrap gap-3">
                <button type="button" onClick={resetAll} className="a18-btn-outline">Ajukan Lagi</button>
                <button type="button" onClick={() => setActiveTab('buy-car')} className="a18-btn-primary">
                  Lihat Mobil Dijual
                  <ArrowRight className="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        </section>

        <HowItWorksSection title="Cara Jual Mobil di Auto18" steps={HOW_IT_WORKS_SELL} />
        <FaqSection title="Pertanyaan Seputar Jual Mobil" faqs={FAQS_SELL} />
      </div>
    );
  }

  // ---------------------------------------------------------------- Form
  return (
    <div>
      {/* Hero */}
      <section className="a18-stripes">
        <div className="max-w-7xl mx-auto px-4 py-12 sm:px-6 sm:py-14 lg:px-8">
          <h1 className="a18-heading text-3xl leading-tight sm:text-5xl">
            Jual Mobil Anda dengan <span className="text-brand-500">Harga Terbaik</span>
          </h1>
          <p className="mt-3 max-w-xl text-sm text-neutral-300 sm:text-base">
            Isi data mobil, dapatkan estimasi harga instan, lalu jadwalkan inspeksi. Bisa jual langsung atau tukar tambah dengan mobil di showroom.
          </p>

          <div className="mt-6 inline-flex rounded-2xl border border-white/15 bg-black/60 p-1">
            {[
              { id: 'sell', label: 'Jual Langsung', icon: Sparkles },
              { id: 'trade-in', label: 'Tukar Tambah', icon: ArrowLeftRight },
            ].map(({ id, label, icon: Icon }) => (
              <button
                key={id}
                type="button"
                onClick={() => { setType(id); setErrors({}); }}
                aria-pressed={type === id}
                className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition-colors ${
                  type === id ? 'bg-brand-600 text-white' : 'text-neutral-300 hover:text-white'
                }`}
              >
                <Icon className="h-4 w-4" aria-hidden="true" />
                {label}
              </button>
            ))}
          </div>

          <SocialLinks className="mt-6" />
        </div>
      </section>

      {/* Form */}
      <section className="max-w-7xl mx-auto px-4 py-10 sm:px-6 lg:px-8">
        <div className="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
          <form onSubmit={submit} noValidate className="a18-card p-5 sm:p-6">
            {/* Stepper */}
            <ol className="flex items-center gap-2">
              {STEPS.map(({ no, label, icon: Icon }) => {
                const state = step === no ? 'active' : step > no ? 'done' : 'todo';
                const isDone = state === 'done';
                return (
                  <li key={no} className="flex flex-1 items-center gap-2">
                    <span
                      className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-sm font-black transition-colors duration-200 ${
                        state === 'active'
                          ? 'border-brand-500 bg-brand-600 text-white'
                          : isDone
                            ? 'border-white/40 bg-white/10 text-white'
                            : 'border-white/15 text-neutral-500'
                      }`}
                    >
                      {/* Kedua ikon ditumpuk di sel grid yang sama lalu saling berganti;
                          initial={false}: tidak ada animasi saat form pertama tampil. */}
                      <span className="grid">
                        <motion.span
                          initial={false}
                          animate={isDone ? STEP_ICON_HIDDEN : STEP_ICON_SHOWN}
                          className="col-start-1 row-start-1 flex"
                        >
                          <Icon className="h-4 w-4" />
                        </motion.span>
                        <motion.span
                          initial={false}
                          animate={isDone ? STEP_ICON_SHOWN : STEP_ICON_HIDDEN}
                          className="col-start-1 row-start-1 flex"
                        >
                          <CheckCircle2 className="h-4 w-4" />
                        </motion.span>
                      </span>
                    </span>
                    <span className={`hidden text-xs font-bold uppercase tracking-wide transition-colors duration-200 sm:block ${state === 'todo' ? 'text-neutral-500' : 'text-white'}`}>
                      {label}
                    </span>
                    {no < STEPS.length && (
                      <span className="relative h-px flex-1 bg-white/10" aria-hidden="true">
                        <motion.span
                          initial={false}
                          animate={{ scaleX: step > no ? 1 : 0 }}
                          transition={LINE_TRANSITION}
                          style={{ originX: 0 }}
                          className="absolute inset-0 bg-brand-600"
                        />
                      </span>
                    )}
                  </li>
                );
              })}
            </ol>

            {/* Pembungkus setinggi isi langkah yang terukur (transisi tinggi via CSS).
                overflow-y-clip memotong isi hanya selama tinggi menyesuaikan, tanpa memotong
                geseran horizontal; py-1 (dengan mt-5 & mt-5 di navigasi, total tetap 24px)
                memberi ruang untuk ring fokus input di tepi atas/bawah. */}
            <div
              className="mt-5 overflow-y-clip transition-[height] duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:transition-none"
              style={stepBodyHeight == null ? undefined : { height: stepBodyHeight }}
            >
              <div ref={stepBodyRef} className="py-1">
                <AnimatePresence mode="wait" initial={false} custom={stepDirection}>
                  <motion.div
                    key={step}
                    custom={stepDirection}
                    variants={STEP_VARIANTS}
                    initial="enter"
                    animate="center"
                    exit="exit"
                    className="space-y-4"
                  >
                    {/* STEP 1 */}
                    {step === 1 && (
                      <>
                        <div className="grid gap-3 sm:grid-cols-2">
                          <div>
                            <label htmlFor="sc-brand" className="a18-label">Merek</label>
                            <select id="sc-brand" value={form.brand} onChange={(e) => setField({ brand: e.target.value })} className="a18-input">
                              {CAR_BRANDS.map(brand => <option key={brand} value={brand}>{brand}</option>)}
                            </select>
                          </div>
                          <div>
                            <label htmlFor="sc-model" className="a18-label">Model</label>
                            <input
                              id="sc-model"
                              value={form.model}
                              onChange={(e) => setField({ model: e.target.value })}
                              className="a18-input"
                              placeholder="Avanza, Xpander, Brio..."
                            />
                            {errors.model && <p className="mt-1 text-xs text-brand-400">{errors.model}</p>}
                          </div>
                          <div>
                            <label htmlFor="sc-variant" className="a18-label">Varian (opsional)</label>
                            <input
                              id="sc-variant"
                              value={form.variant}
                              onChange={(e) => setField({ variant: e.target.value })}
                              className="a18-input"
                              placeholder="1.5 G CVT"
                            />
                          </div>
                          <div>
                            <label htmlFor="sc-year" className="a18-label">Tahun</label>
                            <select id="sc-year" value={form.year} onChange={(e) => setField({ year: Number(e.target.value) })} className="a18-input">
                              {YEARS.map(year => <option key={year} value={year}>{year}</option>)}
                            </select>
                          </div>
                          <div>
                            <label htmlFor="sc-body" className="a18-label">Tipe Bodi</label>
                            <select id="sc-body" value={form.bodyType} onChange={(e) => setField({ bodyType: e.target.value })} className="a18-input">
                              {BODY_TYPES.map(type_ => <option key={type_} value={type_}>{type_}</option>)}
                            </select>
                          </div>
                          <div>
                            <label htmlFor="sc-trans" className="a18-label">Transmisi</label>
                            <select id="sc-trans" value={form.transmission} onChange={(e) => setField({ transmission: e.target.value })} className="a18-input">
                              {TRANSMISSIONS.map(t => <option key={t} value={t}>{t}</option>)}
                            </select>
                          </div>
                          <div>
                            <label htmlFor="sc-fuel" className="a18-label">Bahan Bakar</label>
                            <select id="sc-fuel" value={form.fuel} onChange={(e) => setField({ fuel: e.target.value })} className="a18-input">
                              {FUEL_TYPES.map(f => <option key={f} value={f}>{f}</option>)}
                            </select>
                          </div>
                          <div>
                            <label htmlFor="sc-km" className="a18-label">Kilometer</label>
                            <input
                              id="sc-km"
                              type="number"
                              min={0}
                              step={1000}
                              value={form.mileage}
                              onChange={(e) => setField({ mileage: e.target.value })}
                              className="a18-input"
                              placeholder="45000"
                            />
                            {errors.mileage && <p className="mt-1 text-xs text-brand-400">{errors.mileage}</p>}
                          </div>
                          <div>
                            <label htmlFor="sc-color" className="a18-label">Warna</label>
                            <select id="sc-color" value={form.color} onChange={(e) => setField({ color: e.target.value })} className="a18-input">
                              {CAR_COLORS.map(color => <option key={color.name} value={color.name}>{color.name}</option>)}
                            </select>
                          </div>
                          <div>
                            <label htmlFor="sc-location" className="a18-label">Lokasi Mobil</label>
                            <select id="sc-location" value={form.location} onChange={(e) => setField({ location: e.target.value })} className="a18-input">
                              {CAR_LOCATIONS.map(loc => <option key={loc} value={loc}>{loc}</option>)}
                            </select>
                          </div>
                        </div>

                        <fieldset>
                          <legend className="a18-label">Kondisi Mobil</legend>
                          <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            {CAR_CONDITIONS.map(condition => (
                              <label
                                key={condition}
                                className={`cursor-pointer rounded-xl border px-3 py-2.5 text-center text-xs font-bold transition-colors ${
                                  form.condition === condition
                                    ? 'border-brand-500 bg-brand-600 text-white'
                                    : 'border-white/15 text-neutral-300 hover:border-white/40'
                                }`}
                              >
                                <input
                                  type="radio"
                                  name="condition"
                                  className="sr-only"
                                  checked={form.condition === condition}
                                  onChange={() => setField({ condition })}
                                />
                                {condition}
                              </label>
                            ))}
                          </div>
                        </fieldset>
                      </>
                    )}

                    {/* STEP 2 */}
                    {step === 2 && (
                      <>
                        <div className="grid gap-3 sm:grid-cols-2">
                          <div>
                            <label htmlFor="sc-date" className="a18-label">Tanggal Inspeksi</label>
                            <input
                              id="sc-date"
                              type="date"
                              min={todayLocal()}
                              value={form.inspectionDate}
                              onChange={(e) => setField({ inspectionDate: e.target.value })}
                              className="a18-input"
                            />
                            {errors.inspectionDate && <p className="mt-1 text-xs text-brand-400">{errors.inspectionDate}</p>}
                          </div>
                          <div>
                            <label htmlFor="sc-asking" className="a18-label">Harga Diharapkan (opsional)</label>
                            <input
                              id="sc-asking"
                              type="number"
                              min={0}
                              step={1000000}
                              value={form.askingPrice}
                              onChange={(e) => setField({ askingPrice: e.target.value })}
                              className="a18-input"
                              placeholder={String(estimate.mid)}
                            />
                          </div>
                        </div>

                        <fieldset>
                          <legend className="a18-label">Jam Inspeksi</legend>
                          <div className="flex flex-wrap gap-2">
                            {TIME_SLOTS.map(slot => (
                              <button
                                key={slot}
                                type="button"
                                onClick={() => setField({ inspectionTime: slot })}
                                aria-pressed={form.inspectionTime === slot}
                                className={`rounded-xl border px-3 py-1.5 text-xs font-bold transition-colors ${
                                  form.inspectionTime === slot
                                    ? 'border-brand-500 bg-brand-600 text-white'
                                    : 'border-white/15 text-neutral-300 hover:border-white/40 hover:text-white'
                                }`}
                              >
                                {slot}
                              </button>
                            ))}
                          </div>
                        </fieldset>

                        {type === 'trade-in' && (
                          <div className="a18-card p-4">
                            <label htmlFor="sc-target" className="a18-label">Mobil Auto18 yang Diinginkan</label>
                            <select
                              id="sc-target"
                              value={form.tradeInCarId}
                              onChange={(e) => setField({ tradeInCarId: e.target.value })}
                              className="a18-input"
                            >
                              <option value="">— Pilih mobil —</option>
                              {availableCars.map(car => (
                                <option key={car.id} value={car.id}>
                                  {getCarName(car, { withYear: true })} · {formatRupiah(car.price)}
                                </option>
                              ))}
                            </select>
                            {errors.tradeInCarId && <p className="mt-1 text-xs text-brand-400">{errors.tradeInCarId}</p>}

                            {targetCar && (
                              <div className="mt-3 flex items-center gap-3 rounded-xl border border-white/10 bg-ink-900 p-3">
                                <CarImage car={targetCar} className="h-16 w-24 shrink-0 rounded-lg" />
                                <div className="min-w-0">
                                  <p className="truncate text-sm font-bold text-white">{getCarName(targetCar, { withYear: true })}</p>
                                  <p className="text-xs text-neutral-400">{formatRupiah(targetCar.price)}</p>
                                  <p className="mt-1 text-xs text-brand-400">
                                    Perkiraan tambahan bayar: <Money value={priceGap} className="font-bold" />
                                  </p>
                                </div>
                              </div>
                            )}
                          </div>
                        )}
                      </>
                    )}

                    {/* STEP 3 */}
                    {step === 3 && (
                      <>
                        <div className="grid gap-3 sm:grid-cols-2">
                          <div>
                            <label htmlFor="sc-name" className="a18-label">Nama Lengkap</label>
                            <input
                              id="sc-name"
                              value={form.customerName}
                              onChange={(e) => setField({ customerName: e.target.value })}
                              className="a18-input"
                              placeholder="Nama sesuai STNK"
                            />
                            {errors.customerName && <p className="mt-1 text-xs text-brand-400">{errors.customerName}</p>}
                          </div>
                          <div>
                            <label htmlFor="sc-phone" className="a18-label">Nomor HP</label>
                            <input
                              id="sc-phone"
                              type="tel"
                              value={form.phone}
                              onChange={(e) => setField({ phone: e.target.value })}
                              className="a18-input"
                              placeholder="0812-3456-7890"
                            />
                            {errors.phone && <p className="mt-1 text-xs text-brand-400">{errors.phone}</p>}
                          </div>
                          <div className="sm:col-span-2">
                            <label htmlFor="sc-email" className="a18-label">Email (opsional)</label>
                            <input
                              id="sc-email"
                              type="email"
                              value={form.email}
                              onChange={(e) => setField({ email: e.target.value })}
                              className="a18-input"
                              placeholder="nama@email.com"
                            />
                            {errors.email && <p className="mt-1 text-xs text-brand-400">{errors.email}</p>}
                          </div>
                          <div className="sm:col-span-2">
                            <label htmlFor="sc-notes" className="a18-label">Catatan (opsional)</label>
                            <textarea
                              id="sc-notes"
                              rows={3}
                              value={form.notes}
                              onChange={(e) => setField({ notes: e.target.value })}
                              className="a18-input"
                              placeholder="Misal: pajak baru diperpanjang, ada baret di bumper"
                            />
                          </div>
                        </div>

                        {/* Ringkasan */}
                        <div className="rounded-2xl border border-white/10 bg-ink-900 p-4">
                          <h3 className="font-display text-sm font-black uppercase tracking-wide text-white">Ringkasan</h3>
                          <dl className="mt-3 space-y-2 text-sm">
                            <div className="flex justify-between gap-4">
                              <dt className="text-neutral-400">Mobil</dt>
                              <dd className="text-right font-semibold text-white">
                                {form.brand} {form.model} {form.variant} {form.year}
                              </dd>
                            </div>
                            <div className="flex justify-between gap-4">
                              <dt className="text-neutral-400">Kilometer</dt>
                              <dd className="font-semibold text-white">{Number(form.mileage || 0).toLocaleString('id-ID')} km</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                              <dt className="text-neutral-400">Jenis</dt>
                              <dd className="font-semibold text-white">{type === 'trade-in' ? 'Tukar Tambah' : 'Jual Langsung'}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                              <dt className="text-neutral-400">Inspeksi</dt>
                              <dd className="font-semibold text-white">{form.inspectionDate} · {form.inspectionTime}</dd>
                            </div>
                          </dl>
                        </div>
                      </>
                    )}
                  </motion.div>
                </AnimatePresence>
              </div>
            </div>

            {/* Navigasi langkah */}
            <div className="mt-5 flex items-center justify-between gap-3 border-t border-white/10 pt-5">
              <button
                type="button"
                onClick={() => setStep(s => Math.max(1, s - 1))}
                disabled={step === 1}
                className="a18-btn-ghost"
              >
                <ArrowLeft className="h-4 w-4" />
                Kembali
              </button>

              {/* Key berbeda di setiap tombol: tanpa ini React menukar type="button" menjadi
                  type="submit" pada elemen DOM yang sama saat berpindah ke langkah 3, dan klik
                  "Lanjut" yang sedang berlangsung bisa ikut men-submit form. */}
              {step < 3 ? (
                <button key="next" type="button" onClick={goNext} className="a18-btn-primary">
                  Lanjut
                  <ArrowRight className="h-4 w-4" />
                </button>
              ) : (
                <button key="submit" type="submit" className="a18-btn-primary">
                  <CheckCircle2 className="h-4 w-4" />
                  Kirim Permintaan
                </button>
              )}
            </div>
          </form>

          {/* Panel estimasi */}
          <aside className="lg:sticky lg:top-28 lg:self-start">
            <div className="a18-card p-5">
              <p className="text-[10px] font-bold uppercase tracking-wider text-brand-500">Estimasi Harga</p>
              {/* Angka bergulir langsung mengikuti merek/model/tahun/km/transmisi/kondisi */}
              <p className="mt-1 font-display text-2xl font-black leading-tight text-white">
                <Money value={estimate.low} />
                <span className="text-neutral-500"> – </span>
                <Money value={estimate.high} />
              </p>
              <p className="mt-2 text-xs text-neutral-400">
                {form.brand} {form.model || '(model belum diisi)'} {form.year} · {Number(form.mileage || 0).toLocaleString('id-ID')} km · {form.condition}
              </p>

              <p className="mt-4 flex items-start gap-1.5 rounded-xl border border-white/10 bg-ink-900 p-3 text-xs leading-relaxed text-neutral-400">
                <Info className="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand-500" aria-hidden="true" />
                Angka ini perkiraan otomatis dari merek, tipe, tahun, kilometer, dan kondisi. Harga final ditentukan setelah inspeksi langsung dan tidak mengikat.
              </p>

              {type === 'trade-in' && targetCar && (
                <div className="mt-4 border-t border-white/10 pt-4">
                  <p className="a18-label">Perkiraan Tambahan Bayar</p>
                  <p className="font-display text-xl font-black text-brand-500"><Money value={priceGap} /></p>
                  <p className="mt-1 text-xs text-neutral-500">
                    {getCarName(targetCar, { withYear: true })} dikurangi estimasi mobil Anda.
                  </p>
                </div>
              )}
            </div>
          </aside>
        </div>
      </section>

      <HowItWorksSection
        title="Cara Jual Mobil di Auto18"
        subtitle="Dari isi data sampai dana cair, prosesnya transparan."
        steps={HOW_IT_WORKS_SELL}
      />
      <FaqSection title="Pertanyaan Seputar Jual Mobil" faqs={FAQS_SELL} />
    </div>
  );
}
