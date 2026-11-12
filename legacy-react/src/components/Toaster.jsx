import React, { useEffect, useRef, useState, useSyncExternalStore } from 'react';
import { AnimatePresence, motion, useAnimate } from 'motion/react';
import { CircleAlert, CircleCheck, Info, X } from 'lucide-react';
import {
  dismissToast,
  getToasts,
  holdToasts,
  releaseToasts,
  subscribeToasts
} from '../utils/toast';

// Masuk: pegas singkat tanpa banyak pantulan. Keluar: pudar cepat.
const SPRING = { type: 'spring', bounce: 0.15, duration: 0.35 };
const ENTER_TRANSITION = { ...SPRING, opacity: { duration: 0.2, ease: 'easeOut' } };
const EXIT_TRANSITION = { duration: 0.18, ease: 'easeIn' };

// Aksen kiri + ikon sesuai jenis toast
const TONE = {
  success: { Icon: CircleCheck, accent: 'bg-emerald-500', icon: 'text-emerald-400' },
  error: { Icon: CircleAlert, accent: 'bg-brand-600', icon: 'text-brand-500' },
  info: { Icon: Info, accent: 'bg-neutral-400', icon: 'text-neutral-300' },
};

// Posisi: tengah atas di semua ukuran layar. Pojok kanan bawah (desktop) menutupi
// tombol "Bayar Sekarang" dan diskon di keranjang Kasir, dan bawah layar ponsel dipakai
// bar bayar yang menempel; bagian tengah header justru ruang kosong.
const OFFSET_Y = -16; // muncul dari tepi atas

function ToastItem({ toast, offsetY }) {
  const { id, type, message, bump } = toast;
  const tone = TONE[type] || TONE.info;
  const Icon = tone.Icon;
  const [scope, animate] = useAnimate();
  const lastBump = useRef(bump);

  // Pesan yang sama dipicu lagi (mis. klik "tambah" berulang pada barang yang stoknya
  // habis): toast tidak digandakan, cukup berdenyut kecil sebagai tanda klik tercatat.
  useEffect(() => {
    if (bump === lastBump.current) return;
    lastBump.current = bump;
    if (!scope.current) return;
    animate(scope.current, { scale: [1, 1.03, 1] }, { duration: 0.24, ease: 'easeOut' });
  }, [bump, animate, scope]);

  // Lepas jeda hover/fokus milik toast ini ketika ia benar-benar hilang dari DOM
  // (elemen yang dihapus tidak selalu memicu pointerleave/blur).
  useEffect(() => () => {
    releaseToasts(id + ':hover');
    releaseToasts(id + ':focus');
  }, [id]);

  const handleBlur = (e) => {
    if (!e.currentTarget.contains(e.relatedTarget)) releaseToasts(id + ':focus');
  };

  return (
    <motion.div
      layout="position"
      initial={{ opacity: 0, y: offsetY }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: offsetY / 2, transition: EXIT_TRANSITION }}
      transition={ENTER_TRANSITION}
      onPointerEnter={() => holdToasts(id + ':hover')}
      onPointerLeave={() => releaseToasts(id + ':hover')}
      onFocus={() => holdToasts(id + ':focus')}
      onBlur={handleBlur}
      className="pointer-events-auto w-full sm:w-[22rem]"
    >
      <div
        ref={scope}
        className="relative flex items-start gap-3 overflow-hidden rounded-xl border border-white/10 bg-ink-800 py-3 pl-4 pr-2 shadow-lg shadow-black/50"
      >
        <span aria-hidden="true" className={`absolute inset-y-0 left-0 w-1 ${tone.accent}`} />
        <Icon aria-hidden="true" className={`mt-0.5 h-5 w-5 shrink-0 ${tone.icon}`} />
        {/* Teks biasa: pengumuman ke pembaca layar lewat live region tetap di Toaster */}
        <p
          data-toast-type={type}
          className="min-w-0 flex-1 break-words pt-px text-sm font-semibold leading-snug text-white"
        >
          {message}
        </p>
        <button
          type="button"
          onClick={() => dismissToast(id)}
          aria-label="Abaikan notifikasi"
          className="-my-1 shrink-0 rounded-lg p-1.5 text-neutral-400 transition-colors hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
        >
          <X aria-hidden="true" className="h-4 w-4" />
        </button>
      </div>
    </motion.div>
  );
}

/**
 * Tumpukan toast global (lihat src/utils/toast.js). Dirender sekali di App, di dalam
 * MotionConfig — gerakan geser/layout otomatis mati untuk pengguna "kurangi gerakan",
 * tersisa pudar opacity; posisi akhirnya tetap sama.
 */
export default function Toaster() {
  const toasts = useSyncExternalStore(subscribeToasts, getToasts, getToasts);

  // Pembaca layar sering tidak membacakan live region yang dibuat bersamaan dengan
  // teksnya. Jadi dua region (sopan & segera) selalu terpasang dan mulai kosong; tiap
  // toast baru — atau toast yang sama diulang (bump) — dikosongkan dulu lalu diisi.
  const [announce, setAnnounce] = useState({ polite: '', assertive: '' });
  const seenRef = useRef(new Map());
  useEffect(() => {
    let latest = null;
    for (const t of toasts) {
      if (seenRef.current.get(t.id) !== t.bump) latest = t;
    }
    seenRef.current = new Map(toasts.map((t) => [t.id, t.bump]));
    if (!latest) return undefined;
    const channel = latest.type === 'error' ? 'assertive' : 'polite';
    setAnnounce((a) => ({ ...a, [channel]: '' }));
    const timer = setTimeout(() => setAnnounce((a) => ({ ...a, [channel]: latest.message })), 60);
    return () => clearTimeout(timer);
  }, [toasts]);

  return (
    <section
      aria-label="Notifikasi"
      // z-[100]: di atas semua modal (modal nota memakai z-[60])
      className="pointer-events-none fixed inset-x-0 top-0 z-[100] flex justify-center p-3 sm:p-4"
    >
      <div className="sr-only" role="status" aria-live="polite" aria-atomic="true">{announce.polite}</div>
      <div className="sr-only" role="alert" aria-atomic="true">{announce.assertive}</div>
      {/* Terbaru di paling atas (flex-col-reverse) */}
      <div className="flex w-full max-w-sm flex-col-reverse gap-2 sm:w-auto sm:max-w-none sm:items-center">
        <AnimatePresence initial={false}>
          {toasts.map((t) => (
            <ToastItem key={t.id} toast={t} offsetY={OFFSET_Y} />
          ))}
        </AnimatePresence>
      </div>
    </section>
  );
}
