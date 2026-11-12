import React, { useEffect, useMemo, useRef, useState } from 'react';
import { X, Save, AlertTriangle } from 'lucide-react';
import { useApp } from '../context/AppContext';
import CarImage from './CarImage';
import { CAR_IMAGES } from '../data/carImages';
import {
  BODY_TYPES, CAR_BRANDS, CAR_COLORS, CAR_LOCATIONS, FUEL_TYPES, INSPECTION_CATEGORIES, TRANSMISSIONS
} from '../data/initialData';
import { formatRupiah } from '../utils/formatters';
import { lockScroll, unlockScroll } from '../utils/scrollLock';

const currentYear = new Date().getFullYear();

const blankCar = () => ({
  brand: 'Toyota',
  model: '',
  variant: '',
  year: currentYear - 3,
  bodyType: 'MPV',
  transmission: 'Otomatis',
  fuel: 'Bensin',
  engineCc: 1500,
  seats: 7,
  mileage: 0,
  color: 'Putih',
  colorHex: '#f4f4f5',
  plate: '',
  location: 'Jakarta Barat',
  price: 0,
  buyPrice: 0,
  status: 'available',
  ownerCount: 1,
  taxValidUntil: '',
  serviceRecord: true,
  isFeatured: false,
  imageKey: '',
  imageUrl: '',
  features: '',
  description: '',
  inspection: Object.fromEntries(INSPECTION_CATEGORIES.map(c => [c.key, 92])),
});

export default function CarFormModal({ car = null, onClose }) {
  const { addCar, updateCar } = useApp();
  const isEdit = Boolean(car);

  const [form, setForm] = useState(() => {
    if (!car) return blankCar();
    return {
      ...blankCar(),
      ...car,
      features: Array.isArray(car.features) ? car.features.join(', ') : (car.features || ''),
      inspection: { ...blankCar().inspection, ...(car.inspection || {}) },
    };
  });
  const [errors, setErrors] = useState({});

  const setField = (patch) => setForm(prev => ({ ...prev, ...patch }));

  // onClose berubah identitas tiap render induk; simpan di ref supaya effect ini hanya
  // memasang/melepas listener dan kunci scroll sekali per kemunculan modal, bukan tiap render.
  const closeRef = useRef(onClose);
  closeRef.current = onClose;

  useEffect(() => {
    const onKeyDown = (e) => { if (e.key === 'Escape') closeRef.current(); };
    window.addEventListener('keydown', onKeyDown);
    lockScroll();
    return () => {
      window.removeEventListener('keydown', onKeyDown);
      unlockScroll();
    };
  }, []);

  const margin = (Number(form.price) || 0) - (Number(form.buyPrice) || 0);
  const marginPercent = Number(form.buyPrice) > 0 ? (margin / Number(form.buyPrice)) * 100 : 0;

  const previewCar = useMemo(() => ({
    ...form,
    features: [],
  }), [form]);

  const imageKeys = useMemo(() => Object.keys(CAR_IMAGES), []);

  const submit = (e) => {
    e.preventDefault();
    const next = {};
    if (!form.model.trim()) next.model = 'Model wajib diisi';
    if (!form.year || Number(form.year) < 1980 || Number(form.year) > currentYear + 1) next.year = 'Tahun tidak valid';
    if (!(Number(form.price) > 0)) next.price = 'Harga jual wajib diisi';
    if (Number(form.buyPrice) < 0) next.buyPrice = 'Modal tidak valid';
    setErrors(next);
    if (Object.keys(next).length > 0) return;

    const payload = {
      ...form,
      model: form.model.trim(),
      variant: form.variant.trim(),
      plate: form.plate.trim(),
      description: form.description.trim(),
      features: String(form.features || '').split(',').map(f => f.trim()).filter(Boolean),
    };

    if (isEdit) updateCar(car.id, payload);
    else addCar(payload);
    onClose();
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/80 p-4 backdrop-blur-sm"
      onMouseDown={(e) => { if (e.target === e.currentTarget) onClose(); }}
    >
      <form
        onSubmit={submit}
        noValidate
        role="dialog"
        aria-modal="true"
        aria-label={isEdit ? 'Edit mobil' : 'Tambah mobil'}
        className="my-4 w-full max-w-4xl overflow-hidden rounded-3xl border border-white/10 bg-ink-800"
      >
        <div className="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-white/10 bg-ink-800/95 px-5 py-3.5 backdrop-blur">
          <h2 className="font-display text-base font-black uppercase tracking-wide text-white">
            {isEdit ? `Edit ${car.brand} ${car.model}` : 'Tambah Mobil ke Stok'}
          </h2>
          <button type="button" onClick={onClose} aria-label="Tutup" className="a18-btn-ghost px-2">
            <X className="h-5 w-5" />
          </button>
        </div>

        <div className="space-y-6 p-5">
          {/* Info dasar */}
          <section>
            <h3 className="a18-label">Info Dasar</h3>
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <label htmlFor="cf-brand" className="a18-label">Merek</label>
                <select id="cf-brand" value={form.brand} onChange={(e) => setField({ brand: e.target.value })} className="a18-input">
                  {CAR_BRANDS.map(brand => <option key={brand} value={brand}>{brand}</option>)}
                </select>
              </div>
              <div>
                <label htmlFor="cf-model" className="a18-label">Model</label>
                <input id="cf-model" value={form.model} onChange={(e) => setField({ model: e.target.value })} className="a18-input" placeholder="Xpander" />
                {errors.model && <p className="mt-1 text-xs text-brand-400">{errors.model}</p>}
              </div>
              <div>
                <label htmlFor="cf-variant" className="a18-label">Varian</label>
                <input id="cf-variant" value={form.variant} onChange={(e) => setField({ variant: e.target.value })} className="a18-input" placeholder="Ultimate 1.5 AT" />
              </div>
              <div>
                <label htmlFor="cf-year" className="a18-label">Tahun</label>
                <input id="cf-year" type="number" min={1980} max={currentYear + 1} value={form.year} onChange={(e) => setField({ year: e.target.value })} className="a18-input" />
                {errors.year && <p className="mt-1 text-xs text-brand-400">{errors.year}</p>}
              </div>
              <div>
                <label htmlFor="cf-body" className="a18-label">Tipe Bodi</label>
                <select id="cf-body" value={form.bodyType} onChange={(e) => setField({ bodyType: e.target.value })} className="a18-input">
                  {BODY_TYPES.map(type => <option key={type} value={type}>{type}</option>)}
                </select>
              </div>
              <div>
                <label htmlFor="cf-trans" className="a18-label">Transmisi</label>
                <select id="cf-trans" value={form.transmission} onChange={(e) => setField({ transmission: e.target.value })} className="a18-input">
                  {TRANSMISSIONS.map(t => <option key={t} value={t}>{t}</option>)}
                </select>
              </div>
              <div>
                <label htmlFor="cf-fuel" className="a18-label">Bahan Bakar</label>
                <select id="cf-fuel" value={form.fuel} onChange={(e) => setField({ fuel: e.target.value })} className="a18-input">
                  {FUEL_TYPES.map(f => <option key={f} value={f}>{f}</option>)}
                </select>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label htmlFor="cf-cc" className="a18-label">Mesin (cc)</label>
                  <input id="cf-cc" type="number" min={0} value={form.engineCc} onChange={(e) => setField({ engineCc: e.target.value })} className="a18-input" />
                </div>
                <div>
                  <label htmlFor="cf-seats" className="a18-label">Kursi</label>
                  <input id="cf-seats" type="number" min={2} max={20} value={form.seats} onChange={(e) => setField({ seats: e.target.value })} className="a18-input" />
                </div>
              </div>
            </div>
          </section>

          {/* Kondisi & dokumen */}
          <section>
            <h3 className="a18-label">Kondisi & Dokumen</h3>
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <label htmlFor="cf-km" className="a18-label">Kilometer</label>
                <input id="cf-km" type="number" min={0} step={1000} value={form.mileage} onChange={(e) => setField({ mileage: e.target.value })} className="a18-input" />
              </div>
              <div>
                <label htmlFor="cf-color" className="a18-label">Warna</label>
                <select
                  id="cf-color"
                  value={form.color}
                  onChange={(e) => {
                    const picked = CAR_COLORS.find(c => c.name === e.target.value);
                    setField({ color: e.target.value, colorHex: picked?.hex || '' });
                  }}
                  className="a18-input"
                >
                  {CAR_COLORS.map(color => <option key={color.name} value={color.name}>{color.name}</option>)}
                </select>
              </div>
              <div>
                <label htmlFor="cf-plate" className="a18-label">Plat Nomor</label>
                <input id="cf-plate" value={form.plate} onChange={(e) => setField({ plate: e.target.value })} className="a18-input" placeholder="B 1821 XPD" />
              </div>
              <div>
                <label htmlFor="cf-location" className="a18-label">Lokasi</label>
                <select id="cf-location" value={form.location} onChange={(e) => setField({ location: e.target.value })} className="a18-input">
                  {CAR_LOCATIONS.map(loc => <option key={loc} value={loc}>{loc}</option>)}
                </select>
              </div>
              <div>
                <label htmlFor="cf-owner" className="a18-label">Kepemilikan (tangan ke-)</label>
                <input id="cf-owner" type="number" min={1} max={10} value={form.ownerCount} onChange={(e) => setField({ ownerCount: e.target.value })} className="a18-input" />
              </div>
              <div>
                <label htmlFor="cf-tax" className="a18-label">Pajak Berlaku s/d</label>
                <input id="cf-tax" type="month" value={form.taxValidUntil || ''} onChange={(e) => setField({ taxValidUntil: e.target.value })} className="a18-input" />
              </div>
              <label className="flex items-center gap-2 self-end pb-2.5 text-sm text-neutral-300">
                <input
                  type="checkbox"
                  checked={Boolean(form.serviceRecord)}
                  onChange={(e) => setField({ serviceRecord: e.target.checked })}
                  className="h-4 w-4 accent-brand-600"
                />
                Riwayat servis lengkap
              </label>
            </div>
          </section>

          {/* Harga */}
          <section>
            <h3 className="a18-label">Harga</h3>
            <div className="grid gap-3 sm:grid-cols-3">
              <div>
                <label htmlFor="cf-buy" className="a18-label">Modal / Harga Beli</label>
                <input id="cf-buy" type="number" min={0} step={500000} value={form.buyPrice} onChange={(e) => setField({ buyPrice: e.target.value })} className="a18-input" />
                {errors.buyPrice && <p className="mt-1 text-xs text-brand-400">{errors.buyPrice}</p>}
              </div>
              <div>
                <label htmlFor="cf-price" className="a18-label">Harga Jual</label>
                <input id="cf-price" type="number" min={0} step={500000} value={form.price} onChange={(e) => setField({ price: e.target.value })} className="a18-input" />
                {errors.price && <p className="mt-1 text-xs text-brand-400">{errors.price}</p>}
              </div>
              <div className="rounded-xl border border-white/10 bg-ink-900 p-3">
                <p className="a18-label mb-1">Margin</p>
                <p className={`font-display text-lg font-black ${margin >= 0 ? 'text-emerald-400' : 'text-brand-400'}`}>
                  {formatRupiah(margin)}
                </p>
                <p className="text-xs text-neutral-500">{marginPercent.toFixed(1)}% dari modal</p>
              </div>
            </div>
            {margin < 0 && (
              <p className="mt-2 flex items-center gap-1.5 text-xs text-amber-400">
                <AlertTriangle className="h-3.5 w-3.5" aria-hidden="true" />
                Harga jual lebih rendah dari modal.
              </p>
            )}
          </section>

          {/* Tampilan */}
          <section>
            <h3 className="a18-label">Tampilan & Status</h3>
            <div className="grid gap-4 lg:grid-cols-[1fr_260px]">
              <div className="grid gap-3 sm:grid-cols-2">
                {(!isEdit || form.status !== 'sold') && (
                  <div>
                    <label htmlFor="cf-status" className="a18-label">Status</label>
                    <select id="cf-status" value={form.status} onChange={(e) => setField({ status: e.target.value })} className="a18-input">
                      <option value="available">Tersedia</option>
                      <option value="booked">Dipesan</option>
                    </select>
                  </div>
                )}
                <div>
                  <label htmlFor="cf-imagekey" className="a18-label">Foto Bawaan</label>
                  <select id="cf-imagekey" value={form.imageKey || ''} onChange={(e) => setField({ imageKey: e.target.value })} className="a18-input">
                    <option value="">Tanpa foto (ilustrasi)</option>
                    {imageKeys.map(key => <option key={key} value={key}>{key}</option>)}
                  </select>
                </div>
                <div className="sm:col-span-2">
                  <label htmlFor="cf-imageurl" className="a18-label">URL Foto (opsional)</label>
                  <input id="cf-imageurl" type="url" value={form.imageUrl} onChange={(e) => setField({ imageUrl: e.target.value })} className="a18-input" placeholder="https://..." />
                </div>
                <label className="flex items-center gap-2 text-sm text-neutral-300">
                  <input
                    type="checkbox"
                    checked={Boolean(form.isFeatured)}
                    onChange={(e) => setField({ isFeatured: e.target.checked })}
                    className="h-4 w-4 accent-brand-600"
                  />
                  Tampilkan sebagai Pilihan Auto18
                </label>
              </div>

              <div>
                <p className="a18-label">Pratinjau</p>
                <CarImage car={previewCar} className="aspect-[16/10] w-full rounded-xl" />
              </div>
            </div>
          </section>

          {/* Fitur & deskripsi */}
          <section className="grid gap-3">
            <div>
              <label htmlFor="cf-features" className="a18-label">Fitur (pisahkan dengan koma)</label>
              <input
                id="cf-features"
                value={form.features}
                onChange={(e) => setField({ features: e.target.value })}
                className="a18-input"
                placeholder="Kamera Mundur, Keyless Entry, Cruise Control"
              />
              <div className="mt-2 flex flex-wrap gap-1.5">
                {String(form.features || '').split(',').map(f => f.trim()).filter(Boolean).map(feature => (
                  <span key={feature} className="rounded-full border border-white/15 bg-ink-900 px-2.5 py-1 text-xs text-neutral-300">
                    {feature}
                  </span>
                ))}
              </div>
            </div>
            <div>
              <label htmlFor="cf-desc" className="a18-label">Catatan Showroom</label>
              <textarea
                id="cf-desc"
                rows={3}
                value={form.description}
                onChange={(e) => setField({ description: e.target.value })}
                className="a18-input"
                placeholder="Kondisi mobil, riwayat servis, kelebihan unit"
              />
            </div>
          </section>

          {/* Inspeksi */}
          <section>
            <h3 className="a18-label">Nilai Inspeksi</h3>
            <div className="grid gap-3 sm:grid-cols-2">
              {INSPECTION_CATEGORIES.map(category => (
                <div key={category.key}>
                  <div className="flex items-center justify-between text-xs">
                    <label htmlFor={`cf-insp-${category.key}`} className="text-neutral-300">{category.label}</label>
                    <span className="font-bold text-white">{form.inspection[category.key] ?? 0}</span>
                  </div>
                  <input
                    id={`cf-insp-${category.key}`}
                    type="range"
                    min={0}
                    max={100}
                    step={1}
                    value={form.inspection[category.key] ?? 0}
                    onChange={(e) => setField({ inspection: { ...form.inspection, [category.key]: Number(e.target.value) } })}
                    className="w-full accent-brand-600"
                  />
                </div>
              ))}
            </div>
            <p className="mt-2 text-xs text-neutral-500">
              Rata-rata:{' '}
              <span className="font-bold text-white">
                {Math.round(
                  INSPECTION_CATEGORIES.reduce((sum, c) => sum + (Number(form.inspection[c.key]) || 0), 0) / INSPECTION_CATEGORIES.length
                )}
              </span>
              /100
            </p>
          </section>
        </div>

        <div className="sticky bottom-0 flex items-center justify-end gap-2 border-t border-white/10 bg-ink-800/95 px-5 py-4 backdrop-blur">
          <button type="button" onClick={onClose} className="a18-btn-ghost">Batal</button>
          <button type="submit" className="a18-btn-primary">
            <Save className="h-4 w-4" />
            {isEdit ? 'Simpan Perubahan' : 'Tambah Mobil'}
          </button>
        </div>
      </form>
    </div>
  );
}
