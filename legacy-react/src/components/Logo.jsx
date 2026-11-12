import React from 'react';

// Ingin memakai file logo asli? Taruh gambarnya di folder public/ (mis. public/logo-auto18.png)
// lalu isi konstanta ini dengan './logo-auto18.png'. Kosongkan untuk logo teks bawaan.
const LOGO_IMAGE_URL = '';

const SIZES = {
  sm: { word: 'text-xl', tagline: 'text-[7px]', line: 'w-2.5', img: 'h-8' },
  md: { word: 'text-2xl', tagline: 'text-[8px]', line: 'w-3', img: 'h-10' },
  lg: { word: 'text-4xl', tagline: 'text-[11px]', line: 'w-5', img: 'h-14' },
  xl: { word: 'text-6xl', tagline: 'text-sm', line: 'w-8', img: 'h-24' },
};

/**
 * Logo Auto18: "AUTO" putih-perak + "18" merah, dengan tulisan "AUTO EIGHTEEN".
 * @param {'sm'|'md'|'lg'|'xl'} size
 * @param {boolean} showTagline tampilkan "-AUTO EIGHTEEN-"
 */
export default function Logo({ size = 'md', showTagline = true, className = '' }) {
  const s = SIZES[size] || SIZES.md;

  if (LOGO_IMAGE_URL) {
    return <img src={LOGO_IMAGE_URL} alt="Auto18 - Auto Eighteen" className={`${s.img} w-auto ${className}`} />;
  }

  return (
    <div className={`inline-flex select-none flex-col items-center leading-none ${className}`} aria-label="Auto18 - Auto Eighteen">
      <div className={`font-display font-black italic tracking-tighter ${s.word}`}>
        <span className="bg-gradient-to-b from-white via-neutral-200 to-neutral-500 bg-clip-text pr-0.5 text-transparent drop-shadow-[0_2px_0_rgba(0,0,0,0.9)]">
          AUTO
        </span>
        <span className="bg-gradient-to-b from-brand-400 via-brand-600 to-brand-800 bg-clip-text pr-1 text-transparent drop-shadow-[0_2px_0_rgba(0,0,0,0.9)]">
          18
        </span>
      </div>
      {showTagline && (
        <div className={`mt-1 flex items-center gap-1 font-display font-bold uppercase tracking-[0.3em] text-neutral-300 ${s.tagline}`}>
          <span className={`h-px bg-neutral-400 ${s.line}`} />
          <span>Auto Eighteen</span>
          <span className={`h-px bg-neutral-400 ${s.line}`} />
        </div>
      )}
    </div>
  );
}
