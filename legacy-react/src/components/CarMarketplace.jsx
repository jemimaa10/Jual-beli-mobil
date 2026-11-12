import React, { useMemo, useRef, useState } from 'react';
import { AnimatePresence, motion } from 'motion/react';
import NumberFlow from '@number-flow/react';
import {
  Search, SlidersHorizontal, X, Heart, ArrowRight, Car, ShieldCheck, Tag, RotateCcw
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import CarCard from './CarCard';
import CarImage from './CarImage';
import CreditSimulator from './CreditSimulator';
import { BenefitsSection, FaqSection, HowItWorksSection, SectionHeading, SocialLinks, TipsSection } from './Auto18Sections';
import {
  BODY_TYPES, CAR_BRANDS, CAR_LOCATIONS, FAQS_BUY, FUEL_TYPES, HOW_IT_WORKS_BUY, PRICE_RANGES, TRANSMISSIONS
} from '../data/initialData';
import { formatRupiahShort, getCarName } from '../utils/carUtils';
import { formatRupiah } from '../utils/formatters';

const MILEAGE_OPTIONS = [
  { id: 'all', label: 'Semua', max: null },
  { id: '30', label: '< 30.000 km', max: 30000 },
  { id: '50', label: '< 50.000 km', max: 50000 },
  { id: '80', label: '< 80.000 km', max: 80000 },
  { id: '100', label: '< 100.000 km', max: 100000 },
];

const SORT_OPTIONS = [
  { id: 'recommended', label: 'Rekomendasi' },
  { id: 'newest', label: 'Terbaru' },
  { id: 'price-asc', label: 'Harga Terendah' },
  { id: 'price-desc', label: 'Harga Tertinggi' },
  { id: 'km-asc', label: 'Kilometer Terendah' },
  { id: 'year-desc', label: 'Tahun Terbaru' },
];

// Kartu hasil meluncur ke posisi barunya saat filter/urutan berubah (hanya posisi —
// ukuran kartu sama, jadi isinya tidak ikut melar). Kartu baru muncul, kartu yang
// tersaring keluar dengan cepat.
const CARD_TRANSITION = {
  type: 'spring',
  bounce: 0.15,
  duration: 0.35,
  opacity: { duration: 0.2, ease: 'easeOut' },
};
const CARD_INITIAL = { opacity: 0, scale: 0.96 };
const CARD_ANIMATE = { opacity: 1, scale: 1 };
const CARD_EXIT = { opacity: 0, scale: 0.96, transition: { duration: 0.18, ease: 'easeIn' } };

// Jumlah hasil berganti angka dengan cepat, tidak lebih lambat dari animasi kartu
const COUNT_TIMING = { duration: 400, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' };

const EMPTY_FILTERS = {
  keyword: '',
  brands: [],
  priceRange: 'all',
  bodyType: 'all',
  transmission: 'all',
  fuel: 'all',
  yearFrom: '',
  yearTo: '',
  mileage: 'all',
  location: 'all',
  showSold: false,
  favoritesOnly: false,
};

export default function CarMarketplace() {
  const { cars, favoriteCarIds, setActiveTab, selectedCar } = useApp();
  const [filters, setFilters] = useState(EMPTY_FILTERS);
  // Selama detail mobil terbuka, grid memakai daftar favorit sebelum perubahan. Kalau
  // tidak, menandai/membatalkan favorit di dalam modal (dengan filter "favorit saja")
  // membuat kartu mobil itu keluar-masuk grid dan merebut foto bersama (layoutId) dari
  // modal, sehingga foto di modal hilang. Grid menyusul saat modal ditutup.
  const [filterFavoriteIds, setFilterFavoriteIds] = useState(favoriteCarIds);
  if (!selectedCar && filterFavoriteIds !== favoriteCarIds) setFilterFavoriteIds(favoriteCarIds);
  const [sort, setSort] = useState('recommended');
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  const [heroKeyword, setHeroKeyword] = useState('');
  const [heroBody, setHeroBody] = useState('all');
  const [heroPrice, setHeroPrice] = useState('all');
  const resultsRef = useRef(null);

  const setFilter = (patch) => setFilters(prev => ({ ...prev, ...patch }));

  const scrollToResults = () => {
    resultsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  const toggleBrand = (brand) => {
    setFilters(prev => ({
      ...prev,
      brands: prev.brands.includes(brand) ? prev.brands.filter(b => b !== brand) : [...prev.brands, brand],
    }));
  };

  const availableCars = useMemo(() => cars.filter(c => c.status !== 'sold'), [cars]);

  const brandCounts = useMemo(() => {
    const counts = {};
    availableCars.forEach(c => { counts[c.brand] = (counts[c.brand] || 0) + 1; });
    return counts;
  }, [availableCars]);

  const bodyCounts = useMemo(() => {
    const counts = {};
    availableCars.forEach(c => { counts[c.bodyType] = (counts[c.bodyType] || 0) + 1; });
    return counts;
  }, [availableCars]);

  const filteredCars = useMemo(() => {
    const keyword = filters.keyword.trim().toLowerCase();
    const range = PRICE_RANGES.find(r => r.id === filters.priceRange);
    const maxKm = MILEAGE_OPTIONS.find(o => o.id === filters.mileage)?.max ?? null;

    const result = cars.filter(car => {
      if (!filters.showSold && car.status === 'sold') return false;
      if (filters.favoritesOnly && !filterFavoriteIds.includes(car.id)) return false;
      if (keyword) {
        const haystack = `${car.brand} ${car.model} ${car.variant} ${car.year} ${car.code} ${car.bodyType}`.toLowerCase();
        if (!haystack.includes(keyword)) return false;
      }
      if (filters.brands.length > 0 && !filters.brands.includes(car.brand)) return false;
      if (range) {
        if (car.price < range.min) return false;
        if (range.max !== null && car.price > range.max) return false;
      }
      if (filters.bodyType !== 'all' && car.bodyType !== filters.bodyType) return false;
      if (filters.transmission !== 'all' && car.transmission !== filters.transmission) return false;
      if (filters.fuel !== 'all' && car.fuel !== filters.fuel) return false;
      if (filters.location !== 'all' && car.location !== filters.location) return false;
      if (filters.yearFrom && car.year < Number(filters.yearFrom)) return false;
      if (filters.yearTo && car.year > Number(filters.yearTo)) return false;
      if (maxKm !== null && car.mileage > maxKm) return false;
      return true;
    });

    const byStatus = (car) => (car.status === 'available' ? 0 : car.status === 'booked' ? 1 : 2);

    return result.sort((a, b) => {
      switch (sort) {
        case 'newest':
          return new Date(b.createdAt) - new Date(a.createdAt);
        case 'price-asc':
          return a.price - b.price;
        case 'price-desc':
          return b.price - a.price;
        case 'km-asc':
          return a.mileage - b.mileage;
        case 'year-desc':
          return b.year - a.year;
        default:
          return (
            byStatus(a) - byStatus(b) ||
            Number(b.isFeatured) - Number(a.isFeatured) ||
            new Date(b.createdAt) - new Date(a.createdAt)
          );
      }
    });
  }, [cars, filters, filterFavoriteIds, sort]);

  const featuredCar = useMemo(
    () => availableCars.find(c => c.isFeatured) || availableCars[0] || null,
    [availableCars]
  );

  const medianPrice = useMemo(() => {
    if (availableCars.length === 0) return 200000000;
    const prices = availableCars.map(c => c.price).sort((a, b) => a - b);
    return prices[Math.floor(prices.length / 2)];
  }, [availableCars]);

  const [simulationPrice, setSimulationPrice] = useState(null);
  const activeSimulationPrice = simulationPrice ?? medianPrice;

  const applyHeroSearch = () => {
    setFilter({ keyword: heroKeyword, bodyType: heroBody, priceRange: heroPrice });
    scrollToResults();
  };

  // Chip filter aktif
  const activeChips = [];
  if (filters.keyword) activeChips.push({ key: 'keyword', label: `"${filters.keyword}"`, clear: () => setFilter({ keyword: '' }) });
  filters.brands.forEach(brand => activeChips.push({ key: `brand-${brand}`, label: brand, clear: () => toggleBrand(brand) }));
  if (filters.priceRange !== 'all') {
    activeChips.push({
      key: 'price',
      label: PRICE_RANGES.find(r => r.id === filters.priceRange)?.label || '',
      clear: () => setFilter({ priceRange: 'all' }),
    });
  }
  if (filters.bodyType !== 'all') activeChips.push({ key: 'body', label: filters.bodyType, clear: () => setFilter({ bodyType: 'all' }) });
  if (filters.transmission !== 'all') activeChips.push({ key: 'trans', label: filters.transmission, clear: () => setFilter({ transmission: 'all' }) });
  if (filters.fuel !== 'all') activeChips.push({ key: 'fuel', label: filters.fuel, clear: () => setFilter({ fuel: 'all' }) });
  if (filters.location !== 'all') activeChips.push({ key: 'loc', label: filters.location, clear: () => setFilter({ location: 'all' }) });
  if (filters.mileage !== 'all') {
    activeChips.push({
      key: 'km',
      label: MILEAGE_OPTIONS.find(o => o.id === filters.mileage)?.label || '',
      clear: () => setFilter({ mileage: 'all' }),
    });
  }
  if (filters.yearFrom) activeChips.push({ key: 'yf', label: `Dari ${filters.yearFrom}`, clear: () => setFilter({ yearFrom: '' }) });
  if (filters.yearTo) activeChips.push({ key: 'yt', label: `Sampai ${filters.yearTo}`, clear: () => setFilter({ yearTo: '' }) });
  if (filters.favoritesOnly) activeChips.push({ key: 'fav', label: 'Favorit saja', clear: () => setFilter({ favoritesOnly: false }) });
  if (filters.showSold) activeChips.push({ key: 'sold', label: 'Termasuk terjual', clear: () => setFilter({ showSold: false }) });

  const filterPanel = (
    <div className="space-y-5">
      <div>
        <label htmlFor="filter-keyword" className="a18-label">Cari</label>
        <input
          id="filter-keyword"
          type="search"
          value={filters.keyword}
          onChange={(e) => setFilter({ keyword: e.target.value })}
          placeholder="Merek, model, atau kode"
          className="a18-input"
        />
      </div>

      <fieldset>
        <legend className="a18-label">Merek</legend>
        <div className="max-h-44 space-y-1.5 overflow-y-auto pr-1">
          {CAR_BRANDS.filter(brand => brandCounts[brand] || filters.brands.includes(brand)).map(brand => (
            <label key={brand} className="flex cursor-pointer items-center gap-2 text-sm text-neutral-300 hover:text-white">
              <input
                type="checkbox"
                checked={filters.brands.includes(brand)}
                onChange={() => toggleBrand(brand)}
                className="h-4 w-4 accent-brand-600"
              />
              <span className="flex-1">{brand}</span>
              <span className="text-xs text-neutral-500">{brandCounts[brand] || 0}</span>
            </label>
          ))}
        </div>
      </fieldset>

      <fieldset>
        <legend className="a18-label">Harga</legend>
        <div className="space-y-1.5">
          {[{ id: 'all', label: 'Semua harga' }, ...PRICE_RANGES].map(range => (
            <label key={range.id} className="flex cursor-pointer items-center gap-2 text-sm text-neutral-300 hover:text-white">
              <input
                type="radio"
                name="price-range"
                checked={filters.priceRange === range.id}
                onChange={() => setFilter({ priceRange: range.id })}
                className="h-4 w-4 accent-brand-600"
              />
              {range.label}
            </label>
          ))}
        </div>
      </fieldset>

      <div className="grid grid-cols-2 gap-3">
        <div>
          <label htmlFor="filter-body" className="a18-label">Tipe Bodi</label>
          <select id="filter-body" value={filters.bodyType} onChange={(e) => setFilter({ bodyType: e.target.value })} className="a18-input">
            <option value="all">Semua</option>
            {BODY_TYPES.map(type => <option key={type} value={type}>{type}</option>)}
          </select>
        </div>
        <div>
          <label htmlFor="filter-trans" className="a18-label">Transmisi</label>
          <select id="filter-trans" value={filters.transmission} onChange={(e) => setFilter({ transmission: e.target.value })} className="a18-input">
            <option value="all">Semua</option>
            {TRANSMISSIONS.map(t => <option key={t} value={t}>{t}</option>)}
          </select>
        </div>
        <div>
          <label htmlFor="filter-fuel" className="a18-label">Bahan Bakar</label>
          <select id="filter-fuel" value={filters.fuel} onChange={(e) => setFilter({ fuel: e.target.value })} className="a18-input">
            <option value="all">Semua</option>
            {FUEL_TYPES.map(f => <option key={f} value={f}>{f}</option>)}
          </select>
        </div>
        <div>
          <label htmlFor="filter-km" className="a18-label">Kilometer</label>
          <select id="filter-km" value={filters.mileage} onChange={(e) => setFilter({ mileage: e.target.value })} className="a18-input">
            {MILEAGE_OPTIONS.map(o => <option key={o.id} value={o.id}>{o.label}</option>)}
          </select>
        </div>
        <div>
          <label htmlFor="filter-year-from" className="a18-label">Tahun Dari</label>
          <input
            id="filter-year-from"
            type="number"
            inputMode="numeric"
            value={filters.yearFrom}
            onChange={(e) => setFilter({ yearFrom: e.target.value })}
            placeholder="2015"
            className="a18-input"
          />
        </div>
        <div>
          <label htmlFor="filter-year-to" className="a18-label">Tahun Sampai</label>
          <input
            id="filter-year-to"
            type="number"
            inputMode="numeric"
            value={filters.yearTo}
            onChange={(e) => setFilter({ yearTo: e.target.value })}
            placeholder="2023"
            className="a18-input"
          />
        </div>
      </div>

      <div>
        <label htmlFor="filter-location" className="a18-label">Lokasi</label>
        <select id="filter-location" value={filters.location} onChange={(e) => setFilter({ location: e.target.value })} className="a18-input">
          <option value="all">Semua lokasi</option>
          {CAR_LOCATIONS.map(loc => <option key={loc} value={loc}>{loc}</option>)}
        </select>
      </div>

      <div className="space-y-2 border-t border-white/10 pt-4">
        <label className="flex cursor-pointer items-center gap-2 text-sm text-neutral-300 hover:text-white">
          <input
            type="checkbox"
            checked={filters.favoritesOnly}
            onChange={(e) => setFilter({ favoritesOnly: e.target.checked })}
            className="h-4 w-4 accent-brand-600"
          />
          Hanya favorit saya ({favoriteCarIds.length})
        </label>
        <label className="flex cursor-pointer items-center gap-2 text-sm text-neutral-300 hover:text-white">
          <input
            type="checkbox"
            checked={filters.showSold}
            onChange={(e) => setFilter({ showSold: e.target.checked })}
            className="h-4 w-4 accent-brand-600"
          />
          Tampilkan mobil terjual
        </label>
      </div>

      <button type="button" onClick={() => setFilters(EMPTY_FILTERS)} className="a18-btn-outline w-full">
        <RotateCcw className="h-4 w-4" />
        Reset Filter
      </button>
    </div>
  );

  return (
    <div>
      {/* HERO */}
      <section className="a18-stripes relative overflow-hidden">
        <div className="max-w-7xl mx-auto px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
          <div className="grid items-center gap-10 lg:grid-cols-2">
            <div>
              <span className="inline-flex items-center gap-2 rounded-full border border-white/25 bg-black/50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.2em] text-white">
                <ShieldCheck className="h-3.5 w-3.5 text-brand-500" aria-hidden="true" />
                Mobil Second Bergaransi Inspeksi
              </span>
              <h1 className="a18-heading mt-4 text-3xl leading-[1.05] sm:text-5xl lg:text-6xl">
                Jual / Beli <span className="text-brand-500">Mobil Second</span> Berkualitas Hanya di Auto 18
              </h1>
              <p className="mt-4 max-w-xl text-sm leading-relaxed text-neutral-300 sm:text-base">
                Pilihan mobil bekas terawat, harga transparan, bisa tukar tambah dan kredit.
                Setiap pembelian dilengkapi layanan darurat 24 jam di seluruh Indonesia.
              </p>

              {/* Pencarian cepat */}
              <div className="mt-6 rounded-2xl border border-white/15 bg-black/70 p-3 backdrop-blur">
                <div className="grid gap-2 sm:grid-cols-2">
                  <div className="relative sm:col-span-2">
                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" aria-hidden="true" />
                    <input
                      type="search"
                      value={heroKeyword}
                      onChange={(e) => setHeroKeyword(e.target.value)}
                      onKeyDown={(e) => e.key === 'Enter' && applyHeroSearch()}
                      placeholder="Cari Xpander, Fortuner..."
                      aria-label="Cari merek atau model mobil"
                      className="a18-input pl-9"
                    />
                  </div>
                  <select value={heroBody} onChange={(e) => setHeroBody(e.target.value)} aria-label="Tipe bodi" className="a18-input">
                    <option value="all">Semua Tipe</option>
                    {BODY_TYPES.map(type => <option key={type} value={type}>{type}</option>)}
                  </select>
                  <select value={heroPrice} onChange={(e) => setHeroPrice(e.target.value)} aria-label="Rentang harga" className="a18-input">
                    <option value="all">Semua Harga</option>
                    {PRICE_RANGES.map(range => <option key={range.id} value={range.id}>{range.label}</option>)}
                  </select>
                  <button type="button" onClick={applyHeroSearch} className="a18-btn-primary sm:col-span-2">
                    <Search className="h-4 w-4" />
                    Cari Mobil
                  </button>
                </div>
              </div>

              {/* Statistik singkat */}
              <div className="mt-6 flex flex-wrap gap-x-8 gap-y-3">
                <div>
                  <p className="font-display text-2xl font-black text-white">{availableCars.length}</p>
                  <p className="text-[11px] uppercase tracking-wider text-neutral-400">Mobil Tersedia</p>
                </div>
                <div>
                  <p className="font-display text-2xl font-black text-white">{Object.keys(brandCounts).length}</p>
                  <p className="text-[11px] uppercase tracking-wider text-neutral-400">Merek Pilihan</p>
                </div>
                <div>
                  <p className="font-display text-2xl font-black text-brand-500">24 Jam</p>
                  <p className="text-[11px] uppercase tracking-wider text-neutral-400">Layanan Darurat</p>
                </div>
              </div>

              <SocialLinks className="mt-6" />
            </div>

            {/* Mobil unggulan */}
            {featuredCar && (
              <div className="hidden lg:block">
                <div className="a18-card overflow-hidden">
                  <CarImage car={featuredCar} className="aspect-[16/10] w-full" />
                  <div className="flex items-center justify-between gap-4 p-5">
                    <div className="min-w-0">
                      <p className="text-[10px] font-black uppercase tracking-wider text-brand-500">Pilihan Auto18</p>
                      <p className="truncate font-display text-lg font-black uppercase text-white">
                        {getCarName(featuredCar)}
                      </p>
                      <p className="text-xs text-neutral-400">
                        {featuredCar.year} · {featuredCar.location}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="font-display text-xl font-black text-white">{formatRupiahShort(featuredCar.price)}</p>
                      <button
                        type="button"
                        onClick={scrollToResults}
                        className="mt-1 text-xs font-bold text-brand-400 hover:text-brand-300"
                      >
                        Lihat semua mobil →
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      </section>

      {/* PINTASAN MEREK & TIPE */}
      <section className="border-y border-white/10 bg-ink-900">
        <div className="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
          <h2 className="font-display text-sm font-black uppercase tracking-wide text-white">Cari Berdasarkan Merek</h2>
          <div className="mt-3 flex flex-wrap gap-2">
            {CAR_BRANDS.filter(brand => brandCounts[brand]).map(brand => (
              <button
                key={brand}
                type="button"
                onClick={() => { toggleBrand(brand); scrollToResults(); }}
                aria-pressed={filters.brands.includes(brand)}
                className={`rounded-xl border px-3.5 py-2 text-sm font-bold transition-colors ${
                  filters.brands.includes(brand)
                    ? 'border-brand-500 bg-brand-600 text-white'
                    : 'border-white/15 text-neutral-300 hover:border-white/40 hover:text-white'
                }`}
              >
                {brand} <span className="text-xs font-normal opacity-70">({brandCounts[brand]})</span>
              </button>
            ))}
          </div>

          <h2 className="mt-6 font-display text-sm font-black uppercase tracking-wide text-white">Cari Berdasarkan Tipe</h2>
          <div className="mt-3 flex flex-wrap gap-2">
            {BODY_TYPES.filter(type => bodyCounts[type]).map(type => (
              <button
                key={type}
                type="button"
                onClick={() => { setFilter({ bodyType: filters.bodyType === type ? 'all' : type }); scrollToResults(); }}
                aria-pressed={filters.bodyType === type}
                className={`inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-sm font-bold transition-colors ${
                  filters.bodyType === type
                    ? 'border-brand-500 bg-brand-600 text-white'
                    : 'border-white/15 text-neutral-300 hover:border-white/40 hover:text-white'
                }`}
              >
                <Car className="h-4 w-4" aria-hidden="true" />
                {type} <span className="text-xs font-normal opacity-70">({bodyCounts[type]})</span>
              </button>
            ))}
          </div>
        </div>
      </section>

      {/* DAFTAR MOBIL */}
      <section ref={resultsRef} id="daftar-mobil" className="max-w-7xl mx-auto scroll-mt-28 px-4 py-10 sm:px-6 lg:px-8">
        <div className="flex flex-wrap items-end justify-between gap-4">
          <div>
            <h2 className="a18-heading text-2xl sm:text-3xl">Daftar Mobil</h2>
            <p className="mt-1 text-sm text-neutral-400">
              {/* Seperti Money: angka animasi disembunyikan dari pembaca layar (NumberFlow
                  melaporkan dirinya sebagai gambar) dan diganti salinan teks biasa */}
              <NumberFlow
                aria-hidden="true"
                value={filteredCars.length}
                locales="id-ID"
                transformTiming={COUNT_TIMING}
                className="font-bold text-white"
              />
              <span className="sr-only">{filteredCars.length}</span>{' '}
              mobil ditemukan
            </p>
          </div>
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => setIsFilterOpen(true)}
              className="a18-btn-outline lg:hidden"
            >
              <SlidersHorizontal className="h-4 w-4" />
              Filter
            </button>
            <div>
              <label htmlFor="sort" className="sr-only">Urutkan</label>
              <select id="sort" value={sort} onChange={(e) => setSort(e.target.value)} className="a18-input w-auto">
                {SORT_OPTIONS.map(o => <option key={o.id} value={o.id}>{o.label}</option>)}
              </select>
            </div>
          </div>
        </div>

        {activeChips.length > 0 && (
          <div className="mt-4 flex flex-wrap items-center gap-2">
            {activeChips.map(chip => (
              <span key={chip.key} className="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-ink-800 px-3 py-1 text-xs text-neutral-200">
                {chip.label}
                <button type="button" onClick={chip.clear} aria-label={`Hapus filter ${chip.label}`} className="text-neutral-500 hover:text-brand-400">
                  <X className="h-3 w-3" />
                </button>
              </span>
            ))}
            <button type="button" onClick={() => setFilters(EMPTY_FILTERS)} className="text-xs font-bold text-brand-400 hover:text-brand-300">
              Reset semua
            </button>
          </div>
        )}

        <div className="mt-6 grid gap-6 lg:grid-cols-[280px_1fr]">
          {/* Sidebar filter (desktop) */}
          <aside className="hidden lg:block">
            <div className="a18-card sticky top-28 p-5">
              <h3 className="mb-4 flex items-center gap-2 font-display text-sm font-black uppercase tracking-wide text-white">
                <SlidersHorizontal className="h-4 w-4 text-brand-500" aria-hidden="true" />
                Filter
              </h3>
              {filterPanel}
            </div>
          </aside>

          {/* Hasil */}
          <div>
            {filteredCars.length === 0 ? (
              <div className="a18-card flex flex-col items-center justify-center p-12 text-center">
                <Car className="h-10 w-10 text-neutral-600" aria-hidden="true" />
                <p className="mt-4 font-display text-lg font-black uppercase text-white">Mobil Tidak Ditemukan</p>
                <p className="mt-1 max-w-sm text-sm text-neutral-400">
                  Coba ubah kata kunci atau longgarkan filter pencarian Anda.
                </p>
                <button type="button" onClick={() => setFilters(EMPTY_FILTERS)} className="a18-btn-primary mt-5">
                  <RotateCcw className="h-4 w-4" />
                  Reset Filter
                </button>
              </div>
            ) : (
              // relative: kartu yang keluar (mode popLayout) diposisikan absolut terhadap grid ini
              <div className="relative grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <AnimatePresence mode="popLayout" initial={false}>
                  {filteredCars.map(car => (
                    <motion.div
                      key={car.id}
                      // Ukur ulang posisi hanya saat daftar hasil berubah, bukan pada setiap
                      // render (mis. saat modal detail dibuka & scrollbar halaman hilang)
                      layout="position"
                      layoutDependency={filteredCars}
                      initial={CARD_INITIAL}
                      animate={CARD_ANIMATE}
                      exit={CARD_EXIT}
                      transition={CARD_TRANSITION}
                      className="grid"
                    >
                      <CarCard car={car} />
                    </motion.div>
                  ))}
                </AnimatePresence>
              </div>
            )}
          </div>
        </div>
      </section>

      {/* Drawer filter (mobile) */}
      {isFilterOpen && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div
            className="absolute inset-0 bg-black/80 backdrop-blur-sm"
            onClick={() => setIsFilterOpen(false)}
            aria-hidden="true"
          />
          <div className="absolute inset-y-0 right-0 flex w-[88%] max-w-sm flex-col border-l border-white/10 bg-ink-800">
            <div className="flex items-center justify-between border-b border-white/10 p-4">
              <h3 className="font-display text-sm font-black uppercase tracking-wide text-white">Filter Mobil</h3>
              <button type="button" onClick={() => setIsFilterOpen(false)} aria-label="Tutup filter" className="a18-btn-ghost px-2">
                <X className="h-5 w-5" />
              </button>
            </div>
            <div className="flex-1 overflow-y-auto p-4">{filterPanel}</div>
            <div className="border-t border-white/10 p-4">
              <button type="button" onClick={() => setIsFilterOpen(false)} className="a18-btn-primary w-full">
                Tampilkan {filteredCars.length} Mobil
              </button>
            </div>
          </div>
        </div>
      )}

      {/* SIMULASI KREDIT */}
      <section className="bg-ink-900 py-14">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
          <SectionHeading
            eyebrow="Hitung Dulu"
            title="Simulasi Cicilan Mobil"
            subtitle="Geser harga mobil dan uang muka untuk memperkirakan cicilan bulanan Anda."
          />
          <div className="mt-8 grid gap-6 md:grid-cols-2">
            <div className="a18-card p-5 sm:p-6">
              <label htmlFor="sim-price" className="a18-label">Harga Mobil</label>
              <p className="font-display text-2xl font-black text-white">{formatRupiah(activeSimulationPrice)}</p>
              <input
                id="sim-price"
                type="range"
                min={50000000}
                max={800000000}
                step={5000000}
                value={activeSimulationPrice}
                onChange={(e) => setSimulationPrice(Number(e.target.value))}
                className="mt-3 w-full accent-brand-600"
              />
              <div className="flex justify-between text-[10px] text-neutral-500">
                <span>Rp 50 jt</span>
                <span>Rp 800 jt</span>
              </div>

              <div className="mt-5 border-t border-white/10 pt-4">
                <p className="a18-label">Atau pilih dari stok</p>
                <div className="flex flex-wrap gap-2">
                  {availableCars.slice(0, 4).map(car => (
                    <button
                      key={car.id}
                      type="button"
                      onClick={() => setSimulationPrice(car.price)}
                      className="inline-flex items-center gap-1.5 rounded-xl border border-white/15 px-3 py-1.5 text-xs font-semibold text-neutral-300 transition-colors hover:border-brand-500 hover:text-white"
                    >
                      <Tag className="h-3 w-3 text-brand-500" aria-hidden="true" />
                      {car.model} · {formatRupiahShort(car.price)}
                    </button>
                  ))}
                </div>
              </div>
            </div>

            <CreditSimulator price={activeSimulationPrice} initialTenor={48} />
          </div>
        </div>
      </section>

      <HowItWorksSection
        title="Cara Beli Mobil di Auto18"
        subtitle="Empat langkah sederhana dari pilih mobil sampai bawa pulang."
        steps={HOW_IT_WORKS_BUY}
      />

      <BenefitsSection />
      <TipsSection />

      {/* CTA JUAL MOBIL */}
      <section className="a18-stripes py-14">
        <div className="max-w-5xl mx-auto px-4 text-center sm:px-6 lg:px-8">
          <h2 className="a18-heading text-2xl sm:text-4xl">
            Mau Jual atau <span className="text-brand-500">Tukar Tambah</span> Mobil Anda?
          </h2>
          <p className="mx-auto mt-3 max-w-xl text-sm text-neutral-300 sm:text-base">
            Dapatkan estimasi harga instan, jadwalkan inspeksi, dan terima pembayaran cepat setelah dokumen beres.
          </p>
          <div className="mt-6 flex flex-wrap justify-center gap-3">
            <button type="button" onClick={() => setActiveTab('sell-car')} className="a18-btn-primary px-6 py-3 text-base">
              Jual Mobil Sekarang
              <ArrowRight className="h-4 w-4" />
            </button>
            <button
              type="button"
              onClick={() => { setFilter({ favoritesOnly: true }); scrollToResults(); }}
              className="a18-btn-outline px-6 py-3 text-base"
            >
              <Heart className="h-4 w-4" />
              Lihat Favorit Saya
            </button>
          </div>
        </div>
      </section>

      <FaqSection title="Pertanyaan Seputar Beli Mobil" faqs={FAQS_BUY} />
    </div>
  );
}
