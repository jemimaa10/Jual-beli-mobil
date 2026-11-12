import React, { useEffect, useId, useState } from 'react';
import { CAR_IMAGES } from '../data/carImages';
import { CAR_COLORS } from '../data/initialData';
import { getCarName } from '../utils/carUtils';

// Bentuk ilustrasi per tipe bodi (viewBox 0 0 400 200)
const SHAPES = {
  Hatchback: {
    body: 'M28 104 L74 100 L120 74 Q158 62 210 64 L262 68 Q286 72 306 84 L356 102 Q372 108 372 124 L372 142 Q372 152 360 152 L40 152 Q28 152 28 140 Z',
    cabin: '126,76 246,74 286,104 104,104',
    glass: [['130,80 186,79 186,100 112,100'], ['194,79 242,79 276,100 194,100']],
    wheels: [{ cx: 108, cy: 152, r: 30 }, { cx: 292, cy: 152, r: 30 }],
  },
  Sedan: {
    body: 'M22 106 L70 102 L124 76 Q162 64 212 66 L266 72 Q296 78 318 90 L364 104 Q378 110 378 126 L378 144 Q378 154 366 154 L34 154 Q22 154 22 142 Z',
    cabin: '132,78 244,76 288,106 108,106',
    glass: [['136,82 190,81 190,102 116,102'], ['198,81 240,81 278,102 198,102']],
    wheels: [{ cx: 110, cy: 154, r: 30 }, { cx: 296, cy: 154, r: 30 }],
  },
  MPV: {
    body: 'M26 100 L64 96 L104 62 Q140 50 206 52 L268 56 Q296 62 314 78 L358 98 Q372 104 372 122 L372 142 Q372 152 360 152 L38 152 Q26 152 26 138 Z',
    cabin: '108,64 258,60 296,100 86,100',
    glass: [['112,68 174,66 174,96 92,96'], ['182,66 236,65 268,96 182,96'], ['244,65 256,65 282,96 250,96']],
    wheels: [{ cx: 106, cy: 152, r: 31 }, { cx: 296, cy: 152, r: 31 }],
  },
  SUV: {
    body: 'M24 96 L60 92 L100 56 Q138 44 208 46 L276 50 Q304 56 322 72 L362 92 Q376 98 376 116 L376 138 Q376 148 364 148 L36 148 Q24 148 24 134 Z',
    cabin: '104,58 268,54 306,94 84,94',
    glass: [['108,62 172,60 172,90 88,90'], ['180,60 240,59 272,90 180,90'], ['248,59 266,59 292,90 254,90']],
    wheels: [{ cx: 104, cy: 150, r: 34 }, { cx: 298, cy: 150, r: 34 }],
  },
  'Pick-up': {
    body: 'M24 100 L60 96 L100 60 Q136 48 196 50 L226 54 L226 96 L358 96 Q372 100 372 118 L372 142 Q372 152 360 152 L36 152 Q24 152 24 138 Z',
    cabin: '104,62 208,58 238,100 84,100',
    glass: [['108,66 168,64 168,96 88,96'], ['176,64 206,63 226,96 176,96']],
    wheels: [{ cx: 104, cy: 152, r: 32 }, { cx: 300, cy: 152, r: 32 }],
  },
};

const DEFAULT_HEX = '#c3c7cc';

const resolveHex = (car) => {
  if (car?.colorHex) return car.colorHex;
  const match = CAR_COLORS.find(c => c.name === car?.color);
  return match ? match.hex : DEFAULT_HEX;
};

// Perhalus / pergelap warna untuk gradien bodi
const shade = (hex, amount) => {
  const clean = String(hex || DEFAULT_HEX).replace('#', '');
  const full = clean.length === 3 ? clean.split('').map(c => c + c).join('') : clean.padEnd(6, '0');
  const num = parseInt(full.slice(0, 6), 16);
  if (Number.isNaN(num)) return DEFAULT_HEX;
  const channel = (shift) => {
    const value = (num >> shift) & 255;
    const next = amount >= 0
      ? value + (255 - value) * amount
      : value * (1 + amount);
    return Math.max(0, Math.min(255, Math.round(next)));
  };
  return `#${[channel(16), channel(8), channel(0)].map(v => v.toString(16).padStart(2, '0')).join('')}`;
};

function CarIllustration({ car }) {
  const uid = useId().replace(/:/g, '');
  const shape = SHAPES[car?.bodyType] || SHAPES.MPV;
  const hex = resolveHex(car);
  const light = shade(hex, 0.35);
  const dark = shade(hex, -0.45);
  const label = [car?.brand, car?.model].filter(Boolean).join(' ');

  return (
    <svg
      viewBox="0 0 400 200"
      preserveAspectRatio="xMidYMid meet"
      className="absolute inset-0 h-full w-full"
      role="img"
      aria-label={label ? `Ilustrasi ${label}` : 'Ilustrasi mobil'}
    >
      <defs>
        <linearGradient id={`bg-${uid}`} x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor="#242424" />
          <stop offset="100%" stopColor="#0a0a0a" />
        </linearGradient>
        <linearGradient id={`body-${uid}`} x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor={light} />
          <stop offset="55%" stopColor={hex} />
          <stop offset="100%" stopColor={dark} />
        </linearGradient>
        <linearGradient id={`glass-${uid}`} x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="#3f4650" />
          <stop offset="100%" stopColor="#14181d" />
        </linearGradient>
      </defs>

      <rect width="400" height="200" fill={`url(#bg-${uid})`} />
      {/* Aksen diagonal merah khas Auto18 */}
      <polygon points="300,0 400,0 400,40 236,200 132,200" fill="#e41b1b" opacity="0.22" />
      <polygon points="392,0 400,0 400,14 288,200 268,200" fill="#e41b1b" opacity="0.35" />

      {/* Bayangan bawah mobil */}
      <ellipse cx="200" cy="176" rx="150" ry="9" fill="#000000" opacity="0.55" />

      {/* Bodi */}
      <path d={shape.body} fill={`url(#body-${uid})`} stroke="#000000" strokeOpacity="0.55" strokeWidth="2" />
      <polygon points={shape.cabin} fill={`url(#body-${uid})`} stroke="#000000" strokeOpacity="0.55" strokeWidth="2" />
      {shape.glass.map((points, i) => (
        <polygon key={i} points={points} fill={`url(#glass-${uid})`} opacity="0.95" />
      ))}

      {/* Lampu */}
      <rect x="352" y="112" width="20" height="10" rx="4" fill="#fde68a" opacity="0.9" />
      <rect x="28" y="112" width="16" height="9" rx="4" fill="#e41b1b" opacity="0.9" />

      {/* Roda */}
      {shape.wheels.map((w, i) => (
        <g key={i}>
          <circle cx={w.cx} cy={w.cy} r={w.r} fill="#0d0d0d" />
          <circle cx={w.cx} cy={w.cy} r={w.r * 0.52} fill="#9aa0a6" />
          <circle cx={w.cx} cy={w.cy} r={w.r * 0.2} fill="#3c4046" />
        </g>
      ))}

      {label && (
        <text
          x="20"
          y="192"
          fill="#ffffff"
          fillOpacity="0.55"
          fontSize="13"
          fontWeight="800"
          letterSpacing="2"
          fontFamily="Montserrat, system-ui, sans-serif"
        >
          {label.toUpperCase()}
        </text>
      )}
    </svg>
  );
}

/**
 * Foto mobil dengan fallback ilustrasi.
 * Ukuran ditentukan pemanggil lewat className, mis. "aspect-[16/10] w-full rounded-xl".
 */
export default function CarImage({ car, className = '', imgClassName = '' }) {
  const src = car?.imageUrl || CAR_IMAGES[car?.imageKey] || '';
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    setFailed(false);
  }, [src]);

  return (
    <div className={`relative overflow-hidden bg-ink-900 ${className}`}>
      {src && !failed ? (
        <img
          src={src}
          alt={getCarName(car, { withYear: true }) || 'Foto mobil'}
          loading="lazy"
          onError={() => setFailed(true)}
          className={`absolute inset-0 h-full w-full object-cover ${imgClassName}`}
        />
      ) : (
        <CarIllustration car={car} />
      )}
    </div>
  );
}
