// ============================================================================
// Data awal Auto18 — Showroom Jual / Beli Mobil Second
// ============================================================================

// Helper tanggal relatif terhadap hari ini supaya dashboard & laporan selalu "hidup"
const today = new Date();
const y = today.getFullYear();
const m = today.getMonth();
const d = today.getDate();

// ISO timestamp untuk (hari ini + dayOffset) pukul h:min waktu lokal
const at = (dayOffset, h, min = 0) => new Date(y, m, d + dayOffset, h, min).toISOString();

// 'YYYY-MM-DD' waktu lokal untuk (hari ini + dayOffset)
const dateStr = (dayOffset) => {
  const t = new Date(y, m, d + dayOffset);
  return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}-${String(t.getDate()).padStart(2, '0')}`;
};

// ---------------------------------------------------------------------------
// Profil showroom
// ---------------------------------------------------------------------------
export const INITIAL_STORE_INFO = {
  name: 'Auto18',
  tagline: 'Jual / Beli Mobil Second Berkualitas Hanya di Auto 18',
  address: 'Showroom Auto18, Jakarta',
  phone: '021-31172266',
  callCenter: '021-31172266',
  whatsapp: '', // format internasional tanpa +, mis. 6281234567890 (opsional)
  email: '',
  instagram: 'auto18eighteen',
  tiktok: 'autoeighteen',
  youtube: 'auto eighteen 18',
  supportPartner: 'LEMONAIDE',
  cashierName: 'Admin Showroom',
  receiptFooter: 'Terima kasih telah berbelanja di Auto18!\nLayanan Darurat 24 Jam: 021-31172266',
  taxEnabled: false,
  taxRate: 11,
  creditRate: 7, // bunga flat per tahun (%) untuk simulasi kredit
  minDpPercent: 20, // DP minimal (%)
};

// ---------------------------------------------------------------------------
// Referensi mobil
// ---------------------------------------------------------------------------
export const CAR_BRANDS = ['Toyota', 'Honda', 'Mitsubishi', 'Suzuki', 'Daihatsu', 'Nissan', 'Hyundai', 'Mazda', 'Wuling', 'BMW'];

export const BODY_TYPES = ['MPV', 'SUV', 'Hatchback', 'Sedan', 'Pick-up'];

export const TRANSMISSIONS = ['Otomatis', 'Manual'];

export const FUEL_TYPES = ['Bensin', 'Diesel', 'Hybrid', 'Listrik'];

export const CAR_CONDITIONS = ['Sangat Baik', 'Baik', 'Cukup', 'Perlu Perbaikan'];

export const CAR_COLORS = [
  { name: 'Putih', hex: '#f4f4f5' },
  { name: 'Hitam', hex: '#111111' },
  { name: 'Silver', hex: '#c3c7cc' },
  { name: 'Abu-abu', hex: '#6b7280' },
  { name: 'Merah', hex: '#c81e1e' },
  { name: 'Biru', hex: '#1e3a8a' },
  { name: 'Hijau', hex: '#1f4d3a' },
  { name: 'Coklat', hex: '#6b4f3a' },
  { name: 'Oranye', hex: '#ea580c' },
];

export const CAR_LOCATIONS = [
  'Jakarta Pusat', 'Jakarta Barat', 'Jakarta Selatan', 'Jakarta Timur', 'Jakarta Utara',
  'Tangerang', 'Tangerang Selatan', 'Bekasi', 'Depok', 'Bogor',
];

// Rentang harga untuk filter (max: null = tanpa batas atas)
export const PRICE_RANGES = [
  { id: 'lt150', label: '< Rp 150 jt', min: 0, max: 150_000_000 },
  { id: '150-250', label: 'Rp 150 – 250 jt', min: 150_000_000, max: 250_000_000 },
  { id: '250-350', label: 'Rp 250 – 350 jt', min: 250_000_000, max: 350_000_000 },
  { id: 'gt350', label: '> Rp 350 jt', min: 350_000_000, max: null },
];

export const TENOR_OPTIONS = [12, 24, 36, 48, 60];

export const LEASING_PARTNERS = ['BCA Finance', 'Adira Finance', 'Mandiri Tunas Finance', 'ACC', 'Leasing Lainnya'];

// Kategori laporan inspeksi (nilai 0–100 per kategori ada di car.inspection)
export const INSPECTION_CATEGORIES = [
  { key: 'mesin', label: 'Mesin & Performa' },
  { key: 'transmisi', label: 'Transmisi & Kopling' },
  { key: 'kakiKaki', label: 'Kaki-kaki & Rem' },
  { key: 'kelistrikan', label: 'Kelistrikan & AC' },
  { key: 'interior', label: 'Interior' },
  { key: 'eksterior', label: 'Eksterior & Rangka' },
  { key: 'dokumen', label: 'Dokumen (BPKB, STNK, Faktur)' },
];

// Buat objek inspeksi dari 7 nilai sesuai urutan INSPECTION_CATEGORIES
const insp = (...scores) =>
  Object.fromEntries(INSPECTION_CATEGORIES.map((c, i) => [c.key, scores[i]]));
const avg = (obj) => {
  const vals = Object.values(obj);
  return Math.round(vals.reduce((a, b) => a + b, 0) / vals.length);
};

// ---------------------------------------------------------------------------
// Stok mobil bekas
// ---------------------------------------------------------------------------
// status: 'available' | 'booked' | 'sold'
// imageKey: kunci foto di src/data/carImages.js; imageUrl: URL foto kustom (opsional)
const RAW_CARS = [
  {
    id: 'car-1', code: 'A18-0001',
    brand: 'Mitsubishi', model: 'Xpander', variant: 'Ultimate 1.5 AT', year: 2021,
    bodyType: 'MPV', transmission: 'Otomatis', fuel: 'Bensin', engineCc: 1499, seats: 7,
    mileage: 42000, color: 'Putih', colorHex: '#f4f4f5', plate: 'B 1821 XPD', location: 'Jakarta Barat',
    price: 219000000, buyPrice: 192000000, status: 'available',
    inspection: insp(97, 96, 95, 98, 94, 95, 100), ownerCount: 1, taxValidUntil: '2027-03', serviceRecord: true,
    features: ['Keyless Entry & Start', 'Kamera Mundur', 'Head Unit Layar Sentuh', 'Cruise Control', 'LED Headlamp'],
    description: 'Tangan pertama dari baru, servis rutin di bengkel resmi. Interior bersih wangi, kaki-kaki senyap, siap pakai mudik.',
    imageKey: 'xpander', imageUrl: '', isFeatured: true, createdAt: at(-2, 10),
  },
  {
    id: 'car-2', code: 'A18-0002',
    brand: 'Toyota', model: 'Fortuner', variant: '2.4 VRZ AT 4x2', year: 2020,
    bodyType: 'SUV', transmission: 'Otomatis', fuel: 'Diesel', engineCc: 2393, seats: 7,
    mileage: 58000, color: 'Hitam', colorHex: '#111111', plate: 'B 1188 FTR', location: 'Jakarta Selatan',
    price: 438000000, buyPrice: 398000000, status: 'available',
    inspection: insp(95, 94, 93, 96, 92, 94, 100), ownerCount: 1, taxValidUntil: '2026-12', serviceRecord: true,
    features: ['Power Back Door', 'Kamera 360°', 'Jok Kulit Elektrik', 'Mode Berkendara Eco/Power', 'Velg 18"'],
    description: 'Fortuner VRZ diesel irit dan bertenaga. Riwayat servis lengkap, ban tebal, bebas banjir dan tabrakan.',
    imageKey: 'fortuner', imageUrl: '', isFeatured: true, createdAt: at(-1, 14),
  },
  {
    id: 'car-3', code: 'A18-0003',
    brand: 'Mitsubishi', model: 'Pajero Sport', variant: 'Dakar 2.4 AT 4x2', year: 2019,
    bodyType: 'SUV', transmission: 'Otomatis', fuel: 'Diesel', engineCc: 2442, seats: 7,
    mileage: 71000, color: 'Putih', colorHex: '#f4f4f5', plate: 'B 1918 PJS', location: 'Tangerang',
    price: 398000000, buyPrice: 362000000, status: 'available',
    inspection: insp(93, 92, 90, 94, 91, 92, 100), ownerCount: 1, taxValidUntil: '2027-01', serviceRecord: true,
    features: ['Sunroof', 'Paddle Shift', 'Blind Spot Warning', 'Jok Kulit', 'Electric Parking Brake'],
    description: 'Pajero Sport Dakar dengan sunroof. Mesin halus, transmisi responsif, kabin terawat dan kedap.',
    imageKey: 'pajero-sport', imageUrl: '', isFeatured: true, createdAt: at(-5, 11),
  },
  {
    id: 'car-4', code: 'A18-0004',
    brand: 'Toyota', model: 'Avanza', variant: '1.5 G CVT', year: 2022,
    bodyType: 'MPV', transmission: 'Otomatis', fuel: 'Bensin', engineCc: 1496, seats: 7,
    mileage: 28000, color: 'Hijau', colorHex: '#1f4d3a', plate: 'B 2218 AVZ', location: 'Bekasi',
    price: 218000000, buyPrice: 196000000, status: 'available',
    inspection: insp(98, 97, 96, 97, 96, 95, 100), ownerCount: 1, taxValidUntil: '2027-06', serviceRecord: true,
    features: ['Toyota Safety Sense', 'Head Unit 9"', 'Kamera Mundur', 'Push Start Button'],
    description: 'Avanza generasi terbaru, kilometer rendah. Masih sangat mulus, cocok untuk keluarga.',
    imageKey: 'avanza', imageUrl: '', isFeatured: false, createdAt: at(-3, 9),
  },
  {
    id: 'car-5', code: 'A18-0005',
    brand: 'Toyota', model: 'Kijang Innova', variant: 'Reborn 2.4 V AT Diesel', year: 2018,
    bodyType: 'MPV', transmission: 'Otomatis', fuel: 'Diesel', engineCc: 2393, seats: 7,
    mileage: 89000, color: 'Silver', colorHex: '#c3c7cc', plate: 'B 1808 INV', location: 'Jakarta Timur',
    price: 348000000, buyPrice: 318000000, status: 'available',
    inspection: insp(92, 91, 90, 93, 90, 91, 100), ownerCount: 2, taxValidUntil: '2026-10', serviceRecord: true,
    features: ['Captain Seat', 'Jok Kulit', 'Double Blower AC', 'Cruise Control'],
    description: 'Innova Reborn tipe V diesel captain seat. Nyaman untuk perjalanan jauh, dokumen lengkap.',
    imageKey: 'innova', imageUrl: '', isFeatured: false, createdAt: at(-8, 15),
  },
  {
    id: 'car-6', code: 'A18-0006',
    brand: 'Honda', model: 'Brio', variant: 'RS CVT', year: 2021,
    bodyType: 'Hatchback', transmission: 'Otomatis', fuel: 'Bensin', engineCc: 1199, seats: 5,
    mileage: 31000, color: 'Merah', colorHex: '#c81e1e', plate: 'B 2118 BRO', location: 'Depok',
    price: 172000000, buyPrice: 152000000, status: 'available',
    inspection: insp(96, 95, 95, 96, 93, 94, 100), ownerCount: 1, taxValidUntil: '2027-02', serviceRecord: true,
    features: ['Body Kit RS', 'Head Unit Touchscreen', 'Velg 15"', 'Rear Spoiler'],
    description: 'Brio RS CVT irit dan lincah untuk harian di kota. Kondisi istimewa, pajak panjang.',
    imageKey: 'brio', imageUrl: '', isFeatured: true, createdAt: at(-1, 10),
  },
  {
    id: 'car-7', code: 'A18-0007',
    brand: 'Honda', model: 'HR-V', variant: '1.5 E CVT', year: 2019,
    bodyType: 'SUV', transmission: 'Otomatis', fuel: 'Bensin', engineCc: 1497, seats: 5,
    mileage: 54000, color: 'Putih', colorHex: '#f4f4f5', plate: 'B 1918 HRV', location: 'Jakarta Utara',
    price: 258000000, buyPrice: 232000000, status: 'available',
    inspection: insp(94, 93, 92, 95, 92, 93, 100), ownerCount: 1, taxValidUntil: '2026-11', serviceRecord: true,
    features: ['Eco Assist', 'Paddle Shift', 'Kamera Mundur', 'Jok Fabric Premium'],
    description: 'HR-V E CVT terawat, konsumsi BBM irit. Cat original, bebas baret besar.',
    imageKey: 'hrv', imageUrl: '', isFeatured: false, createdAt: at(-6, 13),
  },
  {
    id: 'car-8', code: 'A18-0008',
    brand: 'Honda', model: 'CR-V', variant: '1.5 Turbo Prestige', year: 2018,
    bodyType: 'SUV', transmission: 'Otomatis', fuel: 'Bensin', engineCc: 1498, seats: 7,
    mileage: 77000, color: 'Putih', colorHex: '#f4f4f5', plate: 'B 1818 CRV', location: 'Jakarta Selatan',
    price: 398000000, buyPrice: 360000000, status: 'booked',
    inspection: insp(93, 92, 91, 94, 93, 92, 100), ownerCount: 1, taxValidUntil: '2026-09', serviceRecord: true,
    features: ['Sunroof', 'Remote Engine Start', 'Jok Kulit Elektrik', 'Honda LaneWatch'],
    description: 'CR-V Turbo Prestige 7 seater dengan sunroof. Performa turbo responsif, kabin mewah.',
    imageKey: 'crv', imageUrl: '', isFeatured: false, createdAt: at(-10, 16),
  },
  {
    id: 'car-9', code: 'A18-0009',
    brand: 'Suzuki', model: 'Ertiga', variant: 'GX AT', year: 2020,
    bodyType: 'MPV', transmission: 'Otomatis', fuel: 'Bensin', engineCc: 1462, seats: 7,
    mileage: 46000, color: 'Putih', colorHex: '#f4f4f5', plate: 'F 2018 ERG', location: 'Bogor',
    price: 178000000, buyPrice: 158000000, status: 'available',
    inspection: insp(94, 93, 93, 94, 92, 92, 100), ownerCount: 1, taxValidUntil: '2027-04', serviceRecord: false,
    features: ['Head Unit Layar Sentuh', 'Kamera Mundur', 'Engine Start/Stop'],
    description: 'Ertiga GX matic, mesin bandel dan irit. Pas untuk keluarga muda.',
    imageKey: 'ertiga', imageUrl: '', isFeatured: false, createdAt: at(-4, 12),
  },
  {
    id: 'car-10', code: 'A18-0010',
    brand: 'Daihatsu', model: 'Terios', variant: 'R Custom AT', year: 2019,
    bodyType: 'SUV', transmission: 'Otomatis', fuel: 'Bensin', engineCc: 1496, seats: 7,
    mileage: 63000, color: 'Silver', colorHex: '#c3c7cc', plate: 'B 1918 TRS', location: 'Tangerang Selatan',
    price: 188000000, buyPrice: 168000000, status: 'available',
    inspection: insp(92, 91, 91, 92, 90, 91, 100), ownerCount: 2, taxValidUntil: '2026-12', serviceRecord: true,
    features: ['Body Kit Custom', 'Ground Clearance Tinggi', 'Kamera Mundur'],
    description: 'Terios R Custom tangguh untuk jalan rusak dan banjir ringan. Siap ke luar kota.',
    imageKey: 'terios', imageUrl: '', isFeatured: false, createdAt: at(-12, 10),
  },
  {
    id: 'car-11', code: 'A18-0011',
    brand: 'Honda', model: 'Civic', variant: '1.5 Turbo ES CVT', year: 2017,
    bodyType: 'Sedan', transmission: 'Otomatis', fuel: 'Bensin', engineCc: 1498, seats: 5,
    mileage: 82000, color: 'Hitam', colorHex: '#111111', plate: 'B 1718 CVC', location: 'Jakarta Pusat',
    price: 338000000, buyPrice: 305000000, status: 'available',
    inspection: insp(91, 90, 90, 92, 89, 90, 100), ownerCount: 2, taxValidUntil: '2027-01', serviceRecord: true,
    features: ['Mesin Turbo 173 PS', 'Honda LaneWatch', 'Remote Engine Start', 'LED Headlamp'],
    description: 'Civic Turbo ES sporty dan bertenaga. Kondisi prima, cocok untuk pencinta sedan.',
    imageKey: 'civic', imageUrl: '', isFeatured: false, createdAt: at(-15, 14),
  },
  {
    id: 'car-12', code: 'A18-0012',
    brand: 'Toyota', model: 'Yaris', variant: '1.5 S GR Sport CVT', year: 2021,
    bodyType: 'Hatchback', transmission: 'Otomatis', fuel: 'Bensin', engineCc: 1496, seats: 5,
    mileage: 25000, color: 'Silver', colorHex: '#c3c7cc', plate: 'B 2118 YRS', location: 'Jakarta Barat',
    price: 238000000, buyPrice: 214000000, status: 'sold',
    inspection: insp(97, 96, 96, 97, 95, 96, 100), ownerCount: 1, taxValidUntil: '2027-05', serviceRecord: true,
    features: ['Body Kit GR Sport', 'Toyota Safety Sense', '7 Airbag', 'Paddle Shift'],
    description: 'Yaris GR Sport kilometer rendah, seperti baru. Sudah terjual — terima kasih kepada pembeli!',
    imageKey: 'yaris', imageUrl: '', isFeatured: false, createdAt: at(-20, 11),
    soldAt: at(-3, 14, 5), soldPrice: 235000000, soldTransactionId: 'trx-104',
  },
];

export const INITIAL_CARS = RAW_CARS.map((car) => ({
  soldAt: null,
  soldPrice: null,
  soldTransactionId: null,
  ...car,
  inspectionScore: avg(car.inspection),
}));

// ---------------------------------------------------------------------------
// Permintaan jual mobil & tukar tambah dari pelanggan
// ---------------------------------------------------------------------------
// type: 'sell' | 'trade-in'
// status: 'new' | 'scheduled' | 'inspected' | 'purchased' | 'rejected'
export const INITIAL_SELL_REQUESTS = [
  {
    id: 'sell-1', code: 'JUAL-0001', type: 'sell',
    customerName: 'Bpk. Hendra Gunawan', phone: '0812-1818-2201', email: '',
    brand: 'Toyota', model: 'Rush', variant: '1.5 S GR Sport AT', year: 2020,
    bodyType: 'SUV', transmission: 'Otomatis', fuel: 'Bensin', mileage: 48000,
    color: 'Putih', location: 'Jakarta Timur', condition: 'Baik',
    askingPrice: 215000000, estimateLow: 0, estimateHigh: 0,
    inspectionDate: dateStr(1), inspectionTime: '10:00',
    tradeInCarId: null, notes: 'Pajak baru diperpanjang bulan lalu.',
    status: 'scheduled', offerPrice: 0, purchasedCarId: null, createdAt: at(-1, 19, 30),
  },
  {
    id: 'sell-2', code: 'JUAL-0002', type: 'trade-in',
    customerName: 'Ibu Maria Kristina', phone: '0857-1818-3302', email: '',
    brand: 'Honda', model: 'Jazz', variant: 'RS CVT', year: 2017,
    bodyType: 'Hatchback', transmission: 'Otomatis', fuel: 'Bensin', mileage: 76000,
    color: 'Merah', location: 'Tangerang Selatan', condition: 'Baik',
    askingPrice: 175000000, estimateLow: 0, estimateHigh: 0,
    inspectionDate: dateStr(2), inspectionTime: '13:00',
    tradeInCarId: 'car-7', notes: 'Ingin tukar tambah ke HR-V putih.',
    status: 'new', offerPrice: 0, purchasedCarId: null, createdAt: at(0, 8, 45),
  },
  {
    id: 'sell-3', code: 'JUAL-0003', type: 'sell',
    customerName: 'Sdr. Kevin Pratama', phone: '0821-1818-4403', email: '',
    brand: 'Daihatsu', model: 'Sigra', variant: '1.2 R MT', year: 2019,
    bodyType: 'MPV', transmission: 'Manual', fuel: 'Bensin', mileage: 91000,
    color: 'Silver', location: 'Bekasi', condition: 'Cukup',
    askingPrice: 110000000, estimateLow: 0, estimateHigh: 0,
    inspectionDate: dateStr(-2), inspectionTime: '15:00',
    tradeInCarId: null, notes: 'Ada baret di bumper belakang.',
    status: 'inspected', offerPrice: 96000000, purchasedCarId: null, createdAt: at(-4, 16, 10),
  },
];

// ---------------------------------------------------------------------------
// Booking test drive
// ---------------------------------------------------------------------------
// location: 'showroom' | 'rumah'
// status: 'pending' | 'confirmed' | 'done' | 'cancelled'
export const INITIAL_TEST_DRIVES = [
  {
    id: 'td-1', code: 'TD-0001', carId: 'car-2', carName: 'Toyota Fortuner 2.4 VRZ AT 4x2 2020',
    customerName: 'Bpk. Agus Salim', phone: '0813-1818-5501',
    date: dateStr(0), time: '14:00', location: 'showroom', address: '',
    notes: 'Ingin sekalian tanya simulasi kredit.', status: 'confirmed', createdAt: at(-1, 20, 15),
  },
  {
    id: 'td-2', code: 'TD-0002', carId: 'car-1', carName: 'Mitsubishi Xpander Ultimate 1.5 AT 2021',
    customerName: 'Ibu Dewi Lestari', phone: '0878-1818-6602',
    date: dateStr(1), time: '10:30', location: 'rumah', address: 'Kebon Jeruk, Jakarta Barat',
    notes: '', status: 'pending', createdAt: at(0, 7, 50),
  },
  {
    id: 'td-3', code: 'TD-0003', carId: 'car-12', carName: 'Toyota Yaris 1.5 S GR Sport CVT 2021',
    customerName: 'Sdr. Rizky Maulana', phone: '0812-1818-7703',
    date: dateStr(-4), time: '11:00', location: 'showroom', address: '',
    notes: 'Jadi beli setelah test drive.', status: 'done', createdAt: at(-6, 9, 20),
  },
];

// ---------------------------------------------------------------------------
// Konten brand Auto18 (dari materi poster)
// ---------------------------------------------------------------------------
// icon = nama komponen lucide-react
export const AUTO18_BENEFITS = [
  { no: 1, title: 'Towing Darurat', icon: 'Truck' },
  { no: 2, title: 'Pembukaan Pintu Mobil', icon: 'KeyRound' },
  { no: 3, title: 'Pengisian Bahan Bakar', icon: 'Fuel' },
  { no: 4, title: 'Jumper Aki Mobil', icon: 'BatteryCharging' },
  { no: 5, title: 'Pergantian Ban Serep', icon: 'CircleDot' },
  { no: 6, title: 'Pemeriksaan Mobil di Tempat', icon: 'SearchCheck' },
  { no: 7, title: 'Klaim Kecelakaan', icon: 'ShieldAlert' },
  { no: 8, title: 'Klaim Suku Cadang', icon: 'Cog' },
  { no: 9, title: 'Perawatan dan Perbaikan', icon: 'Wrench' },
];

export const AUTO18_BENEFITS_SUBTITLE = 'Layanan Darurat 24 Jam di Seluruh Indonesia';

export const BUYING_TIPS = [
  { no: 1, title: 'Sesuaikan Pembelian Mobil dengan Budget', description: 'Hitung kemampuan DP dan cicilan bulanan. Idealnya cicilan tidak lebih dari 30% penghasilan.' },
  { no: 2, title: 'Periksa Kondisi Mesin', description: 'Dengarkan suara mesin saat dingin, cek asap knalpot, rembesan oli, dan riwayat servis.' },
  { no: 3, title: 'Lakukan Survei Terlebih Dahulu Sebelum Membeli', description: 'Bandingkan harga pasaran untuk model, tahun, dan kilometer yang setara.' },
  { no: 4, title: 'Meneliti Kelengkapan Surat-Surat', description: 'Pastikan BPKB, STNK, faktur, serta nomor rangka dan mesin sesuai, dan pajak masih hidup.' },
  { no: 5, title: 'Jangan Lupa Lakukan Test Drive', description: 'Rasakan langsung transmisi, rem, setir, dan suspensi di jalan sebelum memutuskan.' },
];

export const HOW_IT_WORKS_BUY = [
  { no: 1, title: 'Pilih Mobil', description: 'Cari dan filter mobil second berkualitas sesuai merek, budget, dan kebutuhan.', icon: 'Search' },
  { no: 2, title: 'Booking Test Drive', description: 'Jadwalkan test drive di showroom atau langsung di rumah Anda.', icon: 'CalendarCheck' },
  { no: 3, title: 'Bayar Tunai / Kredit', description: 'Hitung simulasi cicilan, lalu bayar tunai atau ajukan kredit ke leasing partner.', icon: 'Wallet' },
  { no: 4, title: 'Bawa Pulang Mobil', description: 'Surat lengkap, mobil siap pakai, plus benefit layanan darurat 24 jam.', icon: 'KeyRound' },
];

export const HOW_IT_WORKS_SELL = [
  { no: 1, title: 'Isi Data Mobil', description: 'Masukkan merek, tahun, dan kilometer untuk mendapat estimasi harga instan.', icon: 'ClipboardList' },
  { no: 2, title: 'Jadwalkan Inspeksi', description: 'Pilih tanggal dan jam, tim Auto18 akan memeriksa kondisi mobil Anda.', icon: 'CalendarCheck' },
  { no: 3, title: 'Terima Penawaran', description: 'Dapatkan harga final setelah inspeksi, tanpa kewajiban untuk menjual.', icon: 'BadgePercent' },
  { no: 4, title: 'Pembayaran Cepat', description: 'Setelah dokumen beres, dana langsung ditransfer ke rekening Anda.', icon: 'Banknote' },
];

export const FAQS_BUY = [
  { q: 'Apakah semua mobil di Auto18 sudah diinspeksi?', a: 'Ya. Setiap mobil melewati inspeksi mesin, transmisi, kaki-kaki, kelistrikan, interior, eksterior, dan dokumen. Nilai inspeksinya bisa dilihat di halaman detail mobil.' },
  { q: 'Bisakah saya membeli mobil secara kredit?', a: 'Bisa. Gunakan simulasi kredit untuk memperkirakan DP dan cicilan per bulan. Persetujuan kredit dan bunga final mengikuti ketentuan leasing partner.' },
  { q: 'Bagaimana cara booking test drive?', a: 'Buka detail mobil, klik "Booking Test Drive", lalu pilih tanggal, jam, dan lokasi (showroom atau rumah). Tim kami akan menghubungi untuk konfirmasi.' },
  { q: 'Apakah bisa tukar tambah mobil lama saya?', a: 'Bisa. Pilih menu Jual Mobil lalu opsi "Tukar Tambah". Nilai mobil lama Anda akan dipotong dari harga mobil yang ingin dibeli.' },
  { q: 'Apa itu benefit layanan darurat 24 jam?', a: 'Pembeli mobil di Auto18 mendapat layanan darurat seperti towing, jumper aki, ganti ban serep, hingga klaim kecelakaan. Hubungi call center 24 jam kami bila membutuhkan.' },
];

export const FAQS_SELL = [
  { q: 'Apakah estimasi harga di website sudah final?', a: 'Belum. Estimasi dihitung dari merek, tipe, tahun, kilometer, dan kondisi. Harga final diberikan setelah inspeksi langsung.' },
  { q: 'Dokumen apa saja yang perlu disiapkan?', a: 'BPKB, STNK, faktur (jika ada), KTP pemilik sesuai BPKB, dan buku servis bila tersedia.' },
  { q: 'Apakah saya wajib menjual setelah inspeksi?', a: 'Tidak. Inspeksi dan penawaran tidak mengikat, Anda bebas menerima atau menolak.' },
  { q: 'Berapa lama proses pembayarannya?', a: 'Setelah harga disepakati dan dokumen lengkap, pembayaran diproses dengan transfer ke rekening Anda.' },
];

// ---------------------------------------------------------------------------
// Kasir aksesoris, oli & perawatan (modul POS)
// ---------------------------------------------------------------------------
export const INITIAL_CATEGORIES = [
  { id: 'cat-1', name: 'Oli & Pelumas', icon: 'Droplet', color: 'red' },
  { id: 'cat-2', name: 'Aki & Kelistrikan', icon: 'BatteryCharging', color: 'amber' },
  { id: 'cat-3', name: 'Ban & Kaki-kaki', icon: 'CircleDot', color: 'sky' },
  { id: 'cat-4', name: 'Aksesoris Interior', icon: 'Armchair', color: 'violet' },
  { id: 'cat-5', name: 'Perawatan & Cuci Mobil', icon: 'Sparkles', color: 'teal' },
  { id: 'cat-6', name: 'Jasa Showroom', icon: 'Wrench', color: 'neutral' },
];

export const INITIAL_PRODUCTS = [
  { id: 'p-1', sku: 'OLI-1001', name: 'Oli Mesin Shell Helix HX7 10W-40 4L', categoryId: 'cat-1', categoryName: 'Oli & Pelumas', unit: 'botol', buyPrice: 285000, sellPrice: 335000, stock: 18, minStock: 5, barcode: '899180001001', color: '#e41b1b' },
  { id: 'p-2', sku: 'OLI-1002', name: 'Oli Mesin Castrol Magnatec 10W-40 4L', categoryId: 'cat-1', categoryName: 'Oli & Pelumas', unit: 'botol', buyPrice: 295000, sellPrice: 345000, stock: 14, minStock: 5, barcode: '899180001002', color: '#e41b1b' },
  { id: 'p-3', sku: 'OLI-1003', name: 'Oli Diesel Shell Rimula R4 15W-40 5L', categoryId: 'cat-1', categoryName: 'Oli & Pelumas', unit: 'botol', buyPrice: 380000, sellPrice: 440000, stock: 9, minStock: 4, barcode: '899180001003', color: '#e41b1b' },
  { id: 'p-4', sku: 'OLI-1004', name: 'Coolant Radiator Prestone 1L', categoryId: 'cat-1', categoryName: 'Oli & Pelumas', unit: 'botol', buyPrice: 45000, sellPrice: 60000, stock: 30, minStock: 8, barcode: '899180001004', color: '#e41b1b' },
  { id: 'p-5', sku: 'AKI-2001', name: 'Aki GS Astra Hybrid NS40ZL', categoryId: 'cat-2', categoryName: 'Aki & Kelistrikan', unit: 'pcs', buyPrice: 690000, sellPrice: 820000, stock: 6, minStock: 3, barcode: '899180002001', color: '#f59e0b' },
  { id: 'p-6', sku: 'AKI-2002', name: 'Lampu LED Philips Ultinon H4 (Sepasang)', categoryId: 'cat-2', categoryName: 'Aki & Kelistrikan', unit: 'set', buyPrice: 520000, sellPrice: 650000, stock: 2, minStock: 4, barcode: '899180002002', color: '#f59e0b' },
  { id: 'p-7', sku: 'BAN-3001', name: 'Ban Bridgestone Ecopia 185/65 R15', categoryId: 'cat-3', categoryName: 'Ban & Kaki-kaki', unit: 'pcs', buyPrice: 780000, sellPrice: 925000, stock: 16, minStock: 8, barcode: '899180003001', color: '#0ea5e9' },
  { id: 'p-8', sku: 'BAN-3002', name: 'Wiper Bosch Clear Advantage 22"', categoryId: 'cat-3', categoryName: 'Ban & Kaki-kaki', unit: 'pcs', buyPrice: 85000, sellPrice: 115000, stock: 3, minStock: 6, barcode: '899180003002', color: '#0ea5e9' },
  { id: 'p-9', sku: 'INT-4001', name: 'Karpet Mobil Custom 3 Baris (MPV)', categoryId: 'cat-4', categoryName: 'Aksesoris Interior', unit: 'set', buyPrice: 450000, sellPrice: 590000, stock: 7, minStock: 3, barcode: '899180004001', color: '#8b5cf6' },
  { id: 'p-10', sku: 'INT-4002', name: 'Parfum Mobil Gantung Aroma Ocean', categoryId: 'cat-4', categoryName: 'Aksesoris Interior', unit: 'pcs', buyPrice: 18000, sellPrice: 30000, stock: 45, minStock: 10, barcode: '899180004002', color: '#8b5cf6' },
  { id: 'p-11', sku: 'INT-4003', name: 'Dashcam 70mai A500S Dual Channel', categoryId: 'cat-4', categoryName: 'Aksesoris Interior', unit: 'unit', buyPrice: 1150000, sellPrice: 1390000, stock: 5, minStock: 2, barcode: '899180004003', color: '#8b5cf6' },
  { id: 'p-12', sku: 'CUC-5001', name: 'Shampoo Mobil + Wax 1 Liter', categoryId: 'cat-5', categoryName: 'Perawatan & Cuci Mobil', unit: 'botol', buyPrice: 42000, sellPrice: 65000, stock: 25, minStock: 8, barcode: '899180005001', color: '#14b8a6' },
  { id: 'p-13', sku: 'CUC-5002', name: 'Lap Microfiber Premium 40x40 (Isi 3)', categoryId: 'cat-5', categoryName: 'Perawatan & Cuci Mobil', unit: 'pack', buyPrice: 35000, sellPrice: 55000, stock: 40, minStock: 10, barcode: '899180005002', color: '#14b8a6' },
  { id: 'p-14', sku: 'JSA-6001', name: 'Jasa Nano Ceramic Coating (Sedan/Hatchback)', categoryId: 'cat-6', categoryName: 'Jasa Showroom', unit: 'paket', buyPrice: 900000, sellPrice: 1800000, stock: 50, minStock: 5, barcode: '899180006001', color: '#a3a3a3' },
  { id: 'p-15', sku: 'JSA-6002', name: 'Jasa Pasang Kaca Film Full Body', categoryId: 'cat-6', categoryName: 'Jasa Showroom', unit: 'paket', buyPrice: 1100000, sellPrice: 1650000, stock: 30, minStock: 5, barcode: '899180006002', color: '#a3a3a3' },
];

export const INITIAL_SUPPLIERS = [
  {
    id: 'sup-1',
    name: 'PT Sinar Pelumas Nusantara',
    contactPerson: 'Bpk. Rudi Hartono',
    phone: '0812-7788-1801',
    address: 'Jl. Gunung Sahari Raya No. 18, Jakarta Pusat',
    categories: ['Oli & Pelumas', 'Perawatan & Cuci Mobil'],
    balance: 0
  },
  {
    id: 'sup-2',
    name: 'CV Mitra Ban & Aki Jaya',
    contactPerson: 'Ibu Lina Kurniawati',
    phone: '0857-2211-4418',
    address: 'Jl. Daan Mogot KM 12, Jakarta Barat',
    categories: ['Aki & Kelistrikan', 'Ban & Kaki-kaki'],
    balance: 0
  },
  {
    id: 'sup-3',
    name: 'Toko Aksesoris Otomotif Maju Bersama',
    contactPerson: 'Sdr. Andre Wijaya',
    phone: '0821-9090-1818',
    address: 'Jl. Kramat Raya No. 55, Jakarta Pusat',
    categories: ['Aksesoris Interior', 'Jasa Showroom'],
    balance: 0
  }
];

// ---------------------------------------------------------------------------
// Transaksi contoh (retail + penjualan mobil) agar dashboard langsung terisi
// ---------------------------------------------------------------------------
// saleType: 'retail' (kasir aksesoris) | 'car' (penjualan mobil)
// paymentMethod: 'cash' | 'qris' | 'transfer' | 'credit' (khusus mobil)
export const INITIAL_TRANSACTIONS = [
  {
    id: 'trx-101',
    code: 'TRX-A18-1001',
    date: at(0, 9, 15),
    saleType: 'retail',
    customerName: 'Pelanggan Umum',
    cashier: 'Admin Showroom',
    items: [
      { id: 'p-1', sku: 'OLI-1001', name: 'Oli Mesin Shell Helix HX7 10W-40 4L', unit: 'botol', price: 335000, buyPrice: 285000, qty: 1, subtotal: 335000 },
      { id: 'p-10', sku: 'INT-4002', name: 'Parfum Mobil Gantung Aroma Ocean', unit: 'pcs', price: 30000, buyPrice: 18000, qty: 2, subtotal: 60000 }
    ],
    subtotal: 395000,
    discount: 0,
    tax: 0,
    total: 395000,
    paymentMethod: 'cash',
    paidAmount: 400000,
    changeAmount: 5000,
    totalProfit: 74000
  },
  {
    id: 'trx-102',
    code: 'TRX-A18-1002',
    date: at(0, 10, 42),
    saleType: 'retail',
    customerName: 'Ibu Rahayu',
    cashier: 'Admin Showroom',
    items: [
      { id: 'p-8', sku: 'BAN-3002', name: 'Wiper Bosch Clear Advantage 22"', unit: 'pcs', price: 115000, buyPrice: 85000, qty: 2, subtotal: 230000 },
      { id: 'p-13', sku: 'CUC-5002', name: 'Lap Microfiber Premium 40x40 (Isi 3)', unit: 'pack', price: 55000, buyPrice: 35000, qty: 1, subtotal: 55000 }
    ],
    subtotal: 285000,
    discount: 5000,
    tax: 0,
    total: 280000,
    paymentMethod: 'qris',
    paidAmount: 280000,
    changeAmount: 0,
    totalProfit: 75000
  },
  {
    id: 'trx-103',
    code: 'TRX-A18-1003',
    date: at(0, 11, 20),
    saleType: 'retail',
    customerName: 'Bpk. Ahmad',
    cashier: 'Admin Showroom',
    items: [
      { id: 'p-14', sku: 'JSA-6001', name: 'Jasa Nano Ceramic Coating (Sedan/Hatchback)', unit: 'paket', price: 1800000, buyPrice: 900000, qty: 1, subtotal: 1800000 }
    ],
    subtotal: 1800000,
    discount: 0,
    tax: 0,
    total: 1800000,
    paymentMethod: 'transfer',
    paidAmount: 1800000,
    changeAmount: 0,
    totalProfit: 900000
  },
  {
    id: 'trx-105',
    code: 'TRX-A18-1005',
    date: at(-1, 16, 30),
    saleType: 'retail',
    customerName: 'Pelanggan Umum',
    cashier: 'Admin Showroom',
    items: [
      { id: 'p-5', sku: 'AKI-2001', name: 'Aki GS Astra Hybrid NS40ZL', unit: 'pcs', price: 820000, buyPrice: 690000, qty: 1, subtotal: 820000 },
      { id: 'p-4', sku: 'OLI-1004', name: 'Coolant Radiator Prestone 1L', unit: 'botol', price: 60000, buyPrice: 45000, qty: 2, subtotal: 120000 }
    ],
    subtotal: 940000,
    discount: 0,
    tax: 0,
    total: 940000,
    paymentMethod: 'cash',
    paidAmount: 1000000,
    changeAmount: 60000,
    totalProfit: 160000
  },
  {
    id: 'trx-104',
    code: 'TRX-A18-1004',
    date: at(-3, 14, 5),
    saleType: 'car',
    customerName: 'Sdr. Rizky Maulana',
    customerPhone: '0812-1818-7703',
    cashier: 'Admin Showroom',
    items: [
      { id: 'car-12', sku: 'A18-0012', name: 'Toyota Yaris 1.5 S GR Sport CVT 2021', unit: 'unit', price: 238000000, buyPrice: 214000000, qty: 1, subtotal: 238000000 }
    ],
    subtotal: 238000000,
    discount: 3000000,
    tax: 0,
    total: 235000000,
    amountDue: 235000000,
    paymentMethod: 'credit',
    paidAmount: 70500000,
    changeAmount: 0,
    totalProfit: 21000000,
    car: {
      id: 'car-12', code: 'A18-0012', brand: 'Toyota', model: 'Yaris', variant: '1.5 S GR Sport CVT',
      year: 2021, plate: 'B 2118 YRS', mileage: 25000, transmission: 'Otomatis', fuel: 'Bensin', color: 'Silver'
    },
    credit: {
      leasing: 'BCA Finance', dpPercent: 30, tenorMonths: 36, annualRate: 7,
      dpAmount: 70500000, principal: 164500000, totalInterest: 34545000, monthly: 5530000
    },
    tradeIn: null,
    notes: ''
  }
];
