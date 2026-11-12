// Rumus yang sama dengan App\Services\CarPricing (PHP), hanya untuk pratinjau di browser.
// Nilai yang disimpan ke database selalu dihitung ulang oleh PHP.

const num = (v) => {
    const n = Number(v);
    return Number.isFinite(n) ? n : 0;
};

export const rupiah = (value) => 'Rp ' + Math.round(num(value)).toLocaleString('id-ID');

export function rupiahShort(value) {
    const n = num(value);
    if (n >= 1_000_000_000) return `Rp ${(n / 1_000_000_000).toLocaleString('id-ID', { maximumFractionDigits: 2 })} M`;
    if (n >= 1_000_000) return `Rp ${(n / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} jt`;
    if (n >= 1_000) return `Rp ${Math.round(n / 1_000).toLocaleString('id-ID')} rb`;
    return `Rp ${n.toLocaleString('id-ID')}`;
}

export function installment({ price, dpPercent, tenorMonths, annualRate }) {
    const safePrice = Math.max(0, num(price));
    const dp = Math.min(100, Math.max(0, num(dpPercent)));
    const tenor = Math.max(1, num(tenorMonths) || 1);
    const rate = Math.max(0, num(annualRate));

    const dpAmount = Math.round(safePrice * (dp / 100));
    const principal = Math.round(safePrice) - dpAmount;
    const totalInterest = Math.round(principal * (rate / 100) * (tenor / 12));
    const monthly = Math.ceil((principal + totalInterest) / tenor / 1000) * 1000;
    const totalPayment = dpAmount + monthly * Math.floor(tenor);

    return { dpAmount, principal, totalInterest, monthly, totalPayment };
}

// Tabel harga dikirim dari PHP (CarPricing::tables()) supaya tidak ditulis dua kali.
export function estimate(car, tables, now = new Date()) {
    if (!tables) return { low: 0, high: 0, mid: 0 };

    const currentYear = now.getFullYear();
    const age = Math.max(0, currentYear - (num(car.year) || currentYear));

    const modelBase = tables.modelBase[String(car.model || '').trim().toLowerCase()];
    const newPrice = modelBase || (tables.bodyBase[car.body_type] || 300_000_000) * (tables.premium[car.brand] || 1);
    const base = newPrice * (tables.brandFactor[car.brand] || 0.92);

    let value = age === 0 ? base : base * 0.88 * Math.pow(0.94, age - 1);
    value = Math.max(value, base * 0.25);

    const expectedKm = Math.max(1, age) * 15_000;
    const kmDiff = (num(car.mileage) - expectedKm) / 10_000;
    value *= Math.min(1.06, Math.max(0.8, 1 - kmDiff * 0.015));

    if (car.transmission === 'Otomatis') value *= 1.04;
    value *= tables.condition[car.condition] || 1;

    const mid = value * 0.85;
    const step = 500_000;
    return {
        low: Math.round((mid * 0.94) / step) * step,
        high: Math.round((mid * 1.06) / step) * step,
        mid: Math.round(mid / step) * step,
    };
}
