// Kunci scroll halaman yang aman untuk modal bertumpuk (mis. Detail Mobil + Proses Penjualan).
// Setiap modal memanggil lockScroll() saat terbuka dan unlockScroll() saat tertutup;
// scroll baru dilepas setelah modal terakhir tertutup, apa pun urutan cleanup React.
let lockCount = 0;

export function lockScroll() {
  lockCount += 1;
  if (lockCount === 1) document.body.style.overflow = 'hidden';
}

export function unlockScroll() {
  if (lockCount === 0) return;
  lockCount -= 1;
  if (lockCount === 0) document.body.style.overflow = '';
}
