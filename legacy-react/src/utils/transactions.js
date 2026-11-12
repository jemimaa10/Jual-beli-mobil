// Utilitas transaksi yang dipakai Dashboard, Laporan, dan Nota.

// Transaksi mobil yang dibatalkan ("Batalkan status terjual") ditandai status 'void'
// dan tidak boleh ikut dihitung di omset, laba, maupun jumlah unit terjual.
export const isActiveTransaction = (trx) => trx?.status !== 'void';

// Omset bersih = total dibayar pelanggan dikurangi PPN yang dipungut,
// sehingga Omset - HPP = Laba (totalProfit juga tidak memasukkan PPN).
export const netRevenue = (trx) => (Number(trx?.total) || 0) - (Number(trx?.tax) || 0);
