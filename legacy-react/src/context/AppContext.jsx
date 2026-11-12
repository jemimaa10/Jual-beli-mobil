import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import {
  INITIAL_PRODUCTS,
  INITIAL_CATEGORIES,
  INITIAL_SUPPLIERS,
  INITIAL_STORE_INFO,
  INITIAL_TRANSACTIONS,
  INITIAL_CARS,
  INITIAL_SELL_REQUESTS,
  INITIAL_TEST_DRIVES,
  INSPECTION_CATEGORIES
} from '../data/initialData';
import { ALL_TAB_IDS, DEFAULT_TAB } from '../data/navigation';
import { generateTrxCode } from '../utils/formatters';
import { toast } from '../utils/toast';
import {
  calcInstallment,
  getCarName,
  generateCarCode,
  generateSellRequestCode,
  generateTestDriveCode
} from '../utils/carUtils';

const AppContext = createContext();

// Prefix baru agar data demo Auto18 tidak tercampur data lama "kasir_*"
const STORAGE_PREFIX = 'auto18_';

// 1. Storage Helpers
const loadLocal = (key, fallback) => {
  try {
    const item = localStorage.getItem(STORAGE_PREFIX + key);
    return item ? JSON.parse(item) : fallback;
  } catch (e) {
    console.error('Failed to load ' + key, e);
    return fallback;
  }
};

function usePersistentState(key, fallback) {
  const [value, setValue] = useState(() => loadLocal(key, fallback));
  useEffect(() => {
    try {
      localStorage.setItem(STORAGE_PREFIX + key, JSON.stringify(value));
    } catch (e) {
      console.error('Failed to save ' + key, e);
    }
  }, [key, value]);

  // Sinkron antar-tab: tab lain yang menulis key yang sama (mis. #sell-car mengirim
  // permintaan sementara tab ini terbuka di #cars) tidak boleh membuat data di sini hilang.
  useEffect(() => {
    const onStorage = (e) => {
      if (e.storageArea !== localStorage) return;
      if (e.key === null) { setValue(fallback); return; } // localStorage.clear() di tab lain
      if (e.key !== STORAGE_PREFIX + key) return;
      if (e.newValue === null) { setValue(fallback); return; } // key dihapus
      try {
        setValue(JSON.parse(e.newValue));
      } catch (err) {
        console.error('Failed to sync ' + key, err);
      }
    };
    window.addEventListener('storage', onStorage);
    return () => window.removeEventListener('storage', onStorage);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [key]);

  return [value, setValue];
}

const readTabFromHash = () => {
  const hash = window.location.hash.replace('#', '');
  return ALL_TAB_IDS.includes(hash) ? hash : DEFAULT_TAB;
};

const toNumber = (value, fallback = 0) => {
  const n = Number(value);
  return Number.isFinite(n) ? n : fallback;
};

// Rapikan tipe data mobil dari form (string → number, fitur → array, skor inspeksi)
const normalizeCar = (car) => {
  const inspection = Object.fromEntries(
    INSPECTION_CATEGORIES.map(c => [c.key, Math.min(100, Math.max(0, toNumber(car.inspection?.[c.key], 90)))])
  );
  const scores = Object.values(inspection);
  const features = Array.isArray(car.features)
    ? car.features
    : String(car.features || '').split(',');

  return {
    ...car,
    year: toNumber(car.year, new Date().getFullYear()),
    price: toNumber(car.price),
    buyPrice: toNumber(car.buyPrice),
    mileage: toNumber(car.mileage),
    engineCc: toNumber(car.engineCc),
    seats: toNumber(car.seats, 5),
    ownerCount: toNumber(car.ownerCount, 1),
    features: features.map(f => String(f).trim()).filter(Boolean),
    imageUrl: String(car.imageUrl || '').trim(),
    inspection,
    inspectionScore: Math.round(scores.reduce((a, b) => a + b, 0) / scores.length),
  };
};

export function AppProvider({ children }) {
  // Navigasi tab, disinkronkan dengan hash URL (#buy-car, #cars, ...)
  const [activeTab, setActiveTabState] = useState(readTabFromHash);
  const [products, setProducts] = usePersistentState('products', INITIAL_PRODUCTS);
  const [categories, setCategories] = usePersistentState('categories', INITIAL_CATEGORIES);
  const [suppliers, setSuppliers] = usePersistentState('suppliers', INITIAL_SUPPLIERS);
  const [transactions, setTransactions] = usePersistentState('transactions', INITIAL_TRANSACTIONS);
  const [storeInfo, setStoreInfo] = usePersistentState('storeInfo', INITIAL_STORE_INFO);

  // Modul mobil bekas
  const [cars, setCars] = usePersistentState('cars', INITIAL_CARS);
  const [sellRequests, setSellRequests] = usePersistentState('sellRequests', INITIAL_SELL_REQUESTS);
  const [testDrives, setTestDrives] = usePersistentState('testDrives', INITIAL_TEST_DRIVES);
  const [favoriteCarIds, setFavoriteCarIds] = usePersistentState('favoriteCarIds', []);
  const [selectedCarId, setSelectedCarId] = useState(null);
  const [checkoutCarId, setCheckoutCarId] = useState(null);

  // Cart state
  const [cart, setCart] = useState([]);
  const [discountType, setDiscountType] = useState('nominal'); // 'nominal' | 'percent'
  const [discountValue, setDiscountValue] = useState(''); // nilai mentah yang diketik kasir
  const [currentReceipt, setCurrentReceipt] = useState(null);
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);
  const [isReceiptModalOpen, setIsReceiptModalOpen] = useState(false);
  const [isSettingsModalOpen, setIsSettingsModalOpen] = useState(false);

  // Field baru di INITIAL_STORE_INFO tetap muncul walau storeInfo lama tersimpan
  const mergedStoreInfo = { ...INITIAL_STORE_INFO, ...storeInfo };

  // Tab navigation <-> URL hash
  const setActiveTab = useCallback((tab) => {
    if (ALL_TAB_IDS.includes(tab)) setActiveTabState(tab);
  }, []);

  useEffect(() => {
    if (window.location.hash !== '#' + activeTab) {
      if (window.location.hash) {
        window.history.pushState(null, '', '#' + activeTab);
      } else {
        window.history.replaceState(null, '', '#' + activeTab);
      }
    }
    window.scrollTo({ top: 0 });
  }, [activeTab]);

  useEffect(() => {
    const onHashChange = () => setActiveTabState(readTabFromHash());
    window.addEventListener('hashchange', onHashChange);
    window.addEventListener('popstate', onHashChange);
    return () => {
      window.removeEventListener('hashchange', onHashChange);
      window.removeEventListener('popstate', onHashChange);
    };
  }, []);

  // Product CRUD
  const addProduct = (newProduct) => {
    const product = {
      ...newProduct,
      id: 'p-' + Date.now(),
      stock: Number(newProduct.stock) || 0,
      buyPrice: Number(newProduct.buyPrice) || 0,
      sellPrice: Number(newProduct.sellPrice) || 0,
      minStock: Number(newProduct.minStock) || 5,
    };
    setProducts(prev => [product, ...prev]);
    return product;
  };

  const updateProduct = (id, updated) => {
    setProducts(prev => prev.map(p => p.id === id ? { ...p, ...updated } : p));
  };

  const deleteProduct = (id) => {
    setProducts(prev => prev.filter(p => p.id !== id));
  };

  const adjustStock = (productId, deltaQty, reason = 'Penyesuaian manual') => {
    setProducts(prev => prev.map(p => {
      if (p.id === productId) {
        const nextStock = Math.max(0, p.stock + deltaQty);
        return { ...p, stock: nextStock };
      }
      return p;
    }));
  };

  // Supplier & Restock
  const addSupplier = (newSup) => {
    const sup = { ...newSup, id: 'sup-' + Date.now() };
    setSuppliers(prev => [...prev, sup]);
  };

  const updateSupplier = (id, updated) => {
    setSuppliers(prev => prev.map(s => s.id === id ? { ...s, ...updated } : s));
  };

  const deleteSupplier = (id) => {
    setSuppliers(prev => prev.filter(s => s.id !== id));
  };

  const recordPurchase = (supplierId, items, invoiceNo = '', notes = '') => {
    // items: [{ productId, qty, buyPrice }]
    items.forEach(item => {
      adjustStock(item.productId, Number(item.qty), 'Stok Masuk: ' + invoiceNo);
      // optionally update buyPrice if changed
      if (item.buyPrice) {
        updateProduct(item.productId, { buyPrice: Number(item.buyPrice) });
      }
    });
  };

  // Category CRUD
  const addCategory = (name, icon = 'Package', color = 'red') => {
    const cat = { id: 'cat-' + Date.now(), name, icon, color };
    setCategories(prev => [...prev, cat]);
  };

  const deleteCategory = (id) => {
    setCategories(prev => prev.filter(c => c.id !== id));
  };

  // Cart operations
  const addToCart = (product) => {
    if (product.stock <= 0) {
      toast.error('Maaf, stok barang ini sedang habis!');
      return;
    }

    // Batas stok dicek SEBELUM setCart: efek samping (toast) tidak boleh ada di dalam
    // updater setState, karena React boleh menjalankan updater lebih dari sekali.
    const inCart = cart.find(item => item.id === product.id);
    if (inCart && inCart.qty >= product.stock) {
      toast.error('Jumlah melebihi stok yang tersedia (' + product.stock + ')');
      return;
    }

    setCart(prev => {
      const existing = prev.find(item => item.id === product.id);
      if (existing) {
        // Penjaga tetap (tanpa efek samping) untuk klik beruntun sebelum render berikutnya
        if (existing.qty >= product.stock) return prev;
        return prev.map(item => item.id === product.id ? { ...item, qty: item.qty + 1 } : item);
      } else {
        return [...prev, {
          id: product.id,
          sku: product.sku,
          name: product.name,
          unit: product.unit,
          price: product.sellPrice,
          buyPrice: product.buyPrice,
          stock: product.stock,
          qty: 1
        }];
      }
    });
  };

  const updateCartQty = (productId, newQty) => {
    if (newQty <= 0) {
      removeFromCart(productId);
      return;
    }
    const prod = products.find(p => p.id === productId);
    if (prod && newQty > prod.stock) {
      toast.error('Jumlah melebihi sisa stok (' + prod.stock + ')');
      return;
    }
    setCart(prev => prev.map(item => item.id === productId ? { ...item, qty: newQty } : item));
  };

  const removeFromCart = (productId) => {
    setCart(prev => prev.filter(item => item.id !== productId));
  };

  const clearCart = () => {
    setCart([]);
    setDiscountType('nominal');
    setDiscountValue('');
  };

  // Calculate cart totals. Diskon dihitung ulang setiap render dari subtotal yang
  // sedang berjalan (bukan disimpan sebagai Rupiah tetap), jadi ia otomatis mengikuti
  // ketika item ditambah/dihapus dan tidak pernah melebihi subtotal.
  const cartSubtotal = cart.reduce((acc, item) => acc + (item.price * item.qty), 0);
  const rawDiscount = discountType === 'percent'
    ? Math.round(cartSubtotal * Math.min(100, Math.max(0, toNumber(discountValue))) / 100)
    : Math.max(0, toNumber(discountValue));
  const cartDiscount = Math.min(cartSubtotal, rawDiscount);
  const cartTax = mergedStoreInfo.taxEnabled ? Math.round(Math.max(0, cartSubtotal - cartDiscount) * (mergedStoreInfo.taxRate / 100)) : 0;
  const cartTotal = Math.max(0, cartSubtotal - cartDiscount + cartTax);
  const cartItemsCount = cart.reduce((acc, item) => acc + item.qty, 0);

  // Complete Transaction (kasir aksesoris / retail)
  const completeTransaction = ({ paymentMethod, paidAmount, customerName = 'Pelanggan Umum' }) => {
    if (cart.length === 0) return null;

    const changeAmount = Math.max(0, paidAmount - cartTotal);

    // Calculate total profit
    const totalProfit = cart.reduce((acc, item) => {
      const itemProfit = (item.price - (item.buyPrice || 0)) * item.qty;
      return acc + itemProfit;
    }, 0) - cartDiscount;

    const trx = {
      id: 'trx-' + Date.now(),
      code: generateTrxCode(),
      date: new Date().toISOString(),
      saleType: 'retail',
      customerName: customerName || 'Pelanggan Umum',
      cashier: mergedStoreInfo.cashierName || 'Admin Showroom',
      items: cart.map(item => ({
        id: item.id,
        sku: item.sku,
        name: item.name,
        unit: item.unit,
        price: item.price,
        buyPrice: item.buyPrice,
        qty: item.qty,
        subtotal: item.price * item.qty
      })),
      subtotal: cartSubtotal,
      discount: cartDiscount,
      tax: cartTax,
      total: cartTotal,
      paymentMethod, // 'cash', 'qris', 'transfer'
      paidAmount,
      changeAmount,
      totalProfit
    };

    // 1. Deduct product stock
    setProducts(prev => prev.map(p => {
      const inCart = cart.find(c => c.id === p.id);
      if (inCart) {
        return { ...p, stock: Math.max(0, p.stock - inCart.qty) };
      }
      return p;
    }));

    // 2. Save transaction
    setTransactions(prev => [trx, ...prev]);

    // 3. Clear cart & show receipt
    clearCart();
    setCurrentReceipt(trx);
    setIsPaymentModalOpen(false);
    setIsReceiptModalOpen(true);

    return trx;
  };

  // ---------------------------------------------------------------------------
  // Stok mobil
  // ---------------------------------------------------------------------------
  const addCar = (newCar) => {
    const car = normalizeCar({
      status: 'available',
      imageKey: '',
      isFeatured: false,
      soldAt: null,
      soldPrice: null,
      soldTransactionId: null,
      ...newCar,
      id: 'car-' + Date.now(),
      code: newCar.code || generateCarCode(),
      createdAt: new Date().toISOString(),
    });
    setCars(prev => [car, ...prev]);
    return car;
  };

  const updateCar = (id, updated) => {
    setCars(prev => prev.map(c => c.id === id ? normalizeCar({ ...c, ...updated }) : c));
  };

  const deleteCar = (id) => {
    setCars(prev => prev.filter(c => c.id !== id));
    setFavoriteCarIds(prev => prev.filter(favId => favId !== id));
    if (selectedCarId === id) setSelectedCarId(null);
    if (checkoutCarId === id) setCheckoutCarId(null);
  };

  // status: 'available' | 'booked' | 'sold'
  const setCarStatus = (id, status) => {
    setCars(prev => prev.map(c => {
      if (c.id !== id) return c;
      if (status === 'sold') return { ...c, status, soldAt: c.soldAt || new Date().toISOString() };
      return { ...c, status, soldAt: null, soldPrice: null, soldTransactionId: null };
    }));
  };

  // Membatalkan status "terjual": transaksi penjualannya ditandai 'void' (bukan dihapus)
  // supaya tidak lagi dihitung sebagai omset/laba/unit terjual di Dashboard & Laporan,
  // lalu mobilnya dikembalikan ke status tersedia. Mobil tukar tambah yang sudah masuk
  // stok dari penjualan ini TIDAK ikut ditarik kembali.
  const voidCarSale = (carId) => {
    const car = cars.find(c => c.id === carId);
    if (!car) return null;

    let voidedTransaction = null;
    let tradeInRequestId = null;
    if (car.soldTransactionId) {
      setTransactions(prev => prev.map(t => {
        if (t.id !== car.soldTransactionId) return t;
        voidedTransaction = { ...t, status: 'void', voidedAt: new Date().toISOString() };
        tradeInRequestId = t.tradeIn?.requestId || null;
        return voidedTransaction;
      }));
    }

    setCars(prev => prev.map(c => c.id === carId
      ? { ...c, status: 'available', soldAt: null, soldPrice: null, soldTransactionId: null }
      : c
    ));

    return { voidedTransaction, tradeInRequestId };
  };

  const toggleFavoriteCar = (id) => {
    setFavoriteCarIds(prev => prev.includes(id) ? prev.filter(favId => favId !== id) : [...prev, id]);
  };

  const selectedCar = cars.find(c => c.id === selectedCarId) || null;
  const checkoutCar = cars.find(c => c.id === checkoutCarId) || null;

  const openCarDetail = (id) => setSelectedCarId(id);
  const closeCarDetail = () => setSelectedCarId(null);
  const openCarCheckout = (id) => setCheckoutCarId(id);
  const closeCarCheckout = () => setCheckoutCarId(null);

  // ---------------------------------------------------------------------------
  // Permintaan jual mobil / tukar tambah
  // ---------------------------------------------------------------------------
  const addSellRequest = (data) => {
    const request = {
      type: 'sell',
      email: '',
      estimateLow: 0,
      estimateHigh: 0,
      tradeInCarId: null,
      notes: '',
      offerPrice: 0,
      purchasedCarId: null,
      ...data,
      year: toNumber(data.year, new Date().getFullYear()),
      mileage: toNumber(data.mileage),
      askingPrice: toNumber(data.askingPrice),
      id: 'sell-' + Date.now(),
      code: generateSellRequestCode(),
      status: 'new',
      createdAt: new Date().toISOString(),
    };
    setSellRequests(prev => [request, ...prev]);
    return request;
  };

  const updateSellRequest = (id, updated) => {
    setSellRequests(prev => prev.map(r => r.id === id ? { ...r, ...updated } : r));
  };

  const deleteSellRequest = (id) => {
    setSellRequests(prev => prev.filter(r => r.id !== id));
  };

  // Showroom membeli mobil pelanggan → masuk ke stok mobil (status tersedia)
  const purchaseFromSellRequest = (requestId, { offerPrice, sellPrice } = {}) => {
    const request = sellRequests.find(r => r.id === requestId);
    if (!request || request.status === 'purchased') return null;

    const buyPrice = toNumber(offerPrice, request.offerPrice) || request.offerPrice || request.askingPrice;
    const price = toNumber(sellPrice) || Math.round((buyPrice * 1.12) / 500000) * 500000;
    const origin = request.type === 'trade-in' ? 'tukar tambah' : 'pembelian';

    const car = addCar({
      brand: request.brand,
      model: request.model,
      variant: request.variant,
      year: request.year,
      bodyType: request.bodyType || 'MPV',
      transmission: request.transmission || 'Manual',
      fuel: request.fuel || 'Bensin',
      mileage: request.mileage,
      color: request.color || '',
      colorHex: '',
      plate: '',
      location: request.location || '',
      price,
      buyPrice,
      status: 'available',
      ownerCount: 1,
      taxValidUntil: '',
      serviceRecord: false,
      features: [],
      description: `Mobil hasil ${origin} dari pelanggan (${request.code}). Kondisi awal: ${request.condition || '-'}.`,
    });

    updateSellRequest(requestId, { status: 'purchased', offerPrice: buyPrice, purchasedCarId: car.id });
    return car;
  };

  // ---------------------------------------------------------------------------
  // Booking test drive
  // ---------------------------------------------------------------------------
  const addTestDrive = (data) => {
    const car = cars.find(c => c.id === data.carId);
    const booking = {
      location: 'showroom',
      address: '',
      notes: '',
      ...data,
      carName: car ? getCarName(car, { withYear: true }) : (data.carName || ''),
      id: 'td-' + Date.now(),
      code: generateTestDriveCode(),
      status: 'pending',
      createdAt: new Date().toISOString(),
    };
    setTestDrives(prev => [booking, ...prev]);
    return booking;
  };

  const updateTestDrive = (id, updated) => {
    setTestDrives(prev => prev.map(t => t.id === id ? { ...t, ...updated } : t));
  };

  const deleteTestDrive = (id) => {
    setTestDrives(prev => prev.filter(t => t.id !== id));
  };

  // ---------------------------------------------------------------------------
  // Penjualan mobil (kasir showroom)
  // ---------------------------------------------------------------------------
  /**
   * @param {object} p
   * @param {string} p.carId
   * @param {string} p.customerName
   * @param {string} [p.customerPhone]
   * @param {'cash'|'transfer'|'credit'} p.paymentMethod
   * @param {number} [p.discount]      potongan harga (Rp)
   * @param {number} [p.paidAmount]    uang diterima (khusus tunai)
   * @param {{leasing:string, dpPercent:number, tenorMonths:number, annualRate:number}} [p.credit]
   * @param {{requestId?:string, carName:string, value:number}} [p.tradeIn]
   * @param {string} [p.notes]
   */
  const sellCar = ({ carId, customerName, customerPhone = '', paymentMethod = 'cash', discount = 0, paidAmount, credit = null, tradeIn = null, notes = '' }) => {
    const car = cars.find(c => c.id === carId);
    if (!car) return null;
    if (car.status === 'sold') {
      toast.error('Mobil ini sudah terjual.');
      return null;
    }

    const price = toNumber(car.price);

    // Diskon dan tukar tambah tidak lagi dipotong diam-diam kalau melebihi batas —
    // itu bisa membuat mobil "terjual" seharga Rp 0. Tolak dan minta cek ulang.
    const rawDiscount = Number(discount);
    if (!Number.isFinite(rawDiscount) || rawDiscount < 0 || rawDiscount >= price) {
      toast.error('Diskon tidak valid atau melebihi harga mobil.');
      return null;
    }
    const total = price - rawDiscount;

    let tradeInValue = 0;
    if (tradeIn) {
      const rawTradeIn = Number(tradeIn.value);
      if (!Number.isFinite(rawTradeIn) || rawTradeIn < 0 || rawTradeIn > total) {
        toast.error('Nilai tukar tambah melebihi total harga mobil.');
        return null;
      }
      tradeInValue = rawTradeIn;
    }
    const amountDue = total - tradeInValue;

    let creditDetail = null;
    let paid = amountDue;
    let changeAmount = 0;

    if (paymentMethod === 'credit') {
      const dpPercent = toNumber(credit?.dpPercent, mergedStoreInfo.minDpPercent);
      const tenorMonths = toNumber(credit?.tenorMonths, 36);
      const annualRate = toNumber(credit?.annualRate, mergedStoreInfo.creditRate);
      const sim = calcInstallment({ price: amountDue, dpPercent, tenorMonths, annualRate });
      creditDetail = {
        leasing: credit?.leasing || 'Leasing Partner',
        dpPercent,
        tenorMonths,
        annualRate,
        dpAmount: sim.dpAmount,
        principal: sim.principal,
        totalInterest: sim.totalInterest,
        monthly: sim.monthly,
      };
      paid = sim.dpAmount;
    } else if (paymentMethod === 'cash') {
      // Field kosong/tidak diisi berarti kasir belum mengubahnya dari nilai default:
      // perlakukan sebagai pas sesuai sisa bayar, bukan sebagai harga mobil penuh.
      const rawPaid = (paidAmount === null || paidAmount === undefined || paidAmount === '')
        ? amountDue
        : Number(paidAmount);
      if (!Number.isFinite(rawPaid) || rawPaid < amountDue) {
        toast.error('Uang diterima kurang dari sisa yang harus dibayar.');
        return null;
      }
      paid = rawPaid;
      changeAmount = paid - amountDue;
    }

    const trx = {
      id: 'trx-' + Date.now(),
      code: generateTrxCode(),
      date: new Date().toISOString(),
      saleType: 'car',
      customerName: (customerName || '').trim() || 'Pelanggan Umum',
      customerPhone,
      cashier: mergedStoreInfo.cashierName || 'Admin Showroom',
      items: [{
        id: car.id,
        sku: car.code,
        name: getCarName(car, { withYear: true }),
        unit: 'unit',
        price,
        buyPrice: toNumber(car.buyPrice),
        qty: 1,
        subtotal: price
      }],
      subtotal: price,
      discount: rawDiscount,
      tax: 0,
      total,
      amountDue,
      paymentMethod,
      paidAmount: paid,
      changeAmount,
      totalProfit: total - toNumber(car.buyPrice),
      car: {
        id: car.id,
        code: car.code,
        brand: car.brand,
        model: car.model,
        variant: car.variant,
        year: car.year,
        plate: car.plate,
        mileage: car.mileage,
        transmission: car.transmission,
        fuel: car.fuel,
        color: car.color
      },
      credit: creditDetail,
      tradeIn: tradeInValue > 0
        ? { requestId: tradeIn.requestId || null, carName: tradeIn.carName || '', value: tradeInValue }
        : null,
      notes
    };

    setCars(prev => prev.map(c => c.id === carId
      ? { ...c, status: 'sold', soldAt: trx.date, soldPrice: total, soldTransactionId: trx.id }
      : c
    ));
    setTransactions(prev => [trx, ...prev]);

    // Mobil tukar tambah otomatis masuk stok
    if (trx.tradeIn?.requestId) {
      purchaseFromSellRequest(trx.tradeIn.requestId, { offerPrice: tradeInValue });
    }

    setSelectedCarId(null);
    setCheckoutCarId(null);
    setCurrentReceipt(trx);
    setIsReceiptModalOpen(true);

    return trx;
  };

  // Reset to default data
  const resetToDefault = () => {
    if (confirm('Kembalikan semua data ke demo awal? Data yang baru diubah akan di-reset.')) {
      setProducts(INITIAL_PRODUCTS);
      setCategories(INITIAL_CATEGORIES);
      setSuppliers(INITIAL_SUPPLIERS);
      setTransactions(INITIAL_TRANSACTIONS);
      setStoreInfo(INITIAL_STORE_INFO);
      setCars(INITIAL_CARS);
      setSellRequests(INITIAL_SELL_REQUESTS);
      setTestDrives(INITIAL_TEST_DRIVES);
      setFavoriteCarIds([]);
      setSelectedCarId(null);
      setCheckoutCarId(null);
      clearCart();
    }
  };

  return (
    <AppContext.Provider value={{
      activeTab,
      setActiveTab,
      products,
      addProduct,
      updateProduct,
      deleteProduct,
      adjustStock,
      categories,
      addCategory,
      deleteCategory,
      suppliers,
      addSupplier,
      updateSupplier,
      deleteSupplier,
      recordPurchase,
      transactions,
      storeInfo: mergedStoreInfo,
      setStoreInfo,
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
      completeTransaction,
      currentReceipt,
      setCurrentReceipt,
      isPaymentModalOpen,
      setIsPaymentModalOpen,
      isReceiptModalOpen,
      setIsReceiptModalOpen,
      isSettingsModalOpen,
      setIsSettingsModalOpen,
      resetToDefault,
      // Mobil bekas
      cars,
      addCar,
      updateCar,
      deleteCar,
      setCarStatus,
      voidCarSale,
      favoriteCarIds,
      toggleFavoriteCar,
      selectedCar,
      openCarDetail,
      closeCarDetail,
      checkoutCar,
      openCarCheckout,
      closeCarCheckout,
      sellCar,
      sellRequests,
      addSellRequest,
      updateSellRequest,
      deleteSellRequest,
      purchaseFromSellRequest,
      testDrives,
      addTestDrive,
      updateTestDrive,
      deleteTestDrive
    }}>
      {children}
    </AppContext.Provider>
  );
}

export function useApp() {
  const context = useContext(AppContext);
  if (!context) throw new Error('useApp must be used within an AppProvider');
  return context;
}
