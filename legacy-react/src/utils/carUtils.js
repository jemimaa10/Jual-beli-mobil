// Utilitas khusus modul jual beli mobil bekas Auto18

// "Rp 185 jt", "Rp 1,25 M" — untuk kartu listing yang ringkas
export function formatRupiahShort(value) {
  const n = Number(value) || 0;
  if (n >= 1_000_000_000) {
    const m = n / 1_000_000_000;
    return `Rp ${m.toLocaleString('id-ID', { maximumFractionDigits: 2 })} M`;
  }
  if (n >= 1_000_000) {
    const jt = n / 1_000_000;
    return `Rp ${jt.toLocaleString('id-ID', { maximumFractionDigits: 1 })} jt`;
  }
  if (n >= 1_000) {
    return `Rp ${Math.round(n / 1_000).toLocaleString('id-ID')} rb`;
  }
  return `Rp ${n.toLocaleString('id-ID')}`;
}

// "45.000 km"
export function formatKm(km) {
  const n = Number(km) || 0;
  return `${n.toLocaleString('id-ID')} km`;
}

// "Toyota Fortuner 2.4 VRZ AT"
export function getCarName(car, { withYear = false } = {}) {
  if (!car) return '';
  const base = [car.brand, car.model, car.variant].filter(Boolean).join(' ');
  return withYear && car.year ? `${base} ${car.year}` : base;
}

// Nomor HP Indonesia: 08xx / 628xx / +628xx, boleh memakai spasi, titik, tanda hubung, atau kurung
export function isValidPhone(value) {
  const digits = String(value || '').trim().replace(/[\s().-]/g, '');
  return /^(\+62|62|0)8\d{7,11}$/.test(digits);
}

// Pembulatan ke atas kelipatan tertentu (default Rp 1.000)
export function roundUpTo(value, step = 1000) {
  return Math.ceil((Number(value) || 0) / step) * step;
}

/**
 * Simulasi kredit mobil bekas dengan bunga flat (umum di leasing Indonesia).
 * @param {object} p
 * @param {number} p.price        harga mobil (Rp)
 * @param {number} p.dpPercent    uang muka dalam persen, mis. 25
 * @param {number} p.tenorMonths  lama cicilan dalam bulan, mis. 36
 * @param {number} p.annualRate   bunga flat per tahun dalam persen, mis. 7
 * @returns {{ dpAmount:number, principal:number, totalInterest:number, monthly:number, totalPayment:number }}
 */
export function calcInstallment({ price, dpPercent, tenorMonths, annualRate }) {
  const safePrice = Math.max(0, Number(price) || 0);
  const dp = Math.min(100, Math.max(0, Number(dpPercent) || 0));
  const tenor = Math.max(1, Number(tenorMonths) || 1);
  const rate = Math.max(0, Number(annualRate) || 0);

  const dpAmount = Math.round(safePrice * (dp / 100));
  const principal = safePrice - dpAmount;
  const totalInterest = Math.round(principal * (rate / 100) * (tenor / 12));
  const monthly = roundUpTo((principal + totalInterest) / tenor, 1000);
  const totalPayment = dpAmount + monthly * tenor;

  return { dpAmount, principal, totalInterest, monthly, totalPayment };
}

// Perkiraan harga baru (Rp) model populer — dasar estimasi harga jual
const MODEL_BASE_PRICE = {
  agya: 170_000_000, ayla: 150_000_000, brio: 200_000_000, calya: 160_000_000, sigra: 150_000_000,
  avanza: 260_000_000, xenia: 250_000_000, veloz: 300_000_000, ertiga: 260_000_000, xpander: 290_000_000,
  mobilio: 240_000_000, livina: 280_000_000, rush: 290_000_000, terios: 270_000_000, raize: 260_000_000,
  rocky: 250_000_000, jazz: 270_000_000, yaris: 290_000_000, 'br-v': 320_000_000, 'hr-v': 380_000_000,
  city: 350_000_000, vios: 350_000_000, 'kijang innova': 420_000_000, innova: 420_000_000,
  civic: 520_000_000, 'cr-v': 550_000_000, fortuner: 560_000_000, 'pajero sport': 560_000_000,
  'cx-5': 600_000_000, creta: 350_000_000, almaz: 350_000_000, camry: 700_000_000, alphard: 1_500_000_000,
};

// Perkiraan harga mobil baru per tipe bodi (dipakai bila model tidak dikenal)
const BODY_BASE_PRICE = {
  Hatchback: 230_000_000,
  Sedan: 480_000_000,
  MPV: 300_000_000,
  SUV: 520_000_000,
  'Pick-up': 280_000_000,
};

// Pengali merek: seberapa kuat nilai jual kembali di pasar mobil bekas
const BRAND_FACTOR = {
  Toyota: 1.05,
  Honda: 1.02,
  Mitsubishi: 1.0,
  Suzuki: 0.95,
  Daihatsu: 0.95,
  Nissan: 0.9,
  Hyundai: 0.92,
  Mazda: 0.95,
  Wuling: 0.85,
  BMW: 0.65,
};

// Merek premium yang harga barunya jauh di atas rata-rata tipe bodinya
const PREMIUM_BRAND_MULTIPLIER = { BMW: 2.2 };

const CONDITION_FACTOR = {
  'Sangat Baik': 1.05,
  Baik: 1.0,
  Cukup: 0.88,
  'Perlu Perbaikan': 0.72,
};

/**
 * Estimasi harga beli mobil dari pelanggan (fitur "Jual Mobil").
 * Hanya perkiraan kasar; harga final ditentukan setelah inspeksi.
 * @returns {{ low:number, high:number, mid:number }} dibulatkan ke Rp 500.000
 */
export function estimateCarPrice({ brand, model, bodyType, year, mileage, transmission, condition }) {
  const currentYear = new Date().getFullYear();
  const age = Math.max(0, currentYear - (Number(year) || currentYear));
  const modelBase = MODEL_BASE_PRICE[String(model || '').trim().toLowerCase()];
  const newPrice = modelBase
    || (BODY_BASE_PRICE[bodyType] || 300_000_000) * (PREMIUM_BRAND_MULTIPLIER[brand] || 1);
  const base = newPrice * (BRAND_FACTOR[brand] || 0.92);

  // Depresiasi: 12% di tahun pertama, lalu 6% per tahun, minimal sisa 25%
  let value = age === 0 ? base : base * 0.88 * Math.pow(0.94, age - 1);
  value = Math.max(value, base * 0.25);

  // Koreksi kilometer terhadap rata-rata 15.000 km/tahun
  const km = Number(mileage) || 0;
  const expectedKm = Math.max(1, age) * 15_000;
  const kmDiff = (km - expectedKm) / 10_000;
  value *= Math.min(1.06, Math.max(0.8, 1 - kmDiff * 0.015));

  if (transmission === 'Otomatis') value *= 1.04;
  value *= CONDITION_FACTOR[condition] || 1;

  // Harga beli showroom ada di bawah harga pasar
  const mid = value * 0.85;
  const step = 500_000;
  return {
    low: Math.round((mid * 0.94) / step) * step,
    high: Math.round((mid * 1.06) / step) * step,
    mid: Math.round(mid / step) * step,
  };
}

function datePart() {
  const d = new Date();
  const y = d.getFullYear().toString().slice(-2);
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}${m}${day}`;
}

function rand4() {
  return Math.floor(1000 + Math.random() * 9000);
}

// Kode stok mobil: A18-4821
export function generateCarCode() {
  return `A18-${rand4()}`;
}

// Kode permintaan jual / tukar tambah: JUAL-260916-4821
export function generateSellRequestCode() {
  return `JUAL-${datePart()}-${rand4()}`;
}

// Kode booking test drive: TD-260916-4821
export function generateTestDriveCode() {
  return `TD-${datePart()}-${rand4()}`;
}

// Label & gaya status mobil (dipakai kartu, detail, dan halaman stok)
export const CAR_STATUS = {
  available: { label: 'Tersedia', className: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' },
  booked: { label: 'Dipesan', className: 'bg-amber-500/15 text-amber-300 border-amber-500/30' },
  sold: { label: 'Terjual', className: 'bg-brand-600/20 text-brand-300 border-brand-500/40' },
};

export const SELL_REQUEST_STATUS = {
  new: { label: 'Baru Masuk', className: 'bg-sky-500/15 text-sky-300 border-sky-500/30' },
  scheduled: { label: 'Inspeksi Dijadwalkan', className: 'bg-amber-500/15 text-amber-300 border-amber-500/30' },
  inspected: { label: 'Sudah Diinspeksi', className: 'bg-violet-500/15 text-violet-300 border-violet-500/30' },
  purchased: { label: 'Dibeli Auto18', className: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' },
  rejected: { label: 'Ditolak', className: 'bg-neutral-500/15 text-neutral-400 border-neutral-500/30' },
};

export const TEST_DRIVE_STATUS = {
  pending: { label: 'Menunggu Konfirmasi', className: 'bg-amber-500/15 text-amber-300 border-amber-500/30' },
  confirmed: { label: 'Terkonfirmasi', className: 'bg-sky-500/15 text-sky-300 border-sky-500/30' },
  done: { label: 'Selesai', className: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' },
  cancelled: { label: 'Dibatalkan', className: 'bg-neutral-500/15 text-neutral-400 border-neutral-500/30' },
};

// Label metode pembayaran (retail & mobil)
export const PAYMENT_METHOD_LABEL = {
  cash: 'Tunai',
  qris: 'QRIS',
  transfer: 'Transfer',
  credit: 'Kredit',
};
