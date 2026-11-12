import Alpine from 'alpinejs';

window.Alpine = Alpine;

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

Alpine.start();
