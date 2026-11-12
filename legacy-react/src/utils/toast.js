// Notifikasi toast Auto18 — pengganti kotak alert() abu-abu bawaan browser.
//
// Store kecil di level modul (tanpa React), jadi bisa dipanggil dari mana saja:
// komponen, AppContext, atau event handler biasa. <Toaster /> membacanya lewat
// subscribeToasts/getToasts (useSyncExternalStore).
//
//   toast.success('Pengaturan tersimpan!')  → id
//   toast.error('Stok habis!')              → id
//   toast.info('Data dimuat ulang.')        → id
//   toast.dismiss(id)
//
// Aturan:
// - Pesan + jenis yang sama persis dan masih tampil tidak ditumpuk: timernya diulang
//   dari awal dan nilai `bump` naik (Toaster memberi denyut kecil sebagai tanda).
// - Maksimal 3 toast tampil; yang paling lama dibuang lebih dulu.
// - Hilang otomatis: 3,5 detik (success/info), 5 detik (error). Semua timer dijeda
//   selama salah satu toast disorot kursor atau berisi fokus keyboard.

const DURATION = { success: 3500, info: 3500, error: 5000 };
const MAX_VISIBLE = 3;
// Setelah jeda berakhir, sisakan waktu minimal ini supaya toast tidak langsung lenyap
// begitu kursor bergeser keluar.
const MIN_REMAINING_AFTER_PAUSE = 1000;

let toasts = []; // snapshot immutable: [{ id, type, message, bump }]
let nextId = 1;
const listeners = new Set();
const timers = new Map(); // id -> { remaining, startedAt, timeoutId }
const holds = new Set(); // alasan jeda yang aktif, mis. "toast-3:hover", "toast-3:focus"

const emit = () => {
  listeners.forEach((listener) => listener());
};

const isPaused = () => holds.size > 0;

function startTimer(id) {
  const timer = timers.get(id);
  if (!timer || isPaused()) return;
  clearTimeout(timer.timeoutId);
  timer.startedAt = Date.now();
  timer.timeoutId = setTimeout(() => dismissToast(id), timer.remaining);
}

function stopTimer(id) {
  const timer = timers.get(id);
  if (!timer || timer.timeoutId === null) return;
  clearTimeout(timer.timeoutId);
  timer.timeoutId = null;
  timer.remaining = Math.max(
    MIN_REMAINING_AFTER_PAUSE,
    timer.remaining - (Date.now() - timer.startedAt)
  );
}

function clearTimer(id) {
  const timer = timers.get(id);
  if (timer) clearTimeout(timer.timeoutId);
  timers.delete(id);
}

// Pasang (atau ulang dari awal) timer penuh sesuai jenis toast
function resetTimer(id, type) {
  clearTimer(id);
  timers.set(id, { remaining: DURATION[type] ?? DURATION.info, startedAt: 0, timeoutId: null });
  startTimer(id);
}

function releaseHoldsOf(id) {
  const prefix = id + ':';
  let released = false;
  holds.forEach((key) => {
    if (key.startsWith(prefix)) {
      holds.delete(key);
      released = true;
    }
  });
  if (released && !isPaused()) timers.forEach((_, timerId) => startTimer(timerId));
}

function show(type, message) {
  const text = String(message ?? '');

  // Pesan kembar yang masih tampil: jangan menumpuk salinan, cukup ulang timernya
  const existing = toasts.find((t) => t.type === type && t.message === text);
  if (existing) {
    toasts = toasts.map((t) => (t.id === existing.id ? { ...t, bump: t.bump + 1 } : t));
    resetTimer(existing.id, type);
    emit();
    return existing.id;
  }

  const id = 'toast-' + nextId++;
  const overflow = Math.max(0, toasts.length + 1 - MAX_VISIBLE);
  toasts.slice(0, overflow).forEach((t) => {
    clearTimer(t.id);
    releaseHoldsOf(t.id);
  });
  toasts = [...toasts.slice(overflow), { id, type, message: text, bump: 0 }];
  resetTimer(id, type);
  emit();
  return id;
}

export function dismissToast(id) {
  clearTimer(id);
  releaseHoldsOf(id);
  const next = toasts.filter((t) => t.id !== id);
  if (next.length === toasts.length) return;
  toasts = next;
  emit();
}

// --- Untuk <Toaster /> -------------------------------------------------------

export function subscribeToasts(listener) {
  listeners.add(listener);
  return () => {
    listeners.delete(listener);
  };
}

export function getToasts() {
  return toasts;
}

// Jeda semua timer selama ada minimal satu "hold" (hover / fokus pada sebuah toast)
export function holdToasts(key) {
  if (holds.has(key)) return;
  const wasPaused = isPaused();
  holds.add(key);
  if (!wasPaused) timers.forEach((_, id) => stopTimer(id));
}

export function releaseToasts(key) {
  if (!holds.delete(key)) return;
  if (!isPaused()) timers.forEach((_, id) => startTimer(id));
}

export const toast = {
  success: (message) => show('success', message),
  error: (message) => show('error', message),
  info: (message) => show('info', message),
  dismiss: dismissToast,
};

export default toast;
