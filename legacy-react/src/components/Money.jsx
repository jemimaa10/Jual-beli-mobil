import React from 'react';
import NumberFlow from '@number-flow/react';
import { formatRupiah } from '../utils/formatters';

// Format yang sama persis dengan formatRupiah(): id-ID, IDR, tanpa desimal ("Rp 3.942.000").
const LOCALES = 'id-ID';
const FORMAT = {
  style: 'currency',
  currency: 'IDR',
  minimumFractionDigits: 0,
  maximumFractionDigits: 0,
};

// Bawaan NumberFlow (900 ms) terasa lamban untuk kasir; digit cukup berputar cepat lalu mengendap.
const TRANSFORM_TIMING = { duration: 450, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' };
const OPACITY_TIMING = { duration: 180, easing: 'ease-out' };

const toAmount = (value) => {
  const n = Number(value);
  return Number.isFinite(n) ? n : 0;
};

/**
 * Nominal rupiah di layar yang berubah karena input pengguna: digitnya berputar ke nilai baru.
 * Hanya untuk tampilan — jangan dipakai di struk cetak (#printable-receipt), teks salinan, atau CSV.
 *
 * Digit NumberFlow ada di shadow DOM, jadi teks yang sama (hasil formatRupiah) juga disimpan
 * di span sr-only: pembaca layar membacanya sekali, dan textContent elemen tetap "Rp 3.942.000".
 */
export default function Money({ value, className = '', prefix, suffix, ...rest }) {
  const amount = toAmount(value);
  const text = `${prefix ?? ''}${formatRupiah(amount)}${suffix ?? ''}`;

  return (
    <span className={className || undefined}>
      <NumberFlow
        value={amount}
        locales={LOCALES}
        format={FORMAT}
        prefix={prefix}
        suffix={suffix}
        transformTiming={TRANSFORM_TIMING}
        opacityTiming={OPACITY_TIMING}
        {...rest}
        aria-hidden="true"
      />
      <span className="sr-only">{text}</span>
    </span>
  );
}
