import React, { useState, useMemo, useRef, useEffect } from 'react';
import {
  Search,
  Plus,
  Minus,
  Trash2,
  ShoppingCart,
  CreditCard,
  X,
  PackageOpen,
  Wrench
} from 'lucide-react';
import { AnimatePresence, motion, useAnimationControls, useIsPresent } from 'motion/react';
import NumberFlow from '@number-flow/react';
import { useApp } from '../context/AppContext';
import { formatRupiah } from '../utils/formatters';
import Money from './Money';

const DISCOUNT_TYPES = [
  { id: 'nominal', label: 'Rp' },
  { id: 'percent', label: '%' },
];

const PILL_SPRING = { type: 'spring', bounce: 0.15, duration: 0.35 };

// Baris keranjang: masuk bergeser dari kanan, keluar dengan tinggi mengempis sambil memudar.
// Baris di bawahnya ikut naik mulus karena tinggi baris yang keluar menyusut per frame.
// height: 'auto' di ROW_ANIMATE memulihkan tinggi bila barang ditambah lagi saat barisnya
// masih mengempis. Opacity & tinggi tetap beranimasi untuk reduced motion (hanya x yang
// dimatikan MotionConfig), jadi keadaan akhirnya sama persis.
const ROW_INITIAL = { opacity: 0, x: 12 };
const ROW_ANIMATE = { opacity: 1, x: 0, height: 'auto' };
const ROW_EXIT = { opacity: 0, height: 0, transition: { duration: 0.18, ease: 'easeOut' } };
const ROW_TRANSITION = { default: PILL_SPRING, opacity: { duration: 0.15, ease: 'easeOut' } };

// Cadangan: tampilkan "Keranjang Masih Kosong" walau onExitComplete tidak sempat terpanggil
const EMPTY_FALLBACK_MS = 600;
const EMPTY_FADE = { duration: 0.15, ease: 'easeOut' };

// Lonjakan kecil kotak jumlah di kartu barang saat barang ditambahkan (null = mulai dari
// skala saat ini, supaya klik beruntun tidak membuat kotak melompat balik ke 1)
const BADGE_POP = { duration: 0.28, times: [0, 0.4, 1], ease: ['easeOut', 'easeInOut'] };

// Angka jumlah berganti cepat, sejalan dengan Money (tidak lebih lambat dari ~450 ms)
const COUNT_TIMING = { duration: 350, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' };
const COUNT_OPACITY_TIMING = { duration: 150, easing: 'ease-out' };

/**
 * Bilangan bulat yang bergulir saat berubah (jumlah item / qty). Seperti Money: NumberFlow
 * disembunyikan dari pembaca layar (ia melaporkan diri sebagai role="img") dan diganti
 * salinan teks biasa di span sr-only, sehingga textContent tetap mis. "3 item dipilih".
 */
function RollingCount({ value }) {
  return (
    <>
      <NumberFlow
        aria-hidden="true"
        value={value}
        locales="id-ID"
        transformTiming={COUNT_TIMING}
        opacityTiming={COUNT_OPACITY_TIMING}
      />
      <span className="sr-only">{value}</span>
    </>
  );
}

/**
 * Kotak kecil di kartu barang: ikon + saat belum di keranjang, angka qty saat sudah.
 * Satu elemen untuk kedua keadaan supaya lonjakan juga terjadi saat barang pertama kali
 * masuk (0 -> 1). Tidak beranimasi saat halaman dimuat / kartu muncul lagi karena filter.
 */
function TileCartBadge({ qty }) {
  const controls = useAnimationControls();
  const prevQtyRef = useRef(qty);

  useEffect(() => {
    const prevQty = prevQtyRef.current;
    prevQtyRef.current = qty;
    // Hanya saat jumlah bertambah (barang ditambahkan), bukan saat dikurangi/dihapus
    if (qty > prevQty) {
      controls.start({ scale: [null, 1.25, 1], transition: BADGE_POP });
    }
  }, [qty, controls]);

  const isInCart = qty > 0;
  return (
    <motion.span
      animate={controls}
      className={
        isInCart
          ? 'w-8 h-8 shrink-0 rounded-xl bg-brand-600 text-white font-black text-xs flex items-center justify-center'
          : 'w-8 h-8 shrink-0 rounded-xl bg-white/5 text-neutral-300 group-hover:bg-brand-600 group-hover:text-white flex items-center justify-center transition-colors'
      }
    >
      {isInCart ? qty : <Plus className="w-4 h-4" aria-hidden="true" />}
    </motion.span>
  );
}

/** Satu baris keranjang. Anak langsung AnimatePresence, jadi bisa beranimasi keluar. */
function CartRow({ item, onDecrease, onIncrease, onRemove }) {
  // false selama baris mengempis setelah dihapus
  const isPresent = useIsPresent();

  return (
    <motion.div
      initial={ROW_INITIAL}
      animate={ROW_ANIMATE}
      exit={ROW_EXIT}
      transition={ROW_TRANSITION}
      // clip (bukan hidden) hanya di sumbu Y: isi terpotong saat tinggi mengempis, tetapi
      // cincin fokus tombol hapus di tepi kanan tidak ikut terpotong
      className="overflow-y-clip"
    >
      <div
        // inert saat mengempis: tombol baris yang sudah dihapus tidak bisa difokus/diklik lagi
        // (React 18: '' memasang atribut, undefined melepas)
        inert={isPresent ? undefined : ''}
        className={`py-3 flex items-center justify-between gap-2 ${isPresent ? '' : 'pointer-events-none'}`}
      >
        <div className="flex-1 min-w-0">
          <h5 className="font-semibold text-sm text-white truncate" title={item.name}>
            {item.name}
          </h5>
          <div className="flex items-center gap-2 text-xs text-neutral-500 mt-0.5">
            <span>{formatRupiah(item.price)}</span>
            <span aria-hidden="true">•</span>
            <Money value={item.price * item.qty} className="font-bold text-neutral-200" />
          </div>
        </div>

        {/* Kontrol jumlah */}
        <div className="flex items-center gap-1.5 bg-white/5 p-1 rounded-xl shrink-0">
          <button
            type="button"
            onClick={onDecrease}
            aria-label={`Kurangi jumlah ${item.name}`}
            className="w-6 h-6 rounded-lg bg-ink-600 hover:bg-brand-600 text-white flex items-center justify-center transition-colors"
          >
            <Minus className="w-3 h-3" aria-hidden="true" />
          </button>
          <span className="w-7 text-center font-bold text-xs text-white">
            <RollingCount value={item.qty} />
          </span>
          <button
            type="button"
            onClick={onIncrease}
            aria-label={`Tambah jumlah ${item.name}`}
            className="w-6 h-6 rounded-lg bg-ink-600 hover:bg-brand-600 text-white flex items-center justify-center transition-colors"
          >
            <Plus className="w-3 h-3" aria-hidden="true" />
          </button>
        </div>

        <button
          type="button"
          onClick={onRemove}
          className="p-1.5 shrink-0 text-neutral-500 hover:text-brand-400 rounded-lg transition-colors"
          title="Hapus item"
          aria-label={`Hapus ${item.name} dari keranjang`}
        >
          <Trash2 className="w-4 h-4" aria-hidden="true" />
        </button>
      </div>
    </motion.div>
  );
}

export default function PosTerminal() {
  const {
    products,
    categories,
    cart,
    addToCart,
    updateCartQty,
    removeFromCart,
    clearCart,
    cartDiscount,
    discountType,
    setDiscountType,
    discountValue,
    setDiscountValue,
    cartSubtotal,
    cartTax,
    cartTotal,
    cartItemsCount,
    storeInfo,
    setIsPaymentModalOpen
  } = useApp();

  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');

  // Saat keranjang menjadi kosong (hapus item terakhir / Kosongkan / transaksi selesai),
  // baris-barisnya masih mengempis ±180 ms. "Keranjang Masih Kosong" baru ditampilkan
  // setelah itu supaya tidak bertumpuk dengan baris yang sedang keluar. Disesuaikan saat
  // render (bukan di effect) agar tidak ada satu frame pun keduanya tampil bersamaan.
  const [prevCartLength, setPrevCartLength] = useState(cart.length);
  const [rowsLeaving, setRowsLeaving] = useState(false);
  // Keadaan kosong hanya memudar masuk bila muncul karena keranjang dikosongkan,
  // bukan saat halaman kasir pertama kali dibuka
  const [emptyFadesIn, setEmptyFadesIn] = useState(false);
  if (cart.length !== prevCartLength) {
    const becameEmpty = prevCartLength > 0 && cart.length === 0;
    setPrevCartLength(cart.length);
    setRowsLeaving(becameEmpty);
    if (becameEmpty) setEmptyFadesIn(true);
  }
  const showEmptyCart = cart.length === 0 && !rowsLeaving;

  useEffect(() => {
    if (!rowsLeaving) return undefined;
    const timer = setTimeout(() => setRowsLeaving(false), EMPTY_FALLBACK_MS);
    return () => clearTimeout(timer);
  }, [rowsLeaving]);

  // Filter products by category and search
  const filteredProducts = useMemo(() => {
    return products.filter(p => {
      const matchCat = selectedCategory === 'all' || p.categoryId === selectedCategory;
      const matchSearch = p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                          p.sku.toLowerCase().includes(searchQuery.toLowerCase()) ||
                          (p.barcode && p.barcode.includes(searchQuery));
      return matchCat && matchSearch;
    });
  }, [products, selectedCategory, searchQuery]);

  const handleResetDiscount = () => {
    setDiscountValue('');
  };

  return (
    <div className={`max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 ${cart.length > 0 ? 'pb-28 lg:pb-8' : ''}`}>
      {/* Banner halaman */}
      <div className="a18-stripes-soft flex flex-col gap-4 rounded-3xl p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500">Kasir Showroom</span>
          <h1 className="a18-heading mt-1 text-2xl sm:text-3xl">Kasir Aksesoris &amp; Perawatan</h1>
          <p className="mt-1.5 max-w-xl text-sm text-neutral-400">
            Transaksi cepat untuk oli, aki, ban, aksesoris, dan jasa showroom Auto18.
          </p>
        </div>
        <div className="a18-pill shrink-0 px-5 py-3 text-center">
          <p className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Keranjang</p>
          <p className="font-display text-xl font-black text-white"><RollingCount value={cartItemsCount} /> item</p>
        </div>
      </div>

      <div className="mt-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {/* KOLOM KIRI: Katalog barang & jasa */}
        <div className="lg:col-span-7 xl:col-span-8 space-y-4">

          {/* Pencarian & filter kategori */}
          <div className="a18-card p-4 space-y-3">
            <div className="relative">
              <Search className="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-500 pointer-events-none" aria-hidden="true" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Cari oli, aki, ban, kode SKU, atau scan barcode..."
                aria-label="Cari barang atau jasa"
                className="a18-input pl-11 pr-10"
              />
              {searchQuery && (
                <button
                  type="button"
                  onClick={() => setSearchQuery('')}
                  aria-label="Hapus pencarian"
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-500 hover:text-white p-1 transition-colors"
                >
                  <X className="w-4 h-4" />
                </button>
              )}
            </div>

            {/* Chip kategori */}
            <div className="flex items-center gap-2 overflow-x-auto pb-1">
              <button
                type="button"
                onClick={() => setSelectedCategory('all')}
                aria-pressed={selectedCategory === 'all'}
                className={`px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-colors ${
                  selectedCategory === 'all'
                    ? 'bg-brand-600 text-white'
                    : 'bg-white/5 text-neutral-400 hover:bg-white/10 hover:text-white'
                }`}
              >
                Semua ({products.length})
              </button>
              {categories.map(cat => {
                const count = products.filter(p => p.categoryId === cat.id).length;
                const isSelected = selectedCategory === cat.id;
                return (
                  <button
                    key={cat.id}
                    type="button"
                    onClick={() => setSelectedCategory(cat.id)}
                    aria-pressed={isSelected}
                    className={`px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-colors ${
                      isSelected
                        ? 'bg-brand-600 text-white'
                        : 'bg-white/5 text-neutral-400 hover:bg-white/10 hover:text-white'
                    }`}
                  >
                    {cat.name} ({count})
                  </button>
                );
              })}
            </div>
          </div>

          {/* Grid produk */}
          {filteredProducts.length === 0 ? (
            <div className="a18-card p-12 text-center">
              <PackageOpen className="w-12 h-12 text-neutral-700 mx-auto mb-3" aria-hidden="true" />
              <h3 className="font-display font-black uppercase tracking-wide text-white">Barang Tidak Ditemukan</h3>
              <p className="text-xs text-neutral-500 mt-1">
                Coba kata kunci lain, misalnya "oli", "aki", atau "kaca film", atau ganti filter kategori.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3.5">
              {filteredProducts.map(product => {
                const isOutOfStock = product.stock <= 0;
                const isLowStock = product.stock > 0 && product.stock <= product.minStock;
                const inCart = cart.find(c => c.id === product.id);

                return (
                  <button
                    key={product.id}
                    type="button"
                    disabled={isOutOfStock}
                    onClick={() => addToCart(product)}
                    aria-label={`Tambah ${product.name} ke keranjang`}
                    className={`group a18-card p-3.5 flex flex-col justify-between text-left transition-colors ${
                      isOutOfStock
                        ? 'opacity-50 cursor-not-allowed'
                        : 'hover:border-brand-600 active:scale-[0.98]'
                    }`}
                  >
                    {/* Badge atas */}
                    <div className="flex items-center justify-between gap-1 mb-2 overflow-hidden">
                      <span className="text-[10px] font-mono text-neutral-500 bg-white/5 px-1.5 py-0.5 rounded truncate">
                        {product.sku}
                      </span>
                      {isOutOfStock ? (
                        <span className="text-[10px] font-bold px-2 py-0.5 rounded-full border border-brand-500/40 bg-brand-600/20 text-brand-300">
                          Habis
                        </span>
                      ) : isLowStock ? (
                        <span className="text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-500/30 bg-amber-500/15 text-amber-300 whitespace-nowrap">
                          Sisa {product.stock}
                        </span>
                      ) : (
                        <span className="text-[10px] text-neutral-500 whitespace-nowrap">
                          Stok {product.stock} {product.unit}
                        </span>
                      )}
                    </div>

                    {/* Nama barang */}
                    <div className="mb-3 flex-1">
                      <span className="block font-bold text-sm text-white group-hover:text-brand-400 line-clamp-2 leading-snug transition-colors">
                        {product.name}
                      </span>
                      <span className="block text-[11px] text-neutral-500 mt-0.5 truncate">{product.categoryName}</span>
                    </div>

                    {/* Harga & tombol tambah */}
                    <div className="flex items-center justify-between gap-2 pt-2 border-t border-white/10">
                      <div className="min-w-0">
                        <span className="text-[10px] uppercase tracking-wider text-neutral-500 block leading-none">Harga</span>
                        <span className="font-bold text-sm text-white">
                          {formatRupiah(product.sellPrice)}
                        </span>
                      </div>

                      <TileCartBadge qty={inCart ? inCart.qty : 0} />
                    </div>
                  </button>
                );
              })}
            </div>
          )}
        </div>

        {/* KOLOM KANAN: Keranjang kasir */}
        <div className="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-24">
          <div className="a18-card flex flex-col lg:h-[calc(100vh-8rem)]">

            {/* Header keranjang */}
            <div className="p-4 border-b border-white/10 flex items-center justify-between gap-2">
              <div className="flex items-center gap-2 min-w-0">
                <span className="p-2 rounded-xl bg-brand-600/20 text-brand-400 border border-brand-500/30">
                  <ShoppingCart className="w-5 h-5" aria-hidden="true" />
                </span>
                <div className="min-w-0">
                  <h2 className="font-display font-black uppercase tracking-wide text-white text-base">Keranjang Kasir</h2>
                  <p className="text-xs text-neutral-400"><RollingCount value={cartItemsCount} /> item dipilih</p>
                </div>
              </div>
              {cart.length > 0 && (
                <button
                  type="button"
                  onClick={clearCart}
                  className="a18-btn-ghost shrink-0 px-2.5 py-1.5 text-xs text-neutral-400 hover:text-brand-400"
                >
                  <Trash2 className="w-3.5 h-3.5" aria-hidden="true" />
                  <span>Kosongkan</span>
                </button>
              )}
            </div>

            {/* Daftar item */}
            {/* overflow-x-hidden: baris yang masuk bergeser 12px dari kanan tanpa memunculkan
                scrollbar horizontal sesaat */}
            <div className="flex-1 overflow-y-auto overflow-x-hidden p-4 min-h-[8rem] lg:min-h-0">
              {showEmptyCart && (
                <motion.div
                  initial={emptyFadesIn ? { opacity: 0 } : false}
                  animate={{ opacity: 1 }}
                  transition={EMPTY_FADE}
                  className="h-full flex flex-col items-center justify-center text-center py-8"
                >
                  <div className="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mb-3 text-neutral-600">
                    <Wrench className="w-8 h-8" aria-hidden="true" />
                  </div>
                  <p className="font-bold text-white">Keranjang Masih Kosong</p>
                  <p className="text-xs text-neutral-500 mt-1 max-w-xs">
                    Pilih oli, aki, ban, aksesoris, atau jasa showroom di katalog untuk memulai transaksi.
                  </p>
                </motion.div>
              )}
              {/* Daftar tetap terpasang (hanya disembunyikan saat kosong) supaya AnimatePresence
                  tidak ikut di-mount ulang: baris pertama tetap bergeser masuk, dan baris
                  terakhir tetap bisa mengempis keluar. -my-3 menggantikan first:pt-0/last:pb-0
                  lama (jarak tepi tetap 16px) sehingga tidak ada lompatan padding saat baris
                  pertama/terakhir selesai keluar. */}
              <div className={`-my-3 divide-y divide-white/10 ${showEmptyCart ? 'hidden' : ''}`}>
                <AnimatePresence initial={false} onExitComplete={() => setRowsLeaving(false)}>
                  {cart.map(item => (
                    <CartRow
                      key={item.id}
                      item={item}
                      onDecrease={() => updateCartQty(item.id, item.qty - 1)}
                      onIncrease={() => updateCartQty(item.id, item.qty + 1)}
                      onRemove={() => removeFromCart(item.id)}
                    />
                  ))}
                </AnimatePresence>
              </div>
            </div>

            {/* Diskon, total & checkout */}
            {cart.length > 0 && (
              <div className="p-4 bg-ink-900 border-t border-white/10 rounded-b-2xl space-y-3">

                {/* Diskon */}
                <div>
                  <span className="a18-label">Diskon Transaksi</span>
                  <div className="flex items-center gap-2">
                    <div className="isolate flex rounded-xl border border-white/15 p-0.5 shrink-0">
                      {DISCOUNT_TYPES.map(({ id, label }) => {
                        const isSelected = discountType === id;
                        return (
                          <button
                            key={id}
                            type="button"
                            onClick={() => setDiscountType(id)}
                            aria-pressed={isSelected}
                            className={`relative px-2.5 py-1 rounded-lg text-xs font-bold transition-colors ${
                              isSelected ? 'text-white' : 'text-neutral-400 hover:text-white'
                            }`}
                          >
                            {isSelected && (
                              <motion.span
                                layoutId="pos-discount-type-pill"
                                layoutDependency={discountType}
                                transition={PILL_SPRING}
                                aria-hidden="true"
                                className="absolute inset-0 z-[1] bg-brand-600"
                                style={{ borderRadius: 8 }}
                              />
                            )}
                            <span className="relative z-10">{label}</span>
                          </button>
                        );
                      })}
                    </div>
                    <input
                      type="number"
                      min={0}
                      value={discountValue}
                      onChange={(e) => setDiscountValue(e.target.value)}
                      placeholder={discountType === 'percent' ? 'Potongan (%)' : 'Potongan (Rp)'}
                      aria-label="Nilai diskon"
                      className="a18-input flex-1 min-w-0 py-1.5 text-xs"
                    />
                  </div>
                </div>

                {/* Rincian total */}
                <div className="space-y-1.5 text-xs text-neutral-400">
                  <div className="flex justify-between">
                    <span>Subtotal</span>
                    <span className="font-semibold text-white"><Money value={cartSubtotal} /></span>
                  </div>
                  {cartDiscount > 0 && (
                    <div className="flex justify-between text-brand-400">
                      <span className="flex items-center gap-2">
                        Potongan Diskon
                        <button
                          type="button"
                          onClick={handleResetDiscount}
                          className="text-[10px] uppercase tracking-wider text-neutral-500 underline hover:text-white"
                        >
                          Batal
                        </button>
                      </span>
                      <span className="font-semibold">-<Money value={cartDiscount} /></span>
                    </div>
                  )}
                  {storeInfo.taxEnabled && (
                    <div className="flex justify-between">
                      <span>PPN ({storeInfo.taxRate}%)</span>
                      <span className="font-semibold text-white"><Money value={cartTax} /></span>
                    </div>
                  )}
                  <div className="flex items-baseline justify-between gap-2 pt-2 border-t border-white/10">
                    <span className="font-display text-sm font-black uppercase tracking-wide text-white">Total Bayar</span>
                    <span className="font-display text-xl font-black text-white"><Money value={cartTotal} /></span>
                  </div>
                </div>

                {/* Tombol bayar */}
                <button
                  type="button"
                  onClick={() => setIsPaymentModalOpen(true)}
                  className="a18-btn-primary w-full py-3.5"
                >
                  <CreditCard className="w-5 h-5" aria-hidden="true" />
                  <span>Bayar Sekarang</span>
                </button>
              </div>
            )}

          </div>
        </div>

      </div>

      {/* Bar bayar mengambang (mobile) */}
      {cart.length > 0 && (
        <div className="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-ink-900/95 p-3 backdrop-blur-sm lg:hidden">
          <div className="mx-auto flex max-w-7xl items-center gap-3">
            <div className="min-w-0 flex-1">
              <p className="text-[10px] font-bold uppercase tracking-wider text-neutral-500">
                <RollingCount value={cartItemsCount} /> item
              </p>
              {/* NumberFlow = inline-block nowrap: tanpa elipsis, tapi s.d. Rp 9.999.999.999 (~180px) muat di layar 360px */}
              <p className="truncate font-display text-lg font-black text-white"><Money value={cartTotal} /></p>
            </div>
            <button
              type="button"
              onClick={() => setIsPaymentModalOpen(true)}
              className="a18-btn-primary shrink-0 px-5 py-3"
            >
              <CreditCard className="h-4 w-4" aria-hidden="true" />
              Bayar
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
