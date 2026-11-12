// Daftar tab aplikasi. id juga dipakai sebagai hash URL (mis. #buy-car, #cars).

// Halaman untuk pembeli / penjual mobil (ala marketplace)
export const PUBLIC_TABS = [
  { id: 'buy-car', label: 'Beli Mobil' },
  { id: 'sell-car', label: 'Jual Mobil' },
];

// Panel internal showroom
export const ADMIN_TABS = [
  { id: 'dashboard', label: 'Dashboard' },
  { id: 'cars', label: 'Stok Mobil' },
  { id: 'pos', label: 'Kasir Aksesoris' },
  { id: 'products', label: 'Produk & Stok' },
  { id: 'suppliers', label: 'Suplier' },
  { id: 'reports', label: 'Laporan' },
];

export const ALL_TAB_IDS = [...PUBLIC_TABS, ...ADMIN_TABS].map((t) => t.id);

export const DEFAULT_TAB = 'buy-car';
