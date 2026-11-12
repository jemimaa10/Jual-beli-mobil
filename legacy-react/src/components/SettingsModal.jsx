import React, { useEffect, useState } from 'react';
import { X, Settings, Store, Share2, Receipt, Calculator, Save, RotateCcw } from 'lucide-react';
import { useApp } from '../context/AppContext';
import { INITIAL_STORE_INFO } from '../data/initialData';
import { calcInstallment } from '../utils/carUtils';
import { formatRupiah } from '../utils/formatters';
import { toast } from '../utils/toast';

// Contoh harga untuk pratinjau simulasi kredit
const PREVIEW_PRICE = 200_000_000;
const PREVIEW_TENOR = 36;

// Ubah input angka menjadi number dalam rentang; pakai fallback bila kosong / tidak valid
const toNumberInRange = (value, fallback, min, max) => {
  const n = Number(value);
  if (value === '' || value === null || value === undefined || !Number.isFinite(n)) return fallback;
  return Math.min(max, Math.max(min, n));
};

const clean = (value) => String(value ?? '').trim();

export default function SettingsModal() {
  const { isSettingsModalOpen, storeInfo } = useApp();

  if (!isSettingsModalOpen) return null;

  // key: form dibuat ulang dari data terbaru setiap modal dibuka
  // atau bila data toko berubah dari luar (mis. Reset Data Demo)
  return <SettingsPanel key={JSON.stringify(storeInfo)} />;
}

function SettingsPanel() {
  const { setIsSettingsModalOpen, storeInfo, setStoreInfo, resetToDefault } = useApp();
  const [form, setForm] = useState(() => ({ ...storeInfo }));

  const close = () => setIsSettingsModalOpen(false);

  // Tutup dengan tombol Escape
  useEffect(() => {
    const onKeyDown = (e) => {
      if (e.key === 'Escape') setIsSettingsModalOpen(false);
    };
    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, [setIsSettingsModalOpen]);

  const setField = (key) => (e) => {
    const value = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
    setForm(prev => ({ ...prev, [key]: value }));
  };

  const handleSave = (e) => {
    e.preventDefault();
    setStoreInfo({
      ...form,
      name: clean(form.name) || INITIAL_STORE_INFO.name,
      tagline: clean(form.tagline),
      address: clean(form.address),
      phone: clean(form.phone),
      email: clean(form.email),
      callCenter: clean(form.callCenter),
      whatsapp: clean(form.whatsapp).replace(/\D/g, ''),
      instagram: clean(form.instagram).replace(/^@+/, ''),
      tiktok: clean(form.tiktok).replace(/^@+/, ''),
      youtube: clean(form.youtube),
      supportPartner: clean(form.supportPartner),
      cashierName: clean(form.cashierName) || INITIAL_STORE_INFO.cashierName,
      receiptFooter: String(form.receiptFooter ?? '').trim(),
      taxEnabled: Boolean(form.taxEnabled),
      // Field angka disimpan sebagai number
      taxRate: toNumberInRange(form.taxRate, storeInfo.taxRate, 0, 100),
      creditRate: toNumberInRange(form.creditRate, storeInfo.creditRate, 0, 100),
      minDpPercent: toNumberInRange(form.minDpPercent, storeInfo.minDpPercent, 0, 90),
    });
    close();
    toast.success('Pengaturan showroom berhasil diperbarui!');
  };

  // Pratinjau cicilan dengan bunga & DP yang sedang diisi
  const previewDp = toNumberInRange(form.minDpPercent, 0, 0, 90);
  const previewRate = toNumberInRange(form.creditRate, 0, 0, 100);
  const preview = calcInstallment({
    price: PREVIEW_PRICE,
    dpPercent: previewDp,
    tenorMonths: PREVIEW_TENOR,
    annualRate: previewRate,
  });

  return (
    <div
      className="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
      onMouseDown={(e) => {
        if (e.target === e.currentTarget) close();
      }}
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="settings-modal-title"
        className="bg-ink-800 border border-white/10 rounded-3xl w-full max-w-2xl max-h-[92vh] overflow-y-auto shadow-2xl shadow-black"
      >
        {/* Header */}
        <div className="a18-stripes-soft sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-white/10 px-5 py-4 sm:px-6">
          <div className="flex min-w-0 items-center gap-3">
            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white shadow-lg shadow-brand-600/30">
              <Settings className="h-5 w-5" />
            </span>
            <div className="min-w-0">
              <h2 id="settings-modal-title" className="a18-heading truncate text-lg sm:text-xl">
                Pengaturan Showroom
              </h2>
              <p className="truncate text-xs text-neutral-400">Profil, kontak, kasir & simulasi kredit</p>
            </div>
          </div>
          <button
            type="button"
            onClick={close}
            aria-label="Tutup pengaturan"
            title="Tutup"
            className="shrink-0 rounded-xl bg-black/60 p-2 text-neutral-300 transition-colors hover:bg-black hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        <form onSubmit={handleSave}>
          <div className="space-y-5 p-5 sm:p-6">
            {/* Profil Showroom */}
            <Section icon={Store} title="Profil Showroom" description="Nama &amp; slogan dipakai di kepala struk dan sebagai atas nama transfer; logo Auto18 di navbar &amp; footer tetap.">
              <Field label="Nama Showroom" htmlFor="settings-name">
                <input
                  id="settings-name"
                  type="text"
                  required
                  value={form.name ?? ''}
                  onChange={setField('name')}
                  className="a18-input"
                />
              </Field>
              <Field label="Slogan / Tagline" htmlFor="settings-tagline">
                <input
                  id="settings-tagline"
                  type="text"
                  value={form.tagline ?? ''}
                  onChange={setField('tagline')}
                  className="a18-input"
                />
              </Field>
              <Field label="Alamat Showroom" htmlFor="settings-address" className="sm:col-span-2">
                <textarea
                  id="settings-address"
                  rows={2}
                  value={form.address ?? ''}
                  onChange={setField('address')}
                  className="a18-input resize-y"
                />
              </Field>
              <Field label="Nomor Telepon" htmlFor="settings-phone">
                <input
                  id="settings-phone"
                  type="tel"
                  value={form.phone ?? ''}
                  onChange={setField('phone')}
                  className="a18-input"
                />
              </Field>
              <Field label="Email" htmlFor="settings-email">
                <input
                  id="settings-email"
                  type="email"
                  value={form.email ?? ''}
                  onChange={setField('email')}
                  placeholder="halo@auto18.id"
                  className="a18-input"
                />
              </Field>
            </Section>

            {/* Kontak & Sosial Media */}
            <Section icon={Share2} title="Kontak & Sosial Media" description="Nomor darurat dan akun sosial media Auto18.">
              <Field label="Call Center 24 Jam" htmlFor="settings-callCenter">
                <input
                  id="settings-callCenter"
                  type="tel"
                  value={form.callCenter ?? ''}
                  onChange={setField('callCenter')}
                  className="a18-input"
                />
              </Field>
              <Field
                label="WhatsApp"
                htmlFor="settings-whatsapp"
                hint="Format 628xxx, kosongkan jika tidak ada"
              >
                <input
                  id="settings-whatsapp"
                  type="tel"
                  inputMode="numeric"
                  value={form.whatsapp ?? ''}
                  onChange={setField('whatsapp')}
                  placeholder="6281234567890"
                  aria-describedby="settings-whatsapp-hint"
                  className="a18-input"
                />
              </Field>
              <Field label="Instagram" htmlFor="settings-instagram" hint="Username tanpa @">
                <PrefixInput prefix="@">
                  <input
                    id="settings-instagram"
                    type="text"
                    value={form.instagram ?? ''}
                    onChange={setField('instagram')}
                    aria-describedby="settings-instagram-hint"
                    className="a18-input pl-8"
                  />
                </PrefixInput>
              </Field>
              <Field label="TikTok" htmlFor="settings-tiktok" hint="Username tanpa @">
                <PrefixInput prefix="@">
                  <input
                    id="settings-tiktok"
                    type="text"
                    value={form.tiktok ?? ''}
                    onChange={setField('tiktok')}
                    aria-describedby="settings-tiktok-hint"
                    className="a18-input pl-8"
                  />
                </PrefixInput>
              </Field>
              <Field label="YouTube" htmlFor="settings-youtube" hint="Nama channel">
                <input
                  id="settings-youtube"
                  type="text"
                  value={form.youtube ?? ''}
                  onChange={setField('youtube')}
                  aria-describedby="settings-youtube-hint"
                  className="a18-input"
                />
              </Field>
              <Field label="Didukung Oleh (Partner)" htmlFor="settings-supportPartner" hint='Tampil sebagai "Supported by ..."'>
                <input
                  id="settings-supportPartner"
                  type="text"
                  value={form.supportPartner ?? ''}
                  onChange={setField('supportPartner')}
                  aria-describedby="settings-supportPartner-hint"
                  className="a18-input"
                />
              </Field>
            </Section>

            {/* Kasir & Struk */}
            <Section icon={Receipt} title="Kasir & Struk" description="Dipakai di kasir aksesoris dan struk penjualan.">
              <Field label="Nama Kasir Default" htmlFor="settings-cashierName" className="sm:col-span-2">
                <input
                  id="settings-cashierName"
                  type="text"
                  value={form.cashierName ?? ''}
                  onChange={setField('cashierName')}
                  className="a18-input"
                />
              </Field>

              <div className="flex flex-col gap-4 rounded-2xl border border-white/10 bg-ink-800 p-4 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
                <label htmlFor="settings-taxEnabled" className="flex flex-1 cursor-pointer items-center gap-3">
                  <span className="relative inline-flex h-6 w-11 shrink-0">
                    <input
                      id="settings-taxEnabled"
                      type="checkbox"
                      role="switch"
                      checked={Boolean(form.taxEnabled)}
                      onChange={setField('taxEnabled')}
                      className="peer sr-only"
                    />
                    <span className="absolute inset-0 rounded-full bg-neutral-700 transition-colors peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-ink-800" />
                    <span className="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5" />
                  </span>
                  <span className="min-w-0">
                    <span className="block text-sm font-bold text-white">Pajak PPN Kasir</span>
                    <span className="block text-xs text-neutral-400">Terapkan PPN pada transaksi kasir aksesoris</span>
                  </span>
                </label>

                <div className="sm:w-36">
                  <label htmlFor="settings-taxRate" className="a18-label">Tarif PPN</label>
                  <SuffixInput suffix="%">
                    <input
                      id="settings-taxRate"
                      type="number"
                      inputMode="decimal"
                      min="0"
                      max="100"
                      step="0.1"
                      required={Boolean(form.taxEnabled)}
                      disabled={!form.taxEnabled}
                      value={form.taxRate ?? ''}
                      onChange={setField('taxRate')}
                      className="a18-input pr-9 disabled:cursor-not-allowed disabled:opacity-50"
                    />
                  </SuffixInput>
                </div>
              </div>

              <Field label="Pesan Kaki Struk" htmlFor="settings-receiptFooter" className="sm:col-span-2">
                <textarea
                  id="settings-receiptFooter"
                  rows={3}
                  value={form.receiptFooter ?? ''}
                  onChange={setField('receiptFooter')}
                  className="a18-input resize-y font-mono text-xs"
                />
              </Field>
            </Section>

            {/* Simulasi Kredit */}
            <Section icon={Calculator} title="Simulasi Kredit" description="Nilai awal kalkulator cicilan di halaman mobil.">
              <Field label="Bunga Flat" htmlFor="settings-creditRate" hint="Persen flat per tahun">
                <SuffixInput suffix="% / tahun">
                  <input
                    id="settings-creditRate"
                    type="number"
                    inputMode="decimal"
                    min="0"
                    max="100"
                    step="0.1"
                    required
                    value={form.creditRate ?? ''}
                    onChange={setField('creditRate')}
                    aria-describedby="settings-creditRate-hint"
                    className="a18-input pr-24"
                  />
                </SuffixInput>
              </Field>
              <Field label="DP Minimal" htmlFor="settings-minDpPercent" hint="Persen dari harga mobil">
                <SuffixInput suffix="%">
                  <input
                    id="settings-minDpPercent"
                    type="number"
                    inputMode="decimal"
                    min="0"
                    max="90"
                    step="1"
                    required
                    value={form.minDpPercent ?? ''}
                    onChange={setField('minDpPercent')}
                    aria-describedby="settings-minDpPercent-hint"
                    className="a18-input pr-9"
                  />
                </SuffixInput>
              </Field>

              <div className="rounded-2xl border border-brand-600/30 bg-brand-600/10 p-4 text-xs leading-relaxed text-neutral-300 sm:col-span-2">
                <span className="font-bold uppercase tracking-wider text-brand-300">Contoh: </span>
                mobil {formatRupiah(PREVIEW_PRICE)}, DP {previewDp}% ({formatRupiah(preview.dpAmount)}), tenor {PREVIEW_TENOR} bulan
                {' '}&rarr; cicilan{' '}
                <span className="font-display text-sm font-black text-white">{formatRupiah(preview.monthly)}</span>/bulan
              </div>
            </Section>
          </div>

          {/* Aksi */}
          <div className="sticky bottom-0 z-10 flex flex-col-reverse gap-3 border-t border-white/10 bg-ink-800/95 px-5 py-4 backdrop-blur sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <button
              type="button"
              onClick={resetToDefault}
              className="a18-btn-ghost text-brand-400 hover:bg-brand-600/10 hover:text-brand-300"
            >
              <RotateCcw className="h-4 w-4" />
              <span>Reset Data Demo</span>
            </button>

            <div className="grid grid-cols-2 gap-2 sm:flex">
              <button type="button" onClick={close} className="a18-btn-outline">
                Batal
              </button>
              <button type="submit" className="a18-btn-primary">
                <Save className="h-4 w-4" />
                <span>Simpan</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
}

// Kartu grup field
function Section({ icon: Icon, title, description, children }) {
  return (
    <section className="rounded-2xl border border-white/10 bg-ink-900/60 p-4 sm:p-5">
      <div className="mb-4 flex items-start gap-3">
        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-600/15 text-brand-500 ring-1 ring-brand-600/30">
          <Icon className="h-4 w-4" />
        </span>
        <div className="min-w-0">
          <h3 className="font-display text-sm font-black uppercase tracking-wide text-white">{title}</h3>
          {description && <p className="text-xs text-neutral-400">{description}</p>}
        </div>
      </div>
      <div className="grid gap-4 sm:grid-cols-2">{children}</div>
    </section>
  );
}

function Field({ label, htmlFor, hint, className = '', children }) {
  return (
    <div className={`min-w-0 ${className}`}>
      <label htmlFor={htmlFor} className="a18-label">{label}</label>
      {children}
      {hint && (
        <p id={`${htmlFor}-hint`} className="mt-1 text-[11px] text-neutral-500">{hint}</p>
      )}
    </div>
  );
}

function PrefixInput({ prefix, children }) {
  return (
    <div className="relative">
      <span className="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-sm font-bold text-neutral-500">
        {prefix}
      </span>
      {children}
    </div>
  );
}

function SuffixInput({ suffix, children }) {
  return (
    <div className="relative">
      {children}
      <span className="pointer-events-none absolute inset-y-0 right-3.5 flex items-center text-xs font-bold text-neutral-500">
        {suffix}
      </span>
    </div>
  );
}
