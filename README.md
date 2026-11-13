# AUTO18 — Auto Eighteen

**Jual / Beli Mobil Second Berkualitas.** Aplikasi web showroom mobil bekas: etalase mobil untuk pembeli, formulir jual & tukar tambah untuk penjual, dan panel showroom lengkap (stok mobil, kasir aksesoris, laporan penjualan).

Dibangun dengan **Laravel 12 (PHP)** dan **MySQL**. Tampilannya mengikuti materi promosi Auto18: latar hitam, garis diagonal merah, dan tipografi tebal huruf besar.

---

## Fitur

### Beli Mobil (publik)
- Pencarian cepat: kata kunci, tipe bodi, dan rentang harga.
- Pintasan merek dan tipe bodi dengan jumlah unit.
- Filter: merek (multi-pilih), harga, tipe bodi, transmisi, bahan bakar, tahun, kilometer, lokasi, favorit, dan mobil terjual.
- Urutan: rekomendasi, terbaru, harga, kilometer, tahun.
- Halaman detail: spesifikasi, fitur, laporan inspeksi 7 kategori dengan diagram, simulasi kredit, booking test drive, tombol WhatsApp / call center.
- Favorit (disimpan di browser, karena pembeli tidak perlu akun).

### Jual Mobil & Tukar Tambah (publik)
- Formulir 3 langkah: data mobil, jadwal inspeksi, data kontak.
- Estimasi harga instan dari merek, model, tahun, kilometer, transmisi, dan kondisi.
- Mode tukar tambah: pilih mobil incaran dan lihat perkiraan tambahan bayar.

### Panel Showroom (wajib login)
- **Dashboard**: omset & laba hari ini, ringkasan showroom, grafik omset 7 hari (mobil vs aksesoris), stok menipis, transaksi terbaru.
- **Stok Mobil**: tambah/ubah/hapus unit, status tersedia/dipesan, "Pilihan Auto18", modal, harga jual, margin.
- **Penjualan mobil**: tunai, transfer, atau kredit (leasing, DP, tenor, bunga flat), diskon, tukar tambah, nota penjualan.
- **Batalkan penjualan**: transaksi ditandai "Dibatalkan" dan tidak lagi dihitung; mobil kembali tersedia.
- **Permintaan jual / tukar tambah**: jadwalkan inspeksi, catat penawaran, beli dan masukkan ke stok, atau tolak.
- **Test drive**: konfirmasi, selesai, batalkan, dengan penanda "Hari Ini".
- **Kasir Aksesoris**: keranjang, diskon (Rp atau %), PPN opsional, bayar tunai/QRIS/transfer, struk siap cetak.
- **Produk & Stok**: data produk, kategori, margin otomatis, peringatan stok menipis, ubah stok manual.
- **Suplier**: data suplier dan pencatatan barang masuk (stok bertambah, harga modal diperbarui).
- **Laporan**: filter periode/tanggal, jenis, metode bayar; total omset, HPP, laba; ekspor CSV; cetak ulang nota.
- **Pengaturan**: profil showroom, kontak, nama kasir, kaki struk, PPN, bunga kredit, DP minimal, reset data demo.

---

## Cara Menjalankan

### Prasyarat
- [XAMPP](https://www.apachefriends.org/) dengan PHP 8.2 atau lebih baru dan MySQL/MariaDB
- [Composer](https://getcomposer.org/)

Node.js **tidak** diperlukan untuk menjalankan aplikasi: berkas CSS/JS hasil build sudah ikut di folder `public/build`.

### Langkah
1. Jalankan **MySQL** dari XAMPP Control Panel.
2. Buat database kosong bernama `auto18` (lewat phpMyAdmin, atau perintah di bawah):
   ```sql
   CREATE DATABASE auto18 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Di folder proyek:
   ```bash
   composer install
   cp .env.example .env        # Windows: copy .env.example .env
   php artisan key:generate
   ```
   `.env.example` sudah berisi pengaturan XAMPP bawaan (`DB_DATABASE=auto18`, user `root`, tanpa kata sandi). Ubah di `.env` bila MySQL Anda berbeda.
4. Buat tabel dan isi data demo:
   ```bash
   php artisan migrate --seed
   ```
5. Jalankan server:
   ```bash
   php artisan serve
   ```
   Buka alamat yang ditampilkan (biasanya http://127.0.0.1:8000).

### Login Panel Showroom
| Email | Kata sandi |
| --- | --- |
| `admin@auto18.test` | `auto18admin` |

Klik **Login** di pojok kanan atas. Halaman Beli Mobil dan Jual Mobil bisa dibuka tanpa login.

### Reset Data Demo
- Dari aplikasi: **Pengaturan** (ikon roda gigi) lalu **Reset Data Demo**.
- Dari terminal: `php artisan migrate:fresh --seed`

Tanggal pada data demo selalu relatif terhadap hari seeding, jadi dashboard dan laporan langsung terisi.

---

## Struktur Proyek

| Lokasi | Isi |
| --- | --- |
| `routes/web.php` | Semua rute: publik, login, dan panel showroom (`/showroom/...`) |
| `app/Http/Controllers/Public` | Katalog mobil, booking test drive, formulir jual mobil |
| `app/Http/Controllers/Showroom` | Dashboard, stok mobil, penjualan, kasir, produk, suplier, laporan, pengaturan |
| `app/Services` | Aturan bisnis: `CarSaleService`, `RetailSaleService`, `SellRequestService`, `CarPricing`, `CodeGenerator`, `DemoData` |
| `app/Models` | Model Eloquent (Car, Product, Transaction, dan lainnya) |
| `database/migrations` | Skema tabel MySQL |
| `database/seeders` | Data demo (`data/demo.json`) |
| `resources/views` | Tampilan Blade |
| `config/auto18.php` | Daftar tetap (merek, tipe bodi, leasing, kategori inspeksi) dan konten brand |
| `tests` | Pengujian otomatis |

Semua perhitungan uang (total, diskon, pajak, stok, cicilan, status) dilakukan dan divalidasi di PHP di dalam transaksi database. JavaScript (Alpine.js) hanya dipakai untuk interaksi halaman seperti keranjang kasir dan pratinjau simulasi.

## Pengujian

```bash
php artisan test
```

Pengujian memakai SQLite di memori (ekstensi `pdo_sqlite`, aktif secara bawaan di XAMPP), jadi tidak menyentuh database `auto18`.

## Mengubah Tampilan (opsional)

Hanya diperlukan bila Anda mengubah CSS/JS di `resources/`:

```bash
npm install
npm run build    # atau: npm run dev
```

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2
- **Database**: MySQL / MariaDB
- **Tampilan**: Blade, Tailwind CSS 3 (tema `brand` merah & `ink` hitam), Alpine.js
- **Ikon**: Lucide (blade-lucide-icons)

## Kredit Foto

Foto mobil berasal dari Wikimedia Commons dengan lisensi CC BY-SA 4.0. Rinciannya ada di `public/images/cars/CREDITS.md`.
