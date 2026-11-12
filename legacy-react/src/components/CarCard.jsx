import React, { useEffect, useRef } from 'react';
import { motion, useAnimate, useIsPresent } from 'motion/react';
import { Heart, MapPin, Gauge, Calendar, Settings2, Fuel, BadgeCheck } from 'lucide-react';
import { useApp } from '../context/AppContext';
import CarImage from './CarImage';
import SoldStamp, { isFreshSale } from './SoldStamp';
import { CAR_STATUS, calcInstallment, formatKm, formatRupiahShort, getCarName } from '../utils/carUtils';
import { formatRupiah } from '../utils/formatters';

// Foto kartu "terbang" ke/dari foto utama CarDetailModal (layoutId yang sama).
const PHOTO_TRANSITION = { type: 'spring', bounce: 0.15, duration: 0.4 };
// Sudut atas mengikuti rounded-2xl kartu. Radius wajib lewat style agar Motion
// mengoreksinya terhadap skala selama foto berpindah bentuk.
const PHOTO_RADIUS = {
  borderTopLeftRadius: 16,
  borderTopRightRadius: 16,
  borderBottomLeftRadius: 0,
  borderBottomRightRadius: 0,
};

// "Pop" ikon hati — harus identik dengan tombol favorit di CarDetailModal:
// jadi favorit 1 -> 1.3 -> 1, batal favorit sedikit mengempis 1 -> 0.85 -> 1, ~300 ms.
// Pegas Motion hanya mendukung 2 keyframe, jadi dipakai keyframe dengan ease-out.
// null = mulai dari skala saat ini, jadi klik beruntun tidak melompat balik ke 1.
const HEART_POP_IN = [null, 1.3, 1];
const HEART_POP_OUT = [null, 0.85, 1];
const HEART_POP_TRANSITION = { duration: 0.3, times: [0, 0.4, 1], ease: 'easeOut' };

export default function CarCard({ car }) {
  const { openCarDetail, favoriteCarIds, toggleFavoriteCar, storeInfo } = useApp();
  // false saat kartu sedang keluar dari grid (AnimatePresence di CarMarketplace):
  // kartu yang sedang menghilang tidak boleh lagi menerima klik atau fokus.
  const isPresent = useIsPresent();

  const isFavorite = favoriteCarIds.includes(car.id);

  // Hati hanya "pop" saat status favorit benar-benar berubah — tidak saat kartu pertama
  // kali tampil (termasuk saat grid disaring ulang) maupun saat modal detail dibuka.
  const [heartScope, animateHeart] = useAnimate();
  const wasFavorite = useRef(isFavorite);
  useEffect(() => {
    if (wasFavorite.current === isFavorite) return;
    wasFavorite.current = isFavorite;
    // Animasi baru pada nilai yang sama otomatis menghentikan yang lama, dan useAnimate
    // menghentikan semuanya saat kartu dilepas, jadi tidak perlu cleanup di sini.
    if (heartScope.current) {
      animateHeart(heartScope.current, { scale: isFavorite ? HEART_POP_IN : HEART_POP_OUT }, HEART_POP_TRANSITION);
    }
  }, [isFavorite, animateHeart, heartScope]);

  const isSold = car.status === 'sold';
  const status = CAR_STATUS[car.status];
  const { monthly } = calcInstallment({
    price: car.price,
    dpPercent: storeInfo.minDpPercent,
    tenorMonths: 60,
    annualRate: storeInfo.creditRate,
  });

  const open = () => openCarDetail(car.id);

  const specs = [
    { icon: Calendar, label: car.year },
    { icon: Gauge, label: formatKm(car.mileage) },
    { icon: Settings2, label: car.transmission },
    { icon: Fuel, label: car.fuel },
  ];

  return (
    <div
      role="button"
      tabIndex={isPresent ? 0 : -1}
      onClick={open}
      onKeyDown={(e) => {
        // Abaikan kalau event ini menggelembung dari elemen lain di dalam kartu (mis.
        // tombol favorit) — kalau tidak, Enter/Space di tombol itu ikut membuka detail.
        if (e.target !== e.currentTarget) return;
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          open();
        }
      }}
      aria-label={`Lihat detail ${getCarName(car, { withYear: true })}`}
      className={`group flex cursor-pointer flex-col overflow-hidden rounded-2xl border border-white/10 bg-ink-800 transition-all hover:border-brand-600 hover:shadow-lg hover:shadow-brand-900/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 ${isPresent ? '' : 'pointer-events-none'}`}
    >
      {/* Foto */}
      <div className="relative">
        <motion.div
          layoutId={`car-photo-${car.id}`}
          transition={PHOTO_TRANSITION}
          className="relative overflow-hidden"
          style={PHOTO_RADIUS}
        >
          <CarImage
            car={car}
            className="aspect-[16/10] w-full"
            imgClassName={`transition-transform duration-500 group-hover:scale-105 ${isSold ? 'grayscale' : ''}`}
          />
          {/* Stempel ikut di dalam foto supaya ikut terbang bersama fotonya */}
          {isSold && (
            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
              <SoldStamp size="sm" animate={isFreshSale(car.soldAt)} />
            </div>
          )}
        </motion.div>

        <div className="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between gap-2 p-3">
          <div className="flex flex-wrap gap-1.5">
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
          </div>
        </div>

        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            toggleFavoriteCar(car.id);
          }}
          aria-pressed={isFavorite}
          aria-label={isFavorite ? 'Hapus dari favorit' : 'Simpan ke favorit'}
          title={isFavorite ? 'Hapus dari favorit' : 'Simpan ke favorit'}
          className="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full border border-white/20 bg-black/60 text-white backdrop-blur transition-colors hover:border-brand-500 hover:bg-brand-600"
        >
          <span ref={heartScope} className="flex">
            <Heart className={`h-4 w-4 ${isFavorite ? 'fill-brand-500 text-brand-500' : ''}`} />
          </span>
        </button>

        {car.inspectionScore > 0 && (
          <span className="absolute bottom-3 left-3 inline-flex items-center gap-1 rounded-full bg-black/75 px-2.5 py-1 text-[10px] font-bold text-white backdrop-blur">
            <BadgeCheck className="h-3.5 w-3.5 text-brand-500" aria-hidden="true" />
            Inspeksi {car.inspectionScore}/100
          </span>
        )}
      </div>

      {/* Info */}
      <div className={`flex flex-1 flex-col p-4 ${isSold ? 'opacity-70' : ''}`}>
        <h3 className="font-display text-base font-black uppercase leading-tight tracking-wide text-white">
          {car.brand} {car.model}
        </h3>
        <p className="mt-0.5 truncate text-xs text-neutral-400">
          {car.variant} · {car.year}
        </p>

        <div className="mt-3 flex flex-wrap gap-x-3 gap-y-1.5 text-[11px] text-neutral-400">
          {specs.map(({ icon: Icon, label }) => (
            <span key={label} className="inline-flex items-center gap-1">
              <Icon className="h-3.5 w-3.5 text-neutral-500" aria-hidden="true" />
              {label}
            </span>
          ))}
        </div>

        <p className="mt-2 inline-flex items-center gap-1 text-[11px] text-neutral-500">
          <MapPin className="h-3.5 w-3.5" aria-hidden="true" />
          {car.location}
        </p>

        <div className="mt-auto border-t border-white/10 pt-3">
          <p className="font-display text-xl font-black text-white">{formatRupiah(car.price)}</p>
          {!isSold && (
            <p className="mt-0.5 text-xs text-brand-400">
              Cicilan mulai {formatRupiahShort(monthly)}/bln
            </p>
          )}
          {isSold && car.soldPrice > 0 && (
            <p className="mt-0.5 text-xs text-neutral-500">Terjual {formatRupiah(car.soldPrice)}</p>
          )}
        </div>
      </div>
    </div>
  );
}
