<?php

// Daftar tetap & konten brand Auto18 (dipindahkan dari legacy-react/src/data/initialData.js)
return [
    'brands' => ['Toyota', 'Honda', 'Mitsubishi', 'Suzuki', 'Daihatsu', 'Nissan', 'Hyundai', 'Mazda', 'Wuling', 'BMW'],

    'body_types' => ['MPV', 'SUV', 'Hatchback', 'Sedan', 'Pick-up'],

    'transmissions' => ['Otomatis', 'Manual'],

    'fuels' => ['Bensin', 'Diesel', 'Hybrid', 'Listrik'],

    'conditions' => ['Sangat Baik', 'Baik', 'Cukup', 'Perlu Perbaikan'],

    'colors' => [
        [
            'name' => 'Putih',
            'hex' => '#f4f4f5',
        ],
        [
            'name' => 'Hitam',
            'hex' => '#111111',
        ],
        [
            'name' => 'Silver',
            'hex' => '#c3c7cc',
        ],
        [
            'name' => 'Abu-abu',
            'hex' => '#6b7280',
        ],
        [
            'name' => 'Merah',
            'hex' => '#c81e1e',
        ],
        [
            'name' => 'Biru',
            'hex' => '#1e3a8a',
        ],
        [
            'name' => 'Hijau',
            'hex' => '#1f4d3a',
        ],
        [
            'name' => 'Coklat',
            'hex' => '#6b4f3a',
        ],
        [
            'name' => 'Oranye',
            'hex' => '#ea580c',
        ],
    ],

    'locations' => ['Jakarta Pusat', 'Jakarta Barat', 'Jakarta Selatan', 'Jakarta Timur', 'Jakarta Utara', 'Tangerang', 'Tangerang Selatan', 'Bekasi', 'Depok', 'Bogor'],

    'price_ranges' => [
        [
            'id' => 'lt150',
            'label' => '< Rp 150 jt',
            'min' => 0,
            'max' => 150000000,
        ],
        [
            'id' => '150-250',
            'label' => 'Rp 150 – 250 jt',
            'min' => 150000000,
            'max' => 250000000,
        ],
        [
            'id' => '250-350',
            'label' => 'Rp 250 – 350 jt',
            'min' => 250000000,
            'max' => 350000000,
        ],
        [
            'id' => 'gt350',
            'label' => '> Rp 350 jt',
            'min' => 350000000,
            'max' => NULL,
        ],
    ],

    'tenors' => [12, 24, 36, 48, 60],

    'leasing_partners' => ['BCA Finance', 'Adira Finance', 'Mandiri Tunas Finance', 'ACC', 'Leasing Lainnya'],

    'inspection_categories' => [
        [
            'key' => 'mesin',
            'label' => 'Mesin & Performa',
        ],
        [
            'key' => 'transmisi',
            'label' => 'Transmisi & Kopling',
        ],
        [
            'key' => 'kakiKaki',
            'label' => 'Kaki-kaki & Rem',
        ],
        [
            'key' => 'kelistrikan',
            'label' => 'Kelistrikan & AC',
        ],
        [
            'key' => 'interior',
            'label' => 'Interior',
        ],
        [
            'key' => 'eksterior',
            'label' => 'Eksterior & Rangka',
        ],
        [
            'key' => 'dokumen',
            'label' => 'Dokumen (BPKB, STNK, Faktur)',
        ],
    ],

    'benefits' => [
        [
            'no' => 1,
            'title' => 'Towing Darurat',
            'icon' => 'Truck',
        ],
        [
            'no' => 2,
            'title' => 'Pembukaan Pintu Mobil',
            'icon' => 'KeyRound',
        ],
        [
            'no' => 3,
            'title' => 'Pengisian Bahan Bakar',
            'icon' => 'Fuel',
        ],
        [
            'no' => 4,
            'title' => 'Jumper Aki Mobil',
            'icon' => 'BatteryCharging',
        ],
        [
            'no' => 5,
            'title' => 'Pergantian Ban Serep',
            'icon' => 'CircleDot',
        ],
        [
            'no' => 6,
            'title' => 'Pemeriksaan Mobil di Tempat',
            'icon' => 'SearchCheck',
        ],
        [
            'no' => 7,
            'title' => 'Klaim Kecelakaan',
            'icon' => 'ShieldAlert',
        ],
        [
            'no' => 8,
            'title' => 'Klaim Suku Cadang',
            'icon' => 'Cog',
        ],
        [
            'no' => 9,
            'title' => 'Perawatan dan Perbaikan',
            'icon' => 'Wrench',
        ],
    ],

    'benefits_subtitle' =>     'Layanan Darurat 24 Jam di Seluruh Indonesia',

    'buying_tips' => [
        [
            'no' => 1,
            'title' => 'Sesuaikan Pembelian Mobil dengan Budget',
            'description' => 'Hitung kemampuan DP dan cicilan bulanan. Idealnya cicilan tidak lebih dari 30% penghasilan.',
        ],
        [
            'no' => 2,
            'title' => 'Periksa Kondisi Mesin',
            'description' => 'Dengarkan suara mesin saat dingin, cek asap knalpot, rembesan oli, dan riwayat servis.',
        ],
        [
            'no' => 3,
            'title' => 'Lakukan Survei Terlebih Dahulu Sebelum Membeli',
            'description' => 'Bandingkan harga pasaran untuk model, tahun, dan kilometer yang setara.',
        ],
        [
            'no' => 4,
            'title' => 'Meneliti Kelengkapan Surat-Surat',
            'description' => 'Pastikan BPKB, STNK, faktur, serta nomor rangka dan mesin sesuai, dan pajak masih hidup.',
        ],
        [
            'no' => 5,
            'title' => 'Jangan Lupa Lakukan Test Drive',
            'description' => 'Rasakan langsung transmisi, rem, setir, dan suspensi di jalan sebelum memutuskan.',
        ],
    ],

    'how_it_works_buy' => [
        [
            'no' => 1,
            'title' => 'Pilih Mobil',
            'description' => 'Cari dan filter mobil second berkualitas sesuai merek, budget, dan kebutuhan.',
            'icon' => 'Search',
        ],
        [
            'no' => 2,
            'title' => 'Booking Test Drive',
            'description' => 'Jadwalkan test drive di showroom atau langsung di rumah Anda.',
            'icon' => 'CalendarCheck',
        ],
        [
            'no' => 3,
            'title' => 'Bayar Tunai / Kredit',
            'description' => 'Hitung simulasi cicilan, lalu bayar tunai atau ajukan kredit ke leasing partner.',
            'icon' => 'Wallet',
        ],
        [
            'no' => 4,
            'title' => 'Bawa Pulang Mobil',
            'description' => 'Surat lengkap, mobil siap pakai, plus benefit layanan darurat 24 jam.',
            'icon' => 'KeyRound',
        ],
    ],

    'how_it_works_sell' => [
        [
            'no' => 1,
            'title' => 'Isi Data Mobil',
            'description' => 'Masukkan merek, tahun, dan kilometer untuk mendapat estimasi harga instan.',
            'icon' => 'ClipboardList',
        ],
        [
            'no' => 2,
            'title' => 'Jadwalkan Inspeksi',
            'description' => 'Pilih tanggal dan jam, tim Auto18 akan memeriksa kondisi mobil Anda.',
            'icon' => 'CalendarCheck',
        ],
        [
            'no' => 3,
            'title' => 'Terima Penawaran',
            'description' => 'Dapatkan harga final setelah inspeksi, tanpa kewajiban untuk menjual.',
            'icon' => 'BadgePercent',
        ],
        [
            'no' => 4,
            'title' => 'Pembayaran Cepat',
            'description' => 'Setelah dokumen beres, dana langsung ditransfer ke rekening Anda.',
            'icon' => 'Banknote',
        ],
    ],

    'faqs_buy' => [
        [
            'q' => 'Apakah semua mobil di Auto18 sudah diinspeksi?',
            'a' => 'Ya. Setiap mobil melewati inspeksi mesin, transmisi, kaki-kaki, kelistrikan, interior, eksterior, dan dokumen. Nilai inspeksinya bisa dilihat di halaman detail mobil.',
        ],
        [
            'q' => 'Bisakah saya membeli mobil secara kredit?',
            'a' => 'Bisa. Gunakan simulasi kredit untuk memperkirakan DP dan cicilan per bulan. Persetujuan kredit dan bunga final mengikuti ketentuan leasing partner.',
        ],
        [
            'q' => 'Bagaimana cara booking test drive?',
            'a' => 'Buka detail mobil, klik "Booking Test Drive", lalu pilih tanggal, jam, dan lokasi (showroom atau rumah). Tim kami akan menghubungi untuk konfirmasi.',
        ],
        [
            'q' => 'Apakah bisa tukar tambah mobil lama saya?',
            'a' => 'Bisa. Pilih menu Jual Mobil lalu opsi "Tukar Tambah". Nilai mobil lama Anda akan dipotong dari harga mobil yang ingin dibeli.',
        ],
        [
            'q' => 'Apa itu benefit layanan darurat 24 jam?',
            'a' => 'Pembeli mobil di Auto18 mendapat layanan darurat seperti towing, jumper aki, ganti ban serep, hingga klaim kecelakaan. Hubungi call center 24 jam kami bila membutuhkan.',
        ],
    ],

    'faqs_sell' => [
        [
            'q' => 'Apakah estimasi harga di website sudah final?',
            'a' => 'Belum. Estimasi dihitung dari merek, tipe, tahun, kilometer, dan kondisi. Harga final diberikan setelah inspeksi langsung.',
        ],
        [
            'q' => 'Dokumen apa saja yang perlu disiapkan?',
            'a' => 'BPKB, STNK, faktur (jika ada), KTP pemilik sesuai BPKB, dan buku servis bila tersedia.',
        ],
        [
            'q' => 'Apakah saya wajib menjual setelah inspeksi?',
            'a' => 'Tidak. Inspeksi dan penawaran tidak mengikat, Anda bebas menerima atau menolak.',
        ],
        [
            'q' => 'Berapa lama proses pembayarannya?',
            'a' => 'Setelah harga disepakati dan dokumen lengkap, pembayaran diproses dengan transfer ke rekening Anda.',
        ],
    ],

    // Profil showroom bawaan (nilai awal tabel settings)
    'store' => [
        'name' => "Auto18",
        'tagline' => "Jual / Beli Mobil Second Berkualitas Hanya di Auto 18",
        'address' => "Showroom Auto18, Jakarta",
        'phone' => "021-31172266",
        'call_center' => "021-31172266",
        'whatsapp' => "",
        'email' => "",
        'instagram' => "auto18eighteen",
        'tiktok' => "autoeighteen",
        'youtube' => "auto eighteen 18",
        'support_partner' => "LEMONAIDE",
        'cashier_name' => "Admin Showroom",
        'receipt_footer' => "Terima kasih telah berbelanja di Auto18!
Layanan Darurat 24 Jam: 021-31172266",
        'tax_enabled' => false,
        'tax_rate' => 11,
        'credit_rate' => 7,
        'min_dp_percent' => 20,
    ],

    // Kredit foto mobil (Wikimedia Commons, CC BY-SA 4.0)
    'photo_credits' => [
        ['key' => 'xpander', 'title' => 'Mitsubishi Xpander 1.5 GLS 2023 (1).jpg', 'author' => 'Ethan Llamas', 'license' => 'CC BY-SA 4.0', 'sourceUrl' => 'https://commons.wikimedia.org/wiki/File:Mitsubishi_Xpander_1.5_GLS_2023_(1).jpg'],
        ['key' => 'fortuner', 'title' => '2016 Toyota Fortuner, West Surabaya.jpg', 'author' => 'Alex Neman', 'license' => 'CC BY-SA 4.0', 'sourceUrl' => 'https://commons.wikimedia.org/wiki/File:2016_Toyota_Fortuner,_West_Surabaya.jpg'],
        ['key' => 'pajero-sport', 'title' => '2017 Mitsubishi Pajero Sport, Malang.jpg', 'author' => 'Alex Neman', 'license' => 'CC BY-SA 4.0', 'sourceUrl' => 'https://commons.wikimedia.org/wiki/File:2017_Mitsubishi_Pajero_Sport,_Malang.jpg'],
        ['key' => 'avanza', 'title' => 'Toyota Avanza 1.5 G 2022.jpg', 'author' => 'Ethan Llamas', 'license' => 'CC BY-SA 4.0', 'sourceUrl' => 'https://commons.wikimedia.org/wiki/File:Toyota_Avanza_1.5_G_2022.jpg'],
        ['key' => 'innova', 'title' => '2016 Toyota Kijang Innova 2.0 Q (TGN140R) 2016-05-18.jpg', 'author' => 'Nathanferdinand', 'license' => 'CC BY-SA 4.0', 'sourceUrl' => 'https://commons.wikimedia.org/wiki/File:2016_Toyota_Kijang_Innova_2.0_Q_(TGN140R)_2016-05-18.jpg'],
        ['key' => 'brio', 'title' => '2019 Honda Brio RS 1.2 DD1 (20200225).jpg', 'author' => 'オーバードライブ83', 'license' => 'CC BY-SA 4.0', 'sourceUrl' => 'https://commons.wikimedia.org/wiki/File:2019_Honda_Brio_RS_1.2_DD1_(20200225).jpg'],
        ['key' => 'hrv', 'title' => '2016 Honda HR-V 1.5 E RU1 (20201113).jpg', 'author' => 'オーバードライブ83', 'license' => 'CC BY-SA 4.0', 'sourceUrl' => 'https://commons.wikimedia.org/wiki/File:2016_Honda_HR-V_1.5_E_RU1_(20201113).jpg'],
        ['key' => 'crv', 'title' => '2017 Honda CR-V 1.5 Turbo Prestige (front), Batu City.jpg', 'author' => 'Alex Neman', 'license' => 'CC BY-SA 4.0', 'sourceUrl' => 'https://commons.wikimedia.org/wiki/File:2017_Honda_CR-V_1.5_Turbo_Prestige_(front),_Batu_City.jpg'],
        ['key' => 'ertiga', 'title' => '2019 Suzuki Ertiga GX (Indonesia) front view.jpg', 'author' => 'Andra Febrian', 'license' => 'CC BY-SA 4.0', 'sourceUrl' => 'https://commons.wikimedia.org/wiki/File:2019_Suzuki_Ertiga_GX_(Indonesia)_front_view.jpg'],
        ['key' => 'terios', 'title' => '2018 Daihatsu Terios R, East Surabaya.jpg', 'author' => 'Alex Neman', 'license' => 'CC BY-SA 4.0', 'sourceUrl' => 'https://commons.wikimedia.org/wiki/File:2018_Daihatsu_Terios_R,_East_Surabaya.jpg'],
        ['key' => 'civic', 'title' => '2018 Honda Civic Saloon Front.jpg', 'author' => 'Vauxford', 'license' => 'CC BY-SA 4.0', 'sourceUrl' => 'https://commons.wikimedia.org/wiki/File:2018_Honda_Civic_Saloon_Front.jpg'],
        ['key' => 'yaris', 'title' => '2021 Toyota Yaris TRD Sportivo 1.5 (Indonesia) front view 01.jpg', 'author' => 'Andra Febrian', 'license' => 'CC BY-SA 4.0', 'sourceUrl' => 'https://commons.wikimedia.org/wiki/File:2021_Toyota_Yaris_TRD_Sportivo_1.5_(Indonesia)_front_view_01.jpg'],
    ],
];
