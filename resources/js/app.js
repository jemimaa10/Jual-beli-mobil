import Alpine from 'alpinejs';
import { estimate, installment, rupiah, rupiahShort } from './pricing';

window.Alpine = Alpine;
window.auto18 = { estimate, installment, rupiah, rupiahShort };

// ---------------------------------------------------------------------------
// Simulasi kredit (kartu di katalog & detail mobil)
// ---------------------------------------------------------------------------
Alpine.data('creditSimulator', ({ price = 0, minDp = 20, rate = 7, tenor = 36 } = {}) => ({
    price,
    minDp: Math.min(70, Number(minDp)),
    sliderMax: Math.max(70, Number(minDp)),
    dp: Math.max(Number(minDp), 20),
    tenor,
    rate: Number(rate),
    tenors: [12, 24, 36, 48, 60],
    rupiah,

    get result() {
        return installment({ price: this.price, dpPercent: this.dp, tenorMonths: this.tenor, annualRate: this.rate });
    },

    setRate(value) {
        const n = Number(value);
        this.rate = Number.isFinite(n) ? Math.min(30, Math.max(0, n)) : 0;
    },
}));

// ---------------------------------------------------------------------------
// Favorit mobil: satu-satunya data yang disimpan di browser (tanpa akun pembeli)
// ---------------------------------------------------------------------------
const FAVORITES_KEY = 'auto18_favoriteCarIds';

const readFavorites = () => {
    try {
        const raw = JSON.parse(localStorage.getItem(FAVORITES_KEY) || '[]');
        return Array.isArray(raw) ? raw.map(Number).filter(Number.isFinite) : [];
    } catch (e) {
        return [];
    }
};

Alpine.store('favorites', {
    ids: readFavorites(),

    get count() {
        return this.ids.length;
    },

    has(id) {
        return this.ids.includes(Number(id));
    },

    toggle(id) {
        id = Number(id);
        this.ids = this.has(id) ? this.ids.filter((x) => x !== id) : [...this.ids, id];
        try {
            localStorage.setItem(FAVORITES_KEY, JSON.stringify(this.ids));
        } catch (e) {
            /* mode privat / penyimpanan diblokir: favorit hanya bertahan selama halaman terbuka */
        }
    },
});

// ---------------------------------------------------------------------------
// Toast: pesan flash dari server atau window.dispatchEvent(new CustomEvent('toast', ...))
// ---------------------------------------------------------------------------
Alpine.data('toaster', (initial = []) => ({
    items: [],
    seq: 0,

    init() {
        initial.forEach((t) => this.add(t.type, t.message));
    },

    add(type, message) {
        if (!message) return;
        const existing = this.items.find((t) => t.open && t.message === message && t.type === type);
        if (existing) {
            this.restart(existing);
            return;
        }
        const toast = { id: ++this.seq, type: type || 'info', message, open: true, timer: null };
        this.items.push(toast);
        this.restart(this.items[this.items.length - 1]);
    },

    restart(toast) {
        clearTimeout(toast.timer);
        toast.timer = setTimeout(() => this.dismiss(toast), toast.type === 'error' ? 6000 : 4000);
    },

    hold(toast) {
        clearTimeout(toast.timer);
    },

    release(toast) {
        this.restart(toast);
    },

    dismiss(toast) {
        clearTimeout(toast.timer);
        toast.open = false;
        setTimeout(() => {
            this.items = this.items.filter((t) => t.id !== toast.id);
        }, 250);
    },
}));

// ---------------------------------------------------------------------------
// Formulir Jual Mobil (3 langkah). Server tetap memvalidasi & menghitung ulang semuanya.
// ---------------------------------------------------------------------------
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

Alpine.data('sellForm', ({ type, step, values, tables, cars, today, serverErrors }) => ({
    type,
    step,
    f: values,
    cars,
    today,
    errors: {},
    serverErrors,
    rupiah,

    get estimate() {
        return estimate(this.f, tables);
    },
    get target() {
        return this.cars.find((c) => String(c.id) === String(this.f.trade_in_car_id)) || null;
    },
    get gap() {
        return this.target ? Math.max(0, this.target.price - this.estimate.mid) : 0;
    },
    get km() {
        return Number(this.f.mileage || 0).toLocaleString('id-ID');
    },

    err(key) {
        return this.errors[key] || this.serverErrors[key] || '';
    },

    validate(step) {
        const e = {};
        if (step === 1) {
            if (!String(this.f.model).trim()) e.model = 'Model mobil wajib diisi.';
            if (this.f.mileage === '' || Number(this.f.mileage) < 0) e.mileage = 'Kilometer wajib diisi.';
            else if (Number(this.f.mileage) > 1000000) e.mileage = 'Kilometer tidak wajar.';
        }
        if (step === 2) {
            if (!this.f.inspection_date) e.inspection_date = 'Tanggal inspeksi wajib dipilih.';
            else if (this.f.inspection_date < this.today) e.inspection_date = 'Tanggal sudah lewat.';
            if (this.type === 'trade-in' && !this.f.trade_in_car_id) e.trade_in_car_id = 'Pilih mobil yang ingin ditukar.';
        }
        if (step === 3) {
            if (!String(this.f.customer_name).trim()) e.customer_name = 'Nama wajib diisi.';
            if (!/^(\+62|62|0)8\d{7,11}$/.test(String(this.f.phone).trim().replace(/[\s().-]/g, ''))) e.phone = 'Nomor HP tidak valid.';
            if (String(this.f.email).trim() && !EMAIL_RE.test(String(this.f.email).trim())) e.email = 'Email tidak valid.';
        }
        this.errors = e;
        return Object.keys(e).length === 0;
    },

    next() {
        this.serverErrors = {};
        if (this.validate(this.step)) this.step = Math.min(3, this.step + 1);
    },

    back() {
        this.step = Math.max(1, this.step - 1);
    },

    submit(event) {
        this.serverErrors = {};
        for (const s of [1, 2, 3]) {
            if (!this.validate(s)) {
                this.step = s;
                event.preventDefault();
                return;
            }
        }
    },
}));

// ---------------------------------------------------------------------------
// Form "Proses Penjualan" mobil: ringkasan langsung. Server menghitung & memvalidasi ulang semuanya.
// ---------------------------------------------------------------------------
Alpine.data('carSaleForm', ({ price, buyPrice, minDp, rate, requests, old }) => ({
    price,
    buyPrice,
    minDp: Number(minDp),
    requests,
    method: old.payment_method,
    discount: old.discount,
    paid: old.paid_amount,
    leasing: old.leasing,
    dp: old.dp_percent === '' ? Math.max(Number(minDp), 20) : Number(old.dp_percent),
    tenor: Number(old.tenor_months),
    rate: old.annual_rate === '' ? Number(rate) : Number(old.annual_rate),
    tenors: [12, 24, 36, 48, 60],
    useTradeIn: old.use_trade_in,
    requestId: old.trade_in_request_id,
    tradeName: old.trade_in_car_name,
    tradeValue: old.trade_in_value,
    rupiah,

    get safeDiscount() {
        const d = Number(this.discount);
        return Math.min(this.price, Math.max(0, Number.isFinite(d) ? d : 0));
    },
    get total() {
        return this.price - this.safeDiscount;
    },
    get appliedTradeIn() {
        const t = Number(this.tradeValue);
        return this.useTradeIn ? Math.min(this.total, Math.max(0, Number.isFinite(t) ? t : 0)) : 0;
    },
    get amountDue() {
        return this.total - this.appliedTradeIn;
    },
    get received() {
        return this.paid === '' || this.paid === null ? this.amountDue : Number(this.paid);
    },
    get change() {
        return Math.max(0, (Number.isFinite(this.received) ? this.received : 0) - this.amountDue);
    },
    get sim() {
        return installment({ price: this.amountDue, dpPercent: this.dp, tenorMonths: this.tenor, annualRate: this.rate });
    },
    get profit() {
        return this.total - this.buyPrice;
    },
    get quickAmounts() {
        const due = this.amountDue;
        return [...new Set([due, Math.ceil(due / 1000000) * 1000000 + 1000000, Math.ceil(due / 5000000) * 5000000 + 5000000])];
    },

    pickRequest(id) {
        this.requestId = id;
        const r = this.requests.find((x) => String(x.id) === String(id));
        this.tradeName = r ? r.name : '';
        this.tradeValue = r ? r.value : '';
    },
    setRate(value) {
        const n = Number(value);
        this.rate = Number.isFinite(n) ? Math.min(30, Math.max(0, n)) : 0;
    },
}));

Alpine.start();
