import React, { useId } from 'react';
import { motion } from 'motion/react';

// Stempel tinta karet merah ("TERJUAL", "DIBATALKAN", ...).
// - Elemen luar polos: menerima className pemanggil (mis. posisi absolut + translate Tailwind).
// - Semua transformasi Motion ada di elemen dalam (.a18-stamp-press) supaya
//   tidak menimpa translate milik pemanggil.
// - Tekstur tinta (tepi kasar, bintik & kepadatan tinta tidak rata) dibuat dari
//   filter SVG inline per instance — tanpa file gambar.

// Kemiringan akhir stempel (derajat). Jika diubah, samakan juga aturan
// "#printable-receipt .a18-stamp-press" di bagian @media print src/index.css.
const TILT = -10;

// Nama kelas ditulis utuh agar tidak ikut dibuang oleh purge Tailwind.
// warp  = kekasaran tepi tinta (px, skala feDisplacementMap)
// grain = frekuensi bintik tinta yang tidak menempel
const SIZES = {
  sm: { className: 'a18-stamp--sm', warp: 2.5, grain: 0.6 },
  md: { className: 'a18-stamp--md', warp: 4, grain: 0.42 },
  lg: { className: 'a18-stamp--lg', warp: 3, grain: 0.5 }
};

// Keadaan akhir — dipakai juga sebagai keadaan statis, jadi hasil akhirnya identik
// dengan/tanpa animasi (termasuk saat reduced motion aktif).
const REST = { opacity: 1, scale: 1, rotate: TILT };

// Awal "hentakan": 2x lebih besar, belum terlihat, sedikit berputar dari kemiringan akhir
const SLAM_FROM = { opacity: 0, scale: 2, rotate: TILT + 6 };

// Pegas cepat dengan sedikit overshoot (skala turun sedikit di bawah 1 lalu kembali) ≈ 450ms.
// Jeda kecil agar hentakan terbaca sebagai aksi terpisah setelah struk/kartu muncul.
const SLAM_DELAY = 0.12;
const slamTransition = (delay) => ({
  default: { type: 'spring', duration: 0.45, bounce: 0.3, delay },
  opacity: { duration: 0.1, ease: 'easeOut', delay }
});

/**
 * true bila isoDate berada dalam windowMs dari sekarang — dipakai untuk
 * menganimasikan stempel hanya sesaat setelah penjualan terjadi.
 */
export function isFreshSale(isoDate, windowMs = 30000) {
  if (isoDate === null || isoDate === undefined || isoDate === '') return false;
  const time = isoDate instanceof Date ? isoDate.getTime() : new Date(isoDate).getTime();
  if (!Number.isFinite(time)) return false;
  const limit = Number.isFinite(windowMs) ? Math.max(0, windowMs) : 30000;
  return Math.abs(Date.now() - time) <= limit;
}

/**
 * delay (detik): jeda sebelum hentakan — mis. menunggu kertas struk selesai "tercetak".
 */
export default function SoldStamp({ label = 'TERJUAL', size = 'md', animate = false, className = '', delay = SLAM_DELAY }) {
  // ID filter unik per instance; karakter ":" dari useId dibuang agar aman di url(#...)
  const reactId = useId();
  const filterId = `a18-ink-${reactId.replace(/[^a-zA-Z0-9_-]/g, '')}`;
  const config = SIZES[size] || SIZES.md;
  const text = String(label ?? '');
  // Label panjang (mis. "DIBATALKAN") dikecilkan sedikit agar tetap muat di kertas struk
  const isLong = text.length > 8;

  return (
    <span
      role="img"
      aria-label={`Stempel ${text}`}
      data-stamp-mode={animate ? 'slam' : 'static'}
      className={`a18-stamp-root ${className}`.trim()}
    >
      {/* Definisi tekstur tinta (tidak terlihat, ukuran 0) */}
      <svg className="a18-stamp-defs" width="0" height="0" aria-hidden="true" focusable="false">
        <filter
          id={filterId}
          x="-10%"
          y="-20%"
          width="120%"
          height="140%"
          colorInterpolationFilters="sRGB"
        >
          {/* Noise halus: tepi tinta bergelombang + kepadatan tinta tidak rata */}
          <feTurbulence type="fractalNoise" baseFrequency="0.045" numOctaves="3" seed="4" result="warp" />
          <feDisplacementMap
            in="SourceGraphic"
            in2="warp"
            scale={config.warp}
            xChannelSelector="R"
            yChannelSelector="G"
            result="rough"
          />
          <feColorMatrix
            in="warp"
            type="matrix"
            values="0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 -1.8 0 1.85"
            result="density"
          />
          {/* Noise kasar: bintik-bintik kecil tempat tinta tidak menempel */}
          <feTurbulence type="fractalNoise" baseFrequency={config.grain} numOctaves="2" seed="9" result="grain" />
          <feColorMatrix
            in="grain"
            type="matrix"
            values="0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  -9 0 0 0 6.4"
            result="speckle"
          />
          <feComposite in="rough" in2="speckle" operator="in" result="speckled" />
          <feComposite in="speckled" in2="density" operator="in" />
        </filter>
      </svg>

      <motion.span
        className="a18-stamp-press"
        initial={animate ? SLAM_FROM : false}
        animate={REST}
        transition={slamTransition(Number.isFinite(delay) ? Math.max(0, delay) : SLAM_DELAY)}
      >
        <span
          className={`a18-stamp ${config.className}${isLong ? ' a18-stamp--long' : ''}`}
          style={{ filter: `url(#${filterId})` }}
        >
          <span className="a18-stamp__rule">
            <span className="a18-stamp__label">{text}</span>
          </span>
        </span>
      </motion.span>
    </span>
  );
}
