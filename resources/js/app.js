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

// ---------------------------------------------------------------------------
// Kasir aksesoris: keranjang di browser, dikirim sebagai form biasa. Server menghitung ulang
// harga, diskon, pajak, dan stok. Keranjang disimpan di sessionStorage supaya tidak hilang
// kalau checkout ditolak; halaman nota mengosongkannya setelah transaksi berhasil.
// ---------------------------------------------------------------------------
const POS_CART_KEY = 'auto18_pos_cart';

Alpine.data('pos', ({ products, taxEnabled, taxRate }) => ({
    products,
    taxEnabled,
    taxRate: Number(taxRate),
    search: '',
    category: 'all',
    cart: [],
    discountType: 'nominal',
    discountValue: '',
    payOpen: false,
    method: 'cash',
    cash: '',
    customer: 'Pelanggan Umum',
    bank: 'BCA',
    rupiah,

    init() {
        try {
            const saved = JSON.parse(sessionStorage.getItem(POS_CART_KEY) || 'null');
            if (saved && Array.isArray(saved.cart)) {
                // Hanya barang yang masih ada, dan jumlahnya tidak melebihi stok terbaru
                this.cart = saved.cart
                    .map((line) => ({ id: line.id, qty: Math.min(Number(line.qty) || 0, this.product(line.id)?.stock ?? 0) }))
                    .filter((line) => line.qty > 0);
                this.discountType = saved.discountType === 'percent' ? 'percent' : 'nominal';
                this.discountValue = saved.discountValue ?? '';
            }
        } catch (e) {
            /* penyimpanan diblokir: keranjang hanya hidup selama halaman terbuka */
        }
        this.$watch('cart', () => this.persist());
        this.$watch('discountType', () => this.persist());
        this.$watch('discountValue', () => this.persist());
    },

    persist() {
        try {
            sessionStorage.setItem(POS_CART_KEY, JSON.stringify({ cart: this.cart, discountType: this.discountType, discountValue: this.discountValue }));
        } catch (e) {
            /* abaikan */
        }
    },

    product(id) {
        return this.products.find((p) => p.id === id);
    },
    qtyOf(id) {
        return this.cart.find((line) => line.id === id)?.qty || 0;
    },
    countIn(categoryId) {
        return this.products.filter((p) => p.category_id === categoryId).length;
    },

    get filtered() {
        const q = this.search.trim().toLowerCase();
        return this.products.filter((p) => {
            const inCategory = this.category === 'all' || p.category_id === this.category;
            const matches = !q || p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q) || (p.barcode && p.barcode.includes(q));
            return inCategory && matches;
        });
    },
    get lines() {
        return this.cart.map((line) => ({ ...line, product: this.product(line.id) })).filter((line) => line.product);
    },
    get count() {
        return this.cart.reduce((sum, line) => sum + line.qty, 0);
    },
    get subtotal() {
        return this.lines.reduce((sum, line) => sum + line.product.sell_price * line.qty, 0);
    },
    get discount() {
        const v = Number(this.discountValue);
        const value = Number.isFinite(v) ? v : 0;
        const raw = this.discountType === 'percent' ? Math.round((this.subtotal * Math.min(100, Math.max(0, value))) / 100) : Math.max(0, value);
        return Math.min(this.subtotal, raw);
    },
    get tax() {
        return this.taxEnabled ? Math.round(Math.max(0, this.subtotal - this.discount) * (this.taxRate / 100)) : 0;
    },
    get total() {
        return Math.max(0, this.subtotal - this.discount + this.tax);
    },
    get cashNumber() {
        return Number(this.cash) || 0;
    },
    get cashEnough() {
        return this.cashNumber >= this.total;
    },
    get quickCash() {
        const total = this.total;
        const up = (step) => Math.ceil(total / step) * step;
        const extra = [...new Set([50000, 100000, 500000, 1000000].map(up))].filter((v) => v > total);
        return [{ label: 'Uang Pas', value: total }, ...extra.map((v) => ({ label: rupiah(v), value: v }))];
    },
    get account() {
        return { BCA: '8830-1234-56', Mandiri: '1310-0099-8822', BRI: '0021-9988-7766-50' }[this.bank];
    },

    toast(message) {
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message } }));
    },

    add(p) {
        if (p.stock <= 0) return this.toast('Maaf, stok barang ini sedang habis!');
        const line = this.cart.find((l) => l.id === p.id);
        if (line) {
            if (line.qty >= p.stock) return this.toast(`Jumlah melebihi stok yang tersedia (${p.stock})`);
            line.qty += 1;
        } else {
            this.cart.push({ id: p.id, qty: 1 });
        }
    },
    setQty(id, qty) {
        if (qty <= 0) return this.remove(id);
        const p = this.product(id);
        if (p && qty > p.stock) return this.toast(`Jumlah melebihi sisa stok (${p.stock})`);
        const line = this.cart.find((l) => l.id === id);
        if (line) line.qty = qty;
    },
    remove(id) {
        this.cart = this.cart.filter((l) => l.id !== id);
    },
    clear() {
        this.cart = [];
        this.discountType = 'nominal';
        this.discountValue = '';
    },
    openPay() {
        this.method = 'cash';
        this.cash = '';
        this.payOpen = true;
        this.$nextTick(() => this.$refs.cash?.focus());
    },
    submit(event) {
        if (this.method === 'cash' && !this.cashEnough) {
            event.preventDefault();
            this.toast('Nominal uang bayar masih kurang!');
        }
    },
}));

Alpine.start();
