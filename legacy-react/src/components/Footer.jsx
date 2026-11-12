import React from 'react';
import { Phone, ChevronRight, ArrowUp, Mail, ShieldCheck } from 'lucide-react';
import { useApp } from '../context/AppContext';
import { ADMIN_TABS } from '../data/navigation';
import { AUTO18_BENEFITS, AUTO18_BENEFITS_SUBTITLE } from '../data/initialData';
import Logo from './Logo';
import { SocialLinks } from './Auto18Sections';

// Pita diagonal merah-hitam di atas footer (gaya poster Auto18)
const STRIPE_STYLE = {
  backgroundImage: 'repeating-linear-gradient(-55deg, #e41b1b 0 18px, #070707 18px 30px)',
};

const BUY_LINKS = ['Cari Mobil Bekas', 'Simulasi Kredit', 'Booking Test Drive', 'Mobil Favorit', 'Tips Membeli Mobil'];
const SELL_LINKS = ['Jual Langsung', 'Tukar Tambah', 'Estimasi Harga Instan', 'Jadwalkan Inspeksi'];

const toTelHref = (number) => `tel:${String(number || '').replace(/[^\d+]/g, '')}`;

function FooterHeading({ children }) {
  return (
    <div className="mb-4">
      <h3 className="font-display text-sm font-black uppercase tracking-wider text-white">{children}</h3>
      <span className="mt-2 block h-1 w-8 -skew-x-12 bg-brand-600" aria-hidden="true" />
    </div>
  );
}

function FooterLink({ label, onClick }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className="group inline-flex items-start gap-1 text-left text-sm text-neutral-400 transition-colors hover:text-white focus:outline-none focus-visible:text-white"
    >
      <ChevronRight className="mt-[3px] h-3.5 w-3.5 shrink-0 text-brand-600 transition-transform group-hover:translate-x-0.5" />
      <span>{label}</span>
    </button>
  );
}

export default function Footer() {
  const { setActiveTab, storeInfo } = useApp();
  const year = new Date().getFullYear();

  return (
    <footer className="mt-12 bg-black text-neutral-300">
      {/* Aksen merah atas */}
      <div className="h-1 bg-brand-600" aria-hidden="true" />
      <div className="h-2.5 opacity-90" style={STRIPE_STYLE} aria-hidden="true" />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-12 lg:gap-x-8">
          {/* Brand */}
          <div className="col-span-2 lg:col-span-3">
            <button
              type="button"
              onClick={() => setActiveTab('buy-car')}
              aria-label="Beranda Auto18"
              className="rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
            >
              <Logo size="lg" />
            </button>
            {storeInfo.tagline && (
              <p className="mt-4 max-w-xs text-sm leading-relaxed text-neutral-400">{storeInfo.tagline}</p>
            )}
            <ul className="mt-4 space-y-2 text-sm text-neutral-400">
              {storeInfo.phone && (
                <li className="flex items-start gap-2">
                  <Phone className="mt-0.5 h-4 w-4 shrink-0 text-brand-500" />
                  <a href={toTelHref(storeInfo.phone)} className="hover:text-white">{storeInfo.phone}</a>
                </li>
              )}
              {storeInfo.email && (
                <li className="flex items-start gap-2">
                  <Mail className="mt-0.5 h-4 w-4 shrink-0 text-brand-500" />
                  <a href={`mailto:${storeInfo.email}`} className="break-all hover:text-white">{storeInfo.email}</a>
                </li>
              )}
            </ul>
            <SocialLinks variant="icon" className="mt-5" />
          </div>

          {/* Beli Mobil */}
          <div className="col-span-1 lg:col-span-2">
            <FooterHeading>Beli Mobil</FooterHeading>
            <ul className="space-y-2.5">
              {BUY_LINKS.map(label => (
                <li key={label}>
                  <FooterLink label={label} onClick={() => setActiveTab('buy-car')} />
                </li>
              ))}
            </ul>
          </div>

          {/* Jual Mobil */}
          <div className="col-span-1 lg:col-span-2">
            <FooterHeading>Jual Mobil</FooterHeading>
            <ul className="space-y-2.5">
              {SELL_LINKS.map(label => (
                <li key={label}>
                  <FooterLink label={label} onClick={() => setActiveTab('sell-car')} />
                </li>
              ))}
            </ul>
          </div>

          {/* Layanan darurat */}
          <div className="col-span-2 sm:col-span-1 lg:col-span-3">
            <FooterHeading>Layanan Darurat 24 Jam</FooterHeading>
            <p className="text-sm leading-relaxed text-neutral-400">
              {AUTO18_BENEFITS_SUBTITLE}. {AUTO18_BENEFITS.length} benefit untuk setiap pembeli mobil Auto18.
            </p>

            {storeInfo.callCenter && (
              <a
                href={toTelHref(storeInfo.callCenter)}
                className="a18-pill mt-4 flex w-full max-w-xs items-center gap-3 px-3 py-2.5 transition-colors hover:border-brand-500 hover:bg-brand-600/10"
              >
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white">
                  <Phone className="h-5 w-5" />
                </span>
                <span className="min-w-0 leading-tight">
                  <span className="block text-[10px] font-bold uppercase tracking-wider text-neutral-400">Call Center</span>
                  <span className="block truncate font-display text-xl font-black text-white">{storeInfo.callCenter}</span>
                </span>
              </a>
            )}

            {storeInfo.supportPartner && (
              <p className="mt-3 flex items-center gap-1.5 text-xs text-neutral-500">
                <ShieldCheck className="h-3.5 w-3.5 text-brand-500" />
                <span>
                  Supported by <span className="font-display font-black uppercase tracking-wide text-neutral-200">{storeInfo.supportPartner}</span>
                </span>
              </p>
            )}
          </div>

          {/* Panel showroom */}
          <div className="col-span-2 sm:col-span-1 lg:col-span-2">
            <FooterHeading>Panel Showroom</FooterHeading>
            <ul className="grid grid-cols-2 gap-x-4 gap-y-2.5 sm:grid-cols-1">
              {ADMIN_TABS.map(tab => (
                <li key={tab.id}>
                  <FooterLink label={tab.label} onClick={() => setActiveTab(tab.id)} />
                </li>
              ))}
            </ul>
          </div>
        </div>
      </div>

      {/* Bar bawah */}
      <div className="border-t border-white/10">
        <div className="max-w-7xl mx-auto flex flex-col gap-3 px-4 py-5 text-xs text-neutral-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
          <p className="leading-relaxed">
            © {year} <span className="font-bold text-neutral-300">Auto18</span> — Auto Eighteen
            {storeInfo.address && <span> · {storeInfo.address}</span>}
            <br className="sm:hidden" />
            <span className="block sm:inline">
              {' '}· Foto mobil:{' '}
              <a href="https://commons.wikimedia.org" target="_blank" rel="noopener noreferrer" className="underline hover:text-white">
                Wikimedia Commons
              </a>
              , lisensi{' '}
              <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener noreferrer" className="underline hover:text-white">
                CC BY-SA 4.0
              </a>
            </span>
          </p>
          <button
            type="button"
            onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
            className="inline-flex items-center gap-1.5 self-start rounded-full border border-white/15 px-3 py-1.5 font-semibold text-neutral-300 transition-colors hover:border-brand-500 hover:text-white sm:self-auto"
          >
            <ArrowUp className="h-3.5 w-3.5" />
            Kembali ke Atas
          </button>
        </div>
      </div>
    </footer>
  );
}
