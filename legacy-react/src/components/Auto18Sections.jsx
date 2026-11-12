import React, { useState } from 'react';
import {
  Truck, KeyRound, Fuel, BatteryCharging, CircleDot, SearchCheck, ShieldAlert, Cog, Wrench,
  Search, CalendarCheck, Wallet, ClipboardList, BadgePercent, Banknote,
  Instagram, Youtube, Music2, Phone, Plus, Minus, ShieldCheck
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import {
  AUTO18_BENEFITS,
  AUTO18_BENEFITS_SUBTITLE,
  BUYING_TIPS
} from '../data/initialData';

// Nama ikon di data → komponen lucide
const ICONS = {
  Truck, KeyRound, Fuel, BatteryCharging, CircleDot, SearchCheck, ShieldAlert, Cog, Wrench,
  Search, CalendarCheck, Wallet, ClipboardList, BadgePercent, Banknote,
};

const toTelHref = (number) => `tel:${String(number || '').replace(/[^\d+]/g, '')}`;

export function SectionHeading({ eyebrow, title, subtitle, align = 'center' }) {
  const alignment = align === 'left' ? 'text-left items-start' : 'text-center items-center';
  return (
    <div className={`flex flex-col ${alignment}`}>
      {eyebrow && (
        <span className="mb-2 inline-flex items-center gap-2 text-xs font-black uppercase tracking-[0.2em] text-brand-500">
          <span className="h-px w-5 bg-brand-600" aria-hidden="true" />
          {eyebrow}
        </span>
      )}
      <h2 className="a18-heading text-2xl sm:text-3xl lg:text-4xl">{title}</h2>
      {subtitle && (
        <p className={`mt-3 max-w-2xl text-sm leading-relaxed text-neutral-400 sm:text-base ${align === 'center' ? 'mx-auto' : ''}`}>
          {subtitle}
        </p>
      )}
    </div>
  );
}

/** Benefit layanan darurat 24 jam — meniru poster "BENEFIT BELI MOBIL DI AUTO 18" */
export function BenefitsSection() {
  const { storeInfo } = useApp();

  return (
    <section className="a18-stripes relative overflow-hidden py-14 sm:py-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center">
          <h2 className="a18-heading text-2xl leading-tight sm:text-4xl">
            Benefit <span className="text-brand-500">Beli Mobil</span> di Auto 18
          </h2>
          <p className="a18-pill mx-auto mt-4 inline-flex items-center gap-2 px-4 py-2 text-xs font-bold uppercase tracking-wide sm:text-sm">
            <span className="text-brand-500" aria-hidden="true">///</span>
            {AUTO18_BENEFITS_SUBTITLE}
            <span className="text-brand-500" aria-hidden="true">///</span>
          </p>
        </div>

        <div className="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {AUTO18_BENEFITS.map(benefit => {
            const Icon = ICONS[benefit.icon] || ShieldCheck;
            return (
              <div key={benefit.no} className="flex items-center gap-3">
                <span className="a18-number w-10 shrink-0 text-right text-3xl sm:text-4xl">{benefit.no}</span>
                <div className="a18-pill flex min-w-0 flex-1 items-center gap-3 px-4 py-3">
                  <Icon className="h-5 w-5 shrink-0 text-brand-500" aria-hidden="true" />
                  <span className="font-display text-sm font-bold uppercase leading-tight tracking-wide">
                    {benefit.title}
                  </span>
                </div>
              </div>
            );
          })}
        </div>

        <div className="mt-10 flex flex-col items-center justify-between gap-4 border-t border-white/15 pt-6 sm:flex-row">
          {storeInfo.supportPartner && (
            <p className="text-center text-xs uppercase tracking-wide text-neutral-400 sm:text-left">
              Supported by{' '}
              <span className="font-display text-base font-black tracking-wide text-white">{storeInfo.supportPartner}</span>
            </p>
          )}
          {storeInfo.callCenter && (
            <a
              href={toTelHref(storeInfo.callCenter)}
              className="flex items-center gap-3 rounded-2xl bg-brand-600 px-5 py-3 transition-colors hover:bg-brand-500"
            >
              <Phone className="h-5 w-5 text-white" aria-hidden="true" />
              <span className="leading-tight text-white">
                <span className="block text-[10px] font-bold uppercase tracking-wider text-white/80">Call Center 24 Jam</span>
                <span className="block font-display text-lg font-black">{storeInfo.callCenter}</span>
              </span>
            </a>
          )}
        </div>
      </div>
    </section>
  );
}

/** Tips membeli mobil second — meniru poster "TIPS MEMBELI MOBIL SECOND" */
export function TipsSection() {
  return (
    <section className="bg-ink-900 py-14 sm:py-16">
      <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <SectionHeading
          eyebrow="Panduan Auto18"
          title={<>Tips Membeli Mobil Second</>}
          subtitle="Lima hal yang wajib dicek sebelum memutuskan membeli mobil bekas."
        />

        <ol className="mt-10 space-y-0">
          {BUYING_TIPS.map((tip, index) => (
            <li
              key={tip.no}
              className={`flex items-start gap-4 py-5 sm:gap-6 ${index === 0 ? '' : 'border-t border-white/15'}`}
            >
              <span className="a18-number shrink-0 text-3xl text-brand-500 sm:text-5xl">{tip.no}</span>
              <div className="min-w-0">
                <h3 className="font-display text-base font-black uppercase leading-snug tracking-wide text-white sm:text-lg">
                  {tip.title}
                </h3>
                <p className="mt-1.5 text-sm leading-relaxed text-neutral-400">{tip.description}</p>
              </div>
            </li>
          ))}
        </ol>
      </div>
    </section>
  );
}

/** Langkah-langkah proses (beli / jual) */
export function HowItWorksSection({ title, subtitle, steps = [] }) {
  return (
    <section className="py-14 sm:py-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <SectionHeading eyebrow="Prosesnya Mudah" title={title} subtitle={subtitle} />

        <div className="relative mt-10 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {/* Garis penghubung antar langkah (desktop) */}
          <div className="pointer-events-none absolute left-0 right-0 top-12 hidden h-px bg-gradient-to-r from-transparent via-brand-600/50 to-transparent lg:block" aria-hidden="true" />

          {steps.map(step => {
            const Icon = ICONS[step.icon] || Search;
            return (
              <div key={step.no} className="a18-card relative z-10 p-5">
                <div className="flex items-center justify-between">
                  <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <Icon className="h-5 w-5" aria-hidden="true" />
                  </span>
                  <span className="font-display text-4xl font-black leading-none text-white/10">
                    {String(step.no).padStart(2, '0')}
                  </span>
                </div>
                <h3 className="mt-4 font-display text-base font-black uppercase tracking-wide text-white">{step.title}</h3>
                <p className="mt-1.5 text-sm leading-relaxed text-neutral-400">{step.description}</p>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}

/** Akordeon FAQ */
export function FaqSection({ title, faqs = [] }) {
  const [openIndex, setOpenIndex] = useState(0);

  return (
    <section className="bg-ink-900 py-14 sm:py-16">
      <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <SectionHeading eyebrow="FAQ" title={title} />

        <div className="mt-8 space-y-3">
          {faqs.map((faq, index) => {
            const isOpen = openIndex === index;
            return (
              <div key={faq.q} className="a18-card overflow-hidden">
                <h3>
                  <button
                    type="button"
                    onClick={() => setOpenIndex(isOpen ? -1 : index)}
                    aria-expanded={isOpen}
                    aria-controls={`faq-panel-${index}`}
                    id={`faq-button-${index}`}
                    className="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-white/5"
                  >
                    <span className="text-sm font-bold text-white sm:text-base">{faq.q}</span>
                    <span className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full ${isOpen ? 'bg-brand-600 text-white' : 'bg-white/10 text-neutral-300'}`}>
                      {isOpen ? <Minus className="h-4 w-4" /> : <Plus className="h-4 w-4" />}
                    </span>
                  </button>
                </h3>
                <div
                  id={`faq-panel-${index}`}
                  role="region"
                  aria-labelledby={`faq-button-${index}`}
                  hidden={!isOpen}
                  className="border-t border-white/10 px-5 py-4 text-sm leading-relaxed text-neutral-400"
                >
                  {faq.a}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}

/** Tautan sosial media Auto18 */
export function SocialLinks({ className = '', variant = 'pill' }) {
  const { storeInfo } = useApp();

  const links = [
    storeInfo.instagram && {
      key: 'instagram',
      label: `@${storeInfo.instagram}`,
      title: 'Instagram Auto18',
      href: `https://www.instagram.com/${storeInfo.instagram}`,
      Icon: Instagram,
    },
    storeInfo.tiktok && {
      key: 'tiktok',
      label: `@${storeInfo.tiktok}`,
      title: 'TikTok Auto18',
      href: `https://www.tiktok.com/@${storeInfo.tiktok}`,
      Icon: Music2,
    },
    storeInfo.youtube && {
      key: 'youtube',
      label: storeInfo.youtube,
      title: 'YouTube Auto18',
      href: `https://www.youtube.com/results?search_query=${encodeURIComponent(storeInfo.youtube)}`,
      Icon: Youtube,
    },
  ].filter(Boolean);

  if (links.length === 0) return null;

  if (variant === 'icon') {
    return (
      <div className={`flex items-center gap-2 ${className}`}>
        {links.map(({ key, href, title, Icon }) => (
          <a
            key={key}
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            title={title}
            aria-label={title}
            className="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 text-neutral-300 transition-colors hover:border-brand-500 hover:bg-brand-600 hover:text-white"
          >
            <Icon className="h-4 w-4" />
          </a>
        ))}
      </div>
    );
  }

  return (
    <div className={`flex flex-wrap items-center gap-2 ${className}`}>
      {links.map(({ key, href, title, label, Icon }) => (
        <a
          key={key}
          href={href}
          target="_blank"
          rel="noopener noreferrer"
          title={title}
          className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-black/40 px-3 py-1.5 text-xs font-semibold text-neutral-200 transition-colors hover:border-brand-500 hover:text-white"
        >
          <Icon className="h-3.5 w-3.5 text-brand-500" aria-hidden="true" />
          {label}
        </a>
      ))}
    </div>
  );
}
