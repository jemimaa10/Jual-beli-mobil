import React from 'react';
import { INSPECTION_CATEGORIES } from '../data/initialData';

// Diagram laporan inspeksi: mobil tampak atas (depan di atas) yang zonanya dipetakan ke
// kunci INSPECTION_CATEGORIES. Tiap zona diwarnai menurut skornya dengan ambang yang sama
// seperti batang skor di CarDetailModal (>= 90 putih, >= 80 kuning, < 80 merah), dan satu
// zona "terpilih" diberi garis merah Auto18.
//
// SVG ini hanya penunjuk untuk mouse/sentuh (aria-hidden). Kontrol yang aksesibel adalah
// tombol batang skor di CarDetailModal; keduanya berbagi selectedKey lewat props.

// Nama kelas ditulis utuh agar tidak ikut dibuang oleh purge Tailwind.
const TONES = {
  high: { fill: 'fill-white/15', stroke: 'stroke-white/40', text: 'text-white' },
  mid: { fill: 'fill-amber-400/25', stroke: 'stroke-amber-400/60', text: 'text-amber-400' },
  low: { fill: 'fill-brand-600/35', stroke: 'stroke-brand-600/70', text: 'text-brand-400' },
};

export const scoreTone = (score) => (score >= 90 ? TONES.high : score >= 80 ? TONES.mid : TONES.low);

export const scoreVerdict = (score) => (score >= 90 ? 'Sangat baik' : score >= 80 ? 'Baik' : 'Perlu perhatian');

// Kunci kategori dengan skor terendah (seri → yang lebih dulu di INSPECTION_CATEGORIES)
export function getLowestCategoryKey(scores) {
  let lowestKey = INSPECTION_CATEGORIES[0]?.key;
  let lowestScore = Infinity;
  INSPECTION_CATEGORIES.forEach(({ key }) => {
    const score = Number(scores?.[key]) || 0;
    if (score < lowestScore) {
      lowestKey = key;
      lowestScore = score;
    }
  });
  return lowestKey;
}

// ---------------------------------------------------------------------------
// Geometri (viewBox 198 × 260). Sumbu tengah mobil di x = 65.
// ---------------------------------------------------------------------------
const BODY =
  'M65 10 C89 10 105 12 108 30 L111 80 L111 200 L109 232 C107 246 91 250 65 250 ' +
  'C39 250 23 246 21 232 L19 200 L19 80 L22 30 C25 12 41 10 65 10 Z';
const MIRROR_LEFT = 'M20 98 L12 95 Q9 95 9 98 L9 104 Q9 107 12 107 L20 106 Z';
const MIRROR_RIGHT = 'M110 98 L118 95 Q121 95 121 98 L121 104 Q121 107 118 107 L110 106 Z';
const WINDSHIELD = 'M33 110 L37 95 Q65 90 93 95 L97 110 Q65 107 33 110 Z';
const REAR_WINDOW = 'M33 199 Q65 202 97 199 L93 212 Q65 215 37 212 Z';
// Kartu dokumen (BPKB/STNK) di samping mobil, sudut kanan atas terlipat
const DOC_CARD = 'M140 100 Q140 96 144 96 L178 96 L190 108 L190 160 Q190 164 186 164 L144 164 Q140 164 140 160 Z';
const DOC_FOLD = 'M178 96 L178 108 L190 108';

// Bentuk utama tiap zona — dipakai untuk warna zona sekaligus garis "terpilih"
const ZONES = {
  // empat roda (digambar lebih dulu, sebagian tertutup bodi)
  kakiKaki: [
    { x: 10, y: 46, width: 14, height: 34, rx: 3.5 },
    { x: 106, y: 46, width: 14, height: 34, rx: 3.5 },
    { x: 10, y: 180, width: 14, height: 34, rx: 3.5 },
    { x: 106, y: 180, width: 14, height: 34, rx: 3.5 },
  ],
  // kulit bodi + spion
  eksterior: [{ d: BODY }, { d: MIRROR_LEFT }, { d: MIRROR_RIGHT }],
  // kap mesin
  mesin: [{ x: 28, y: 31, width: 74, height: 58, rx: 8 }],
  // lampu depan kiri/kanan + dasbor
  kelistrikan: [
    { x: 31, y: 19, width: 18, height: 7, rx: 3.5 },
    { x: 81, y: 19, width: 18, height: 7, rx: 3.5 },
    { x: 34, y: 112, width: 62, height: 6, rx: 3 },
  ],
  // kabin
  interior: [{ x: 30, y: 121, width: 70, height: 75, rx: 8 }],
  // terowongan transmisi di antara jok depan
  transmisi: [{ x: 59, y: 124, width: 12, height: 40, rx: 4 }],
  dokumen: [{ d: DOC_CARD }],
};

// Detail di dalam zona (warna zona yang sama, ditumpuk sehingga sedikit lebih pekat)
const DETAILS = {
  eksterior: [
    // sambungan pintu depan/belakang
    { d: 'M19 158 L30 158', line: true },
    { d: 'M100 158 L111 158', line: true },
  ],
  interior: [
    { x: 36, y: 128, width: 20, height: 24, rx: 5 }, // jok depan kiri
    { x: 74, y: 128, width: 20, height: 24, rx: 5 }, // jok depan kanan
    { x: 36, y: 169, width: 58, height: 21, rx: 6 }, // jok belakang
  ],
  transmisi: [{ cx: 65, cy: 136, r: 3.5 }], // tuas persneling
  dokumen: [
    { d: DOC_FOLD, line: true },
    { x: 147, y: 120, width: 32, height: 2.5, rx: 1.25 },
    { x: 147, y: 144, width: 24, height: 2.5, rx: 1.25 },
    { x: 147, y: 151, width: 34, height: 2.5, rx: 1.25 },
  ],
};

// Urutan gambar: roda di bawah bodi, lalu bagian-bagian di atas bodi
const DRAW_ORDER = ['kakiKaki', 'eksterior', 'mesin', 'kelistrikan', 'interior', 'transmisi', 'dokumen'];

function Shape({ shape, className }) {
  if (shape.d) return <path d={shape.d} className={className} />;
  if (shape.r) return <circle cx={shape.cx} cy={shape.cy} r={shape.r} className={className} />;
  return (
    <rect
      x={shape.x}
      y={shape.y}
      width={shape.width}
      height={shape.height}
      rx={shape.rx}
      className={className}
    />
  );
}

export default function InspectionDiagram({ scores, selectedKey, onSelect, detailId, className = '' }) {
  const scoreOf = (key) => Number(scores?.[key]) || 0;
  const selected = INSPECTION_CATEGORIES.find(category => category.key === selectedKey) || INSPECTION_CATEGORIES[0];
  const selectedScore = scoreOf(selected.key);
  const selectedTone = scoreTone(selectedScore);

  const select = (key) => {
    if (onSelect) onSelect(key);
  };

  const renderZone = (key) => {
    const tone = scoreTone(scoreOf(key));
    return (
      <g
        key={key}
        data-zone={key}
        className="cursor-pointer"
        // Hover memilih zona untuk mouse/pena; sentuhan lewat tap (klik) saja supaya
        // menggeser halaman dengan jari tidak ikut mengganti pilihan.
        onPointerEnter={(e) => { if (e.pointerType !== 'touch') select(key); }}
        onClick={() => select(key)}
      >
        {/* Alas pekat supaya warna zona di bawahnya tidak ikut tercampur */}
        {ZONES[key].map((shape, i) => <Shape key={`base-${i}`} shape={shape} className="fill-ink-900" />)}
        {ZONES[key].map((shape, i) => (
          <Shape key={`tint-${i}`} shape={shape} className={`${tone.fill} ${tone.stroke}`} />
        ))}
        {(DETAILS[key] || []).map((shape, i) => (
          <Shape
            key={`detail-${i}`}
            shape={shape}
            className={shape.line ? `fill-none ${tone.stroke}` : `${tone.fill} ${tone.stroke}`}
          />
        ))}
        {key === 'dokumen' && (
          <g className="pointer-events-none select-none fill-white/85 font-display" fontSize={9} fontWeight={900} letterSpacing={0.6}>
            <text x={147} y={115}>BPKB</text>
            <text x={147} y={139}>STNK</text>
          </g>
        )}
      </g>
    );
  };

  return (
    <div className={className}>
      <svg viewBox="0 0 198 260" className="block h-auto w-full" aria-hidden="true" focusable="false">
        {DRAW_ORDER.slice(0, 2).map(renderZone)}

        {/* Kaca depan & belakang: hiasan saja — klik/hover tembus ke bodi (eksterior) */}
        <g className="pointer-events-none fill-ink-700 stroke-white/25">
          <path d={WINDSHIELD} />
          <path d={REAR_WINDOW} />
        </g>

        {DRAW_ORDER.slice(2).map(renderZone)}

        {/* Garis merah zona terpilih — paling atas agar tidak tertutup zona lain */}
        <g className="pointer-events-none fill-none stroke-brand-600" strokeWidth={2.5} strokeLinejoin="round">
          {(ZONES[selected.key] || []).map((shape, i) => (
            <Shape key={`${selected.key}-${i}`} shape={shape} />
          ))}
        </g>
      </svg>

      {/* Keterangan zona terpilih: label, skor/100, dan ringkasan kondisi */}
      <p id={detailId} className="mt-3 min-h-[2.5rem] text-center text-xs leading-snug">
        <span className="block font-bold text-white">{selected.label}</span>
        <span className="text-neutral-400">
          <span className="font-bold text-white">{selectedScore}</span>/100 ·{' '}
        </span>
        <span className={`font-bold ${selectedTone.text}`}>{scoreVerdict(selectedScore)}</span>
      </p>
    </div>
  );
}
