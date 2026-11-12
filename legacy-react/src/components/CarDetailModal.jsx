import React, { useEffect, useId, useMemo, useRef, useState } from 'react';
import { AnimatePresence, motion, useAnimationControls, useIsPresent } from 'motion/react';
import {
  X, Heart, Calendar, Gauge, Settings2, Fuel, Cog, Users, Palette, MapPin, FileText, Wrench,
  BadgeCheck, MessageCircle, Phone, CalendarCheck, CheckCircle2, ShieldCheck, Receipt
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import CarImage from './CarImage';
import CreditSimulator from './CreditSimulator';
import InspectionDiagram, { getLowestCategoryKey } from './InspectionDiagram';
import SoldStamp, { isFreshSale } from './SoldStamp';
import { AUTO18_BENEFITS, INSPECTION_CATEGORIES } from '../data/initialData';
import { CAR_IMAGE_CREDITS } from '../data/carImages';
import { CAR_STATUS, calcInstallment, formatKm, formatRupiahShort, getCarName, isValidPhone } from '../utils/carUtils';
import { formatDate, formatRupiah } from '../utils/formatters';
import { lockScroll, unlockScroll } from '../utils/scrollLock';

const TIME_SLOTS = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];

// 'YYYY-MM-DD' hari ini menurut waktu lokal
const todayLocal = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

// 'YYYY-MM' → 'Mar 2027'
const formatMonth = (value) => {
  if (!value) return '-';
  const [year, month] = String(value).split('-');
  if (!year || !month) return value;
  const date = new Date(Number(year), Number(month) - 1, 1);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('id-ID', { month: 'short', year: 'numeric' }).format(date);
};

const scoreColor = (score) => (score >= 90 ? 'bg-white' : score >= 80 ? 'bg-amber-400' : 'bg-brand-600');

const emptyBooking = {
  customerName: '',
  phone: '',
  date: '',
  time: '10:00',
  location: 'showroom',
  address: '',
  notes: '',
};

// Foto utama berbagi layoutId dengan foto CarCard di grid, jadi foto "terbang" dari
// kartu ke modal dan kembali lagi saat ditutup.
const PHOTO_TRANSITION = { type: 'spring', bounce: 0.15, duration: 0.4 };
const BACKDROP_TRANSITION = { duration: 0.2, ease: 'easeOut' };

// Panel dibagi tiga lapis supaya tidak ada "double exposure" (teks panel tembus
// pandang di atas grid) saat membuka/menutup:
// - panel: hanya bergeser sedikit (tanpa opacity, agar foto yang terbang tetap penuh)
// - permukaan gelap: memudar sendiri
// - isi (teks, kartu, badge): baru muncul setelah permukaan pekat, dan hilang duluan
const PANEL_VARIANTS = {
  hidden: { y: 12 },
  shown: { y: 0, transition: { type: 'spring', bounce: 0.15, duration: 0.35 } },
  gone: { y: 8, transition: { duration: 0.18, ease: 'easeIn' } },
};
const SURFACE_VARIANTS = {
  hidden: { opacity: 0 },
  shown: { opacity: 1, transition: { duration: 0.12, ease: 'easeOut' } },
  // baru memudar setelah isi (0,08 dtk) benar-benar hilang
  gone: { opacity: 0, transition: { duration: 0.14, delay: 0.09, ease: 'easeIn' } },
};
const CONTENT_VARIANTS = {
  hidden: { opacity: 0 },
  shown: { opacity: 1, transition: { delay: 0.12, duration: 0.2, ease: 'easeOut' } },
  gone: { opacity: 0, transition: { duration: 0.08, ease: 'easeIn' } },
};

// Kontrak "pop" favorit — harus identik dengan hati di CarCard: saat jadi favorit ikon
// membesar 1 → 1,3 → 1 sambil terisi merah; saat dilepas mengecil sedikit 1 → 0,85 → 1.
// ±300 ms. Pegas Motion hanya mendukung 2 keyframe, jadi dipakai keyframe dengan ease-out
// (nilai persis sama dengan CarCard). Keyframe pertama null = mulai dari skala saat ini,
// supaya klik beruntun tidak meloncat.
const HEART_TRANSITION = { duration: 0.3, times: [0, 0.4, 1], ease: 'easeOut' };
const HEART_POP = { scale: [null, 1.3, 1], transition: HEART_TRANSITION };
const HEART_DIP = { scale: [null, 0.85, 1], transition: HEART_TRANSITION };

// Pil pilihan kategori inspeksi (sama seperti pil tenor di CreditSimulator)
const PILL_SPRING = { type: 'spring', bounce: 0.15, duration: 0.35 };

// Batang skor inspeksi mengisi dari 0 sekali saat modal dibuka, setelah isi modal mulai
// tampak (CONTENT_VARIANTS berjeda 0,12 dtk), berurutan tipis 40 ms. Memakai scaleX
// (transformasi), jadi pengguna reduced motion langsung melihat keadaan akhir yang sama.
const BAR_EMPTY = { scaleX: 0 };
const BAR_FULL = { scaleX: 1 };
const BAR_FILL_DELAY = 0.2;
const BAR_FILL_STAGGER = 0.04;
const BAR_FILL_TRANSITION = { duration: 0.45, ease: [0.22, 1, 0.36, 1] };

export default function CarDetailModal() {
  const { selectedCar } = useApp();

  // Dialog tetap terpasang selama animasi keluar dan terus menampilkan mobil yang
  // terakhir dibuka (dari prop-nya sendiri), meski selectedCar sudah null.
  return (
    <AnimatePresence>
      {selectedCar && <CarDetailDialog key={selectedCar.id} car={selectedCar} />}
    </AnimatePresence>
  );
}

function CarDetailDialog({ car }) {
  const {
    closeCarDetail, openCarCheckout, checkoutCar, favoriteCarIds, toggleFavoriteCar,
    addTestDrive, storeInfo, transactions, setCurrentReceipt, setIsReceiptModalOpen,
  } = useApp();

  // false selama animasi keluar: modal sudah ditutup, jadi jangan tangkap klik lagi
  const isPresent = useIsPresent();

  const [showBooking, setShowBooking] = useState(false);
  const [booking, setBooking] = useState(emptyBooking);
  const [errors, setErrors] = useState({});
  const [successCode, setSuccessCode] = useState('');

  const carId = car.id;

  // Reset isi form saat mobil yang dibuka berganti / modal ditutup
  useEffect(() => {
    setShowBooking(false);
    setBooking({ ...emptyBooking, date: todayLocal() });
    setErrors({});
    setSuccessCode('');
  }, [carId]);

  // Tutup dengan Escape (kecuali modal "Proses Penjualan" sedang terbuka di atasnya —
  // Escape hanya boleh menutup modal paling atas) + kunci scroll halaman di belakang
  // modal. Kuncinya dibagi lewat scrollLock supaya modal lain yang bertumpuk tidak
  // saling menimpa nilai overflow tersimpannya satu sama lain.
  const closeRef = useRef(closeCarDetail);
  closeRef.current = closeCarDetail;
  const checkoutOpenRef = useRef(false);
  checkoutOpenRef.current = !!checkoutCar;
  // Apakah dialog ini sedang memegang satu kunci scroll (lihat pengaman di bawah)
  const holdsScrollLockRef = useRef(false);

  useEffect(() => {
    if (!carId) return undefined;
    const onKeyDown = (e) => {
      if (e.key === 'Escape' && !checkoutOpenRef.current) closeRef.current();
    };
    window.addEventListener('keydown', onKeyDown);
    lockScroll();
    holdsScrollLockRef.current = true;
    return () => {
      window.removeEventListener('keydown', onKeyDown);
      if (holdsScrollLockRef.current) {
        holdsScrollLockRef.current = false;
        unlockScroll();
      }
    };
  }, [carId]);

  // Setelah ditutup, dialog baru di-unmount (dan kunci scroll dilepas) begitu animasi
  // keluarnya selesai — termasuk foto yang kembali ke kartunya — supaya halaman tidak
  // bergeser di tengah animasi. Pengaman: kalau animasi keluar karena suatu hal tidak
  // pernah selesai, kunci tetap dilepas setelah jeda singkat agar halaman tidak macet.
  useEffect(() => {
    if (isPresent) {
      // Dibuka lagi setelah pengaman sempat melepas kunci → pasang kembali
      if (!holdsScrollLockRef.current) {
        lockScroll();
        holdsScrollLockRef.current = true;
      }
      return undefined;
    }
    const timer = setTimeout(() => {
      if (holdsScrollLockRef.current) {
        holdsScrollLockRef.current = false;
        unlockScroll();
      }
    }, 1000);
    return () => clearTimeout(timer);
  }, [isPresent]);

  const installment = useMemo(() => calcInstallment({
    price: car.price || 0,
    dpPercent: storeInfo.minDpPercent,
    tenorMonths: 60,
    annualRate: storeInfo.creditRate,
  }), [car.price, storeInfo.minDpPercent, storeInfo.creditRate]);

  const isSold = car.status === 'sold';
  const isFavorite = favoriteCarIds.includes(car.id);
  const status = CAR_STATUS[car.status];
  const soldTransaction = isSold ? transactions.find(t => t.id === car.soldTransactionId) : null;
  // Foto bawaan berasal dari Wikimedia Commons dan wajib dicantumkan sumbernya
  const photoCredit = !car.imageUrl && car.imageKey
    ? CAR_IMAGE_CREDITS.find(credit => credit.key === car.imageKey)
    : null;

  // Hati "pop"/"dip" hanya saat status favorit BERUBAH — tidak saat modal dibuka
  // (nilai awal disimpan di ref, jadi render pertama & StrictMode tidak memicunya).
  const heartControls = useAnimationControls();
  const prevFavoriteRef = useRef(isFavorite);
  useEffect(() => {
    if (prevFavoriteRef.current === isFavorite) return;
    prevFavoriteRef.current = isFavorite;
    heartControls.start(isFavorite ? HEART_POP : HEART_DIP);
  }, [isFavorite, heartControls]);

  const specs = [
    { icon: Calendar, label: 'Tahun', value: car.year },
    { icon: Gauge, label: 'Kilometer', value: formatKm(car.mileage) },
    { icon: Settings2, label: 'Transmisi', value: car.transmission },
    { icon: Fuel, label: 'Bahan Bakar', value: car.fuel },
    { icon: Cog, label: 'Mesin', value: car.engineCc ? `${car.engineCc} cc` : '-' },
    { icon: Users, label: 'Kapasitas', value: `${car.seats} kursi` },
    { icon: Palette, label: 'Warna', value: car.color || '-' },
    { icon: MapPin, label: 'Lokasi', value: car.location || '-' },
    { icon: FileText, label: 'Plat', value: car.plate ? `Plat ${String(car.plate).trim().split(' ')[0]}` : '-' },
    { icon: Users, label: 'Kepemilikan', value: `Tangan ke-${car.ownerCount || 1}` },
    { icon: Calendar, label: 'Pajak s/d', value: formatMonth(car.taxValidUntil) },
    { icon: Wrench, label: 'Riwayat Servis', value: car.serviceRecord ? 'Ada' : 'Tidak ada' },
  ];

  const waDigits = String(storeInfo.whatsapp || '').replace(/\D/g, '');
  const waHref = waDigits
    ? `https://wa.me/${waDigits}?text=${encodeURIComponent(`Halo Auto18, saya tertarik dengan ${getCarName(car, { withYear: true })} (${car.code})`)}`
    : '';

  const submitBooking = (e) => {
    e.preventDefault();
    const nextErrors = {};
    if (!booking.customerName.trim()) nextErrors.customerName = 'Nama wajib diisi';
    if (!isValidPhone(booking.phone)) nextErrors.phone = 'Nomor HP tidak valid';
    if (!booking.date) nextErrors.date = 'Tanggal wajib dipilih';
    else if (booking.date < todayLocal()) nextErrors.date = 'Tanggal sudah lewat';
    if (booking.location === 'rumah' && !booking.address.trim()) nextErrors.address = 'Alamat wajib diisi';
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0) return;

    const created = addTestDrive({
      carId: car.id,
      customerName: booking.customerName.trim(),
      phone: booking.phone.trim(),
      date: booking.date,
      time: booking.time,
      location: booking.location,
      address: booking.address.trim(),
      notes: booking.notes.trim(),
    });
    setSuccessCode(created.code);
    setShowBooking(false);
    setBooking({ ...emptyBooking, date: todayLocal() });
  };

  // Catatan: wadah scroll ini sengaja TIDAK diberi layoutScroll. Motion memperlakukan
  // wadah fixed ber-layoutScroll sebagai akar scroll tersendiri (koordinat viewport),
  // sedangkan foto kartu diukur dalam koordinat halaman — foto bersama jadi meleset
  // sejauh scroll halaman.
  return (
    <div
      // inert saat animasi keluar: tombol yang sedang memudar tidak boleh lagi bisa
      // difokus/diaktifkan lewat keyboard (React 18: '' memasang atribut, undefined melepas)
      inert={isPresent ? undefined : ''}
      className={`fixed inset-0 z-40 flex items-start justify-center overflow-y-auto p-4 ${isPresent ? '' : 'pointer-events-none'}`}
      onMouseDown={(e) => { if (e.target === e.currentTarget) closeCarDetail(); }}
    >
      {/* Latar gelap terpisah supaya bisa memudar sendiri tanpa ikut memudarkan panel
          (dan foto yang sedang terbang di dalamnya). Klik di area kosong tetap jatuh ke
          wadah scroll di atas karena latar ini pointer-events-none. */}
      <motion.div
        aria-hidden="true"
        className="pointer-events-none fixed inset-0 bg-black/80 backdrop-blur-sm"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        transition={BACKDROP_TRANSITION}
      />
      <motion.div
        role="dialog"
        aria-modal="true"
        aria-label={getCarName(car, { withYear: true })}
        // relative: panel harus tergambar di atas latar (fixed) di sebelumnya.
        // Tanpa overflow-hidden: foto yang terbang tidak boleh terpotong tepi panel.
        className="relative my-4 w-full max-w-5xl rounded-3xl"
        variants={PANEL_VARIANTS}
        initial="hidden"
        animate="shown"
        exit="gone"
      >
        {/* Permukaan panel (latar + garis tepi) */}
        <motion.div
          aria-hidden="true"
          variants={SURFACE_VARIANTS}
          className="pointer-events-none absolute inset-0 rounded-3xl border border-white/10 bg-ink-800"
        />

        {/* Header */}
        <motion.div
          variants={CONTENT_VARIANTS}
          // Sengaja tidak sticky: dulu panel ber-overflow-hidden sehingga sticky tidak pernah
          // aktif. Tanpa overflow-hidden, sticky akan menempel 16px di bawah tepi (padding
          // overlay) dan isi yang di-scroll terlihat di atasnya.
          className="relative z-10 flex items-center justify-between gap-3 rounded-t-3xl border-b border-white/10 bg-ink-800/95 px-5 py-3.5 backdrop-blur"
        >
          <div className="min-w-0">
            <p className="text-[10px] font-bold uppercase tracking-wider text-brand-500">{car.code}</p>
            <h2 className="truncate font-display text-lg font-black uppercase tracking-wide text-white">
              {getCarName(car, { withYear: true })}
            </h2>
          </div>
          <button type="button" onClick={closeCarDetail} aria-label="Tutup detail mobil" className="a18-btn-ghost px-2">
            <X className="h-5 w-5" />
          </button>
        </motion.div>

        {/* relative: harus tergambar di atas permukaan panel yang absolute */}
        <div className="relative grid gap-6 p-5 lg:grid-cols-[1.5fr_1fr]">
          {/* Kiri: media & spesifikasi */}
          <div className="space-y-6">
            {/* z-[5]: foto yang sedang terbang harus tergambar di atas kolom kanan (yang
                datang belakangan di DOM), tapi tetap di bawah header (z-10) */}
            <div className="relative z-[5]">
              {/* Radius lewat style (bukan hanya kelas) agar Motion mengoreksinya terhadap skala */}
              <motion.div
                layoutId={`car-photo-${car.id}`}
                transition={PHOTO_TRANSITION}
                className="relative overflow-hidden"
                style={{ borderRadius: 16 }}
              >
                <CarImage car={car} className="aspect-[16/10] w-full rounded-2xl" />
                {isSold && (
                  <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <SoldStamp size="md" animate={isFreshSale(car.soldAt)} />
                  </div>
                )}
              </motion.div>
              <motion.div variants={CONTENT_VARIANTS} className="absolute left-3 top-3 flex flex-wrap gap-1.5">
                {car.isFeatured && !isSold && (
                  <span className="rounded-full bg-brand-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-white">
                    Pilihan Auto18
                  </span>
                )}
                {/* Mobil terjual sudah ditandai stempel di foto; badge hanya untuk "Dipesan" */}
                {status && car.status === 'booked' && (
                  <span className={`rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ${status.className}`}>
                    {status.label}
                  </span>
                )}
              </motion.div>
            </div>

            <motion.div variants={CONTENT_VARIANTS} className="space-y-6">
            {/* Kredit foto (lisensi Wikimedia Commons) */}
            {photoCredit && (
              <p className="-mt-4 text-[10px] text-neutral-600">
                Foto ilustrasi (diubah ukurannya): {photoCredit.author} /{' '}
                <a href={photoCredit.sourceUrl} target="_blank" rel="noopener noreferrer" className="underline hover:text-neutral-400">
                  Wikimedia Commons
                </a>{' '}
                (
                <a
                  href="https://creativecommons.org/licenses/by-sa/4.0/"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="underline hover:text-neutral-400"
                >
                  {photoCredit.license}
                </a>
                )
              </p>
            )}

            {/* Spesifikasi */}
            <div>
              <h3 className="font-display text-sm font-black uppercase tracking-wide text-white">Spesifikasi</h3>
              <dl className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                {specs.map(({ icon: Icon, label, value }) => (
                  <div key={label} className="rounded-xl border border-white/10 bg-ink-900 p-3">
                    <dt className="flex items-center gap-1.5 text-[10px] uppercase tracking-wider text-neutral-500">
                      <Icon className="h-3.5 w-3.5" aria-hidden="true" />
                      {label}
                    </dt>
                    <dd className="mt-1 truncate text-sm font-semibold text-white">{value}</dd>
                  </div>
                ))}
              </dl>
            </div>

            {/* Fitur */}
            {car.features?.length > 0 && (
              <div>
                <h3 className="font-display text-sm font-black uppercase tracking-wide text-white">Fitur Unggulan</h3>
                <div className="mt-3 flex flex-wrap gap-2">
                  {car.features.map(feature => (
                    <span key={feature} className="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-ink-900 px-3 py-1.5 text-xs text-neutral-200">
                      <CheckCircle2 className="h-3.5 w-3.5 text-brand-500" aria-hidden="true" />
                      {feature}
                    </span>
                  ))}
                </div>
              </div>
            )}

            {car.description && (
              <div>
                <h3 className="font-display text-sm font-black uppercase tracking-wide text-white">Catatan Showroom</h3>
                <p className="mt-2 text-sm leading-relaxed text-neutral-400">{car.description}</p>
              </div>
            )}

            {/* Laporan inspeksi */}
            <InspectionReport car={car} />

            {/* Benefit */}
            <div className="rounded-2xl border border-brand-600/40 bg-brand-950/30 p-5">
              <h3 className="flex items-center gap-1.5 font-display text-sm font-black uppercase tracking-wide text-white">
                <ShieldCheck className="h-4 w-4 text-brand-500" aria-hidden="true" />
                Gratis Layanan Darurat 24 Jam
              </h3>
              <ul className="mt-3 grid grid-cols-1 gap-1.5 text-xs text-neutral-300 sm:grid-cols-2">
                {AUTO18_BENEFITS.slice(0, 4).map(benefit => (
                  <li key={benefit.no} className="flex items-center gap-1.5">
                    <CheckCircle2 className="h-3.5 w-3.5 shrink-0 text-brand-500" aria-hidden="true" />
                    {benefit.title}
                  </li>
                ))}
              </ul>
              <p className="mt-2 text-xs text-neutral-500">dan {AUTO18_BENEFITS.length - 4} layanan lainnya.</p>
            </div>
            </motion.div>
          </div>

          {/* Kanan: harga & aksi */}
          {/* Tidak sticky (sama seperti perilaku lama): kolom ini lebih tinggi dari layar,
              jadi kalau menempel, tombol "Proses Penjualan" di bawahnya tak terjangkau */}
          <motion.div variants={CONTENT_VARIANTS} className="space-y-4 lg:self-start">
            <div className="a18-card p-5">
              <p className="text-[10px] font-bold uppercase tracking-wider text-neutral-500">Harga Cash</p>
              <p className="font-display text-3xl font-black text-white">{formatRupiah(car.price)}</p>
              {!isSold && (
                <p className="mt-1 text-sm text-brand-400">
                  Cicilan mulai {formatRupiahShort(installment.monthly)}/bln
                  <span className="text-neutral-500"> · DP {storeInfo.minDpPercent}% · 60 bln</span>
                </p>
              )}

              {isSold ? (
                <div className="mt-4 rounded-xl border border-white/10 bg-ink-900 p-4 text-sm">
                  <p className="font-bold text-white">Mobil ini sudah terjual</p>
                  <p className="mt-1 text-neutral-400">
                    {car.soldAt ? formatDate(car.soldAt, false) : '-'}
                    {car.soldPrice ? ` · ${formatRupiah(car.soldPrice)}` : ''}
                  </p>
                </div>
              ) : (
                <div className="mt-4 space-y-2">
                  {successCode ? (
                    <div className="rounded-xl border border-white/15 bg-ink-900 p-4">
                      <p className="flex items-center gap-1.5 text-sm font-bold text-white">
                        <CheckCircle2 className="h-4 w-4 text-brand-500" aria-hidden="true" />
                        Booking test drive terkirim
                      </p>
                      <p className="mt-1 text-xs text-neutral-400">
                        Kode booking <span className="font-mono font-bold text-white">{successCode}</span>.
                        Tim Auto18 akan menghubungi Anda untuk konfirmasi.
                      </p>
                      <button type="button" onClick={() => setSuccessCode('')} className="a18-btn-ghost mt-2 px-0 text-xs">
                        Booking lagi
                      </button>
                    </div>
                  ) : (
                    <button type="button" onClick={() => setShowBooking(v => !v)} className="a18-btn-primary w-full py-3">
                      <CalendarCheck className="h-4 w-4" />
                      {showBooking ? 'Tutup Form' : 'Booking Test Drive'}
                    </button>
                  )}

                  {waHref ? (
                    <a href={waHref} target="_blank" rel="noopener noreferrer" className="a18-btn-outline w-full">
                      <MessageCircle className="h-4 w-4" />
                      Chat WhatsApp
                    </a>
                  ) : (
                    <a href={`tel:${String(storeInfo.callCenter || '').replace(/[^\d+]/g, '')}`} className="a18-btn-outline w-full">
                      <Phone className="h-4 w-4" />
                      Telepon {storeInfo.callCenter}
                    </a>
                  )}
                </div>
              )}

              <button
                type="button"
                onClick={() => toggleFavoriteCar(car.id)}
                aria-pressed={isFavorite}
                className="a18-btn-ghost mt-2 w-full justify-center"
              >
                <motion.span aria-hidden="true" animate={heartControls} className="inline-flex">
                  <Heart className={`h-4 w-4 ${isFavorite ? 'fill-brand-500 text-brand-500' : ''}`} />
                </motion.span>
                {isFavorite ? 'Tersimpan di Favorit' : 'Simpan ke Favorit'}
              </button>
            </div>

            {/* Form booking test drive */}
            {showBooking && !isSold && (
              <form onSubmit={submitBooking} className="a18-card space-y-3 p-5" noValidate>
                <h3 className="font-display text-sm font-black uppercase tracking-wide text-white">Jadwal Test Drive</h3>

                <div>
                  <label htmlFor="td-name" className="a18-label">Nama Lengkap</label>
                  <input
                    id="td-name"
                    value={booking.customerName}
                    onChange={(e) => setBooking(b => ({ ...b, customerName: e.target.value }))}
                    className="a18-input"
                    placeholder="Nama Anda"
                  />
                  {errors.customerName && <p className="mt-1 text-xs text-brand-400">{errors.customerName}</p>}
                </div>

                <div>
                  <label htmlFor="td-phone" className="a18-label">Nomor HP</label>
                  <input
                    id="td-phone"
                    type="tel"
                    value={booking.phone}
                    onChange={(e) => setBooking(b => ({ ...b, phone: e.target.value }))}
                    className="a18-input"
                    placeholder="0812-3456-7890"
                  />
                  {errors.phone && <p className="mt-1 text-xs text-brand-400">{errors.phone}</p>}
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label htmlFor="td-date" className="a18-label">Tanggal</label>
                    <input
                      id="td-date"
                      type="date"
                      min={todayLocal()}
                      value={booking.date}
                      onChange={(e) => setBooking(b => ({ ...b, date: e.target.value }))}
                      className="a18-input"
                    />
                    {errors.date && <p className="mt-1 text-xs text-brand-400">{errors.date}</p>}
                  </div>
                  <div>
                    <label htmlFor="td-time" className="a18-label">Jam</label>
                    <select
                      id="td-time"
                      value={booking.time}
                      onChange={(e) => setBooking(b => ({ ...b, time: e.target.value }))}
                      className="a18-input"
                    >
                      {TIME_SLOTS.map(slot => <option key={slot} value={slot}>{slot}</option>)}
                    </select>
                  </div>
                </div>

                <fieldset>
                  <legend className="a18-label">Lokasi</legend>
                  <div className="flex gap-2">
                    {[{ id: 'showroom', label: 'Di Showroom' }, { id: 'rumah', label: 'Di Rumah Saya' }].map(option => (
                      <label
                        key={option.id}
                        className={`flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold transition-colors ${
                          booking.location === option.id
                            ? 'border-brand-500 bg-brand-600 text-white'
                            : 'border-white/15 text-neutral-300 hover:border-white/40'
                        }`}
                      >
                        <input
                          type="radio"
                          name="td-location"
                          className="sr-only"
                          checked={booking.location === option.id}
                          onChange={() => setBooking(b => ({ ...b, location: option.id }))}
                        />
                        {option.label}
                      </label>
                    ))}
                  </div>
                </fieldset>

                {booking.location === 'rumah' && (
                  <div>
                    <label htmlFor="td-address" className="a18-label">Alamat</label>
                    <input
                      id="td-address"
                      value={booking.address}
                      onChange={(e) => setBooking(b => ({ ...b, address: e.target.value }))}
                      className="a18-input"
                      placeholder="Kecamatan, kota"
                    />
                    {errors.address && <p className="mt-1 text-xs text-brand-400">{errors.address}</p>}
                  </div>
                )}

                <div>
                  <label htmlFor="td-notes" className="a18-label">Catatan (opsional)</label>
                  <textarea
                    id="td-notes"
                    rows={2}
                    value={booking.notes}
                    onChange={(e) => setBooking(b => ({ ...b, notes: e.target.value }))}
                    className="a18-input"
                    placeholder="Misal: ingin tanya simulasi kredit"
                  />
                </div>

                <button type="submit" className="a18-btn-primary w-full">Kirim Booking</button>
              </form>
            )}

            <CreditSimulator price={car.price} compact />

            {/* Panel showroom */}
            <div className="a18-card p-4">
              <p className="text-[10px] font-bold uppercase tracking-wider text-neutral-500">Panel Showroom</p>
              {isSold ? (
                <div className="mt-2 space-y-2">
                  <p className="text-xs text-neutral-400">
                    Terjual {car.soldAt ? formatDate(car.soldAt) : '-'} · {formatRupiah(car.soldPrice || 0)}
                  </p>
                  {soldTransaction && (
                    <button
                      type="button"
                      onClick={() => { setCurrentReceipt(soldTransaction); setIsReceiptModalOpen(true); }}
                      className="a18-btn-outline w-full"
                    >
                      <Receipt className="h-4 w-4" />
                      Lihat Nota
                    </button>
                  )}
                </div>
              ) : (
                <button type="button" onClick={() => openCarCheckout(car.id)} className="a18-btn-outline mt-2 w-full">
                  <Receipt className="h-4 w-4" />
                  Proses Penjualan
                </button>
              )}
            </div>
          </motion.div>
        </div>
      </motion.div>
    </div>
  );
}

// Kartu "Laporan Inspeksi Auto18": diagram mobil tampak atas + batang skor per kategori.
// Dipisah jadi komponen sendiri supaya hover/pilih kategori hanya me-render kartu ini,
// bukan seluruh dialog.
function InspectionReport({ car }) {
  const uid = useId();
  const detailId = `${uid}-inspection-detail`;

  const scores = useMemo(() => Object.fromEntries(
    INSPECTION_CATEGORIES.map(category => [category.key, car.inspection?.[category.key] ?? 0])
  ), [car.inspection]);

  // Bawaan: kategori dengan skor terendah — bagian yang paling perlu dilihat pembeli.
  // Dialog di-key per mobil, jadi pilihan ini otomatis diulang untuk mobil lain.
  const [selectedKey, setSelectedKey] = useState(() => getLowestCategoryKey(scores));

  return (
    <div className="a18-card p-5">
      <div className="flex items-center gap-4">
        <div className="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-full border-4 border-brand-600 bg-ink-900">
          <span className="font-display text-xl font-black leading-none text-white">{car.inspectionScore}</span>
          <span className="text-[9px] uppercase text-neutral-500">dari 100</span>
        </div>
        <div>
          <h3 className="flex items-center gap-1.5 font-display text-sm font-black uppercase tracking-wide text-white">
            <BadgeCheck className="h-4 w-4 text-brand-500" aria-hidden="true" />
            Laporan Inspeksi Auto18
          </h3>
          <p className="mt-1 text-xs text-neutral-400">
            Mesin, transmisi, kaki-kaki, kelistrikan, interior, eksterior, dan dokumen diperiksa sebelum dijual.
          </p>
        </div>
      </div>

      {/* Diagram di atas batang skor (mobile), di sampingnya mulai md */}
      <div className="mt-4 grid gap-5 md:grid-cols-[200px_minmax(0,1fr)] md:items-center">
        <InspectionDiagram
          scores={scores}
          selectedKey={selectedKey}
          onSelect={setSelectedKey}
          detailId={detailId}
          className="mx-auto w-full max-w-[200px] md:max-w-none"
        />

        {/* isolate: z-index pil & teks tidak bocor ke luar kartu */}
        <div role="group" aria-label="Skor per kategori inspeksi" className="isolate -mx-2.5 space-y-0.5">
          {INSPECTION_CATEGORIES.map((category, index) => {
            const score = scores[category.key];
            const isSelected = selectedKey === category.key;
            const select = () => setSelectedKey(category.key);
            return (
              <button
                key={category.key}
                type="button"
                aria-pressed={isSelected}
                aria-describedby={isSelected ? detailId : undefined}
                onClick={select}
                onFocus={select}
                // Hover memilih untuk mouse/pena; sentuhan lewat tap saja supaya menggeser
                // modal dengan jari tidak ikut mengganti pilihan.
                onPointerEnter={(e) => { if (e.pointerType !== 'touch') select(); }}
                className="relative block w-full rounded-lg px-2.5 py-1.5 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
              >
                {isSelected && (
                  // layoutId unik per instance (useId); layoutDependency: pil hanya bergeser
                  // saat pilihan berganti, bukan saat layout modal berubah.
                  <motion.span
                    layoutId={`${uid}-inspection-pill`}
                    layoutDependency={selectedKey}
                    transition={PILL_SPRING}
                    aria-hidden="true"
                    className="absolute inset-0 z-[1] border border-brand-600/80 bg-brand-600/10"
                    style={{ borderRadius: 8 }}
                  />
                )}
                <span className="relative z-10 block">
                  <span className="flex items-center justify-between text-xs">
                    <span className="text-neutral-300">{category.label}</span>
                    <span className="font-bold text-white">{score}</span>
                  </span>
                  <span className="mt-1 block h-1.5 overflow-hidden rounded-full bg-white/10">
                    <motion.span
                      className={`block h-full origin-left rounded-full ${scoreColor(score)}`}
                      style={{ width: `${score}%` }}
                      initial={BAR_EMPTY}
                      animate={BAR_FULL}
                      transition={{ ...BAR_FILL_TRANSITION, delay: BAR_FILL_DELAY + index * BAR_FILL_STAGGER }}
                    />
                  </span>
                </span>
              </button>
            );
          })}
        </div>
      </div>
    </div>
  );
}
