# Auto18: rewrite from React to Laravel + MySQL

Date: 2026-11-12
Status: waiting for approval

## Why

Auto18 is a group assignment for the Web Programming course. The lecturer requires the site to be built with PHP and SQL, and the class uses Laravel. The current site is a React app that runs only in the browser and keeps its data in `localStorage`, so it does not meet the requirement. It has to be rebuilt as a Laravel application backed by MySQL.

The owner wants this done as quickly as possible so they can move on to another project.

## Goals

1. Every page and feature of the current site works in the Laravel version (full list under "Features to carry over").
2. All data lives in MySQL. Nothing that matters is stored only in the browser.
3. The project looks and reads like a standard Laravel project: routes, controllers, Eloquent models, Blade views, migrations, seeders, validation.
4. The site still looks like Auto18: black background, red diagonal accents, bold uppercase type, Indonesian UI text.
5. It runs on a stock XAMPP install (PHP 8.2, MySQL/MariaDB) with a short, documented setup.

## Non-goals

- Buyer or seller accounts. Only showroom staff log in.
- Multiple staff roles or permissions. One kind of admin.
- Online payments, email or WhatsApp sending, real leasing integration.
- Uploading car photos from disk. Cars keep the current "bundled photo or image URL" behaviour.
- Hosting or deployment. It only needs to run locally.
- Reproducing the Motion-library animations exactly.
- Keeping the React version runnable beside the Laravel one.

## Anti-goals

These would make the result a failure even if it works:

- A site that no longer looks like Auto18, or looks like an unstyled admin template.
- Business rules that exist only in JavaScript. Totals, stock, discounts, credit and status changes must be computed and checked in PHP.
- A React or other JavaScript single-page app with Laravel reduced to a JSON API. The lecturer expects PHP pages.
- Setup that needs tools beyond XAMPP, Composer and a browser.

## Decisions made by the owner

- Full rewrite in PHP, not a PHP backend behind the React pages.
- MySQL for all data.
- Laravel, because that is what the class uses.
- All current features are in scope.
- Speed matters more than polish.

## Decisions made by the designer (not yet confirmed by the owner)

- **Laravel 12**, the newest release that supports the installed PHP 8.2.12.
- **Blade** for all pages. No Livewire, no Inertia.
- **Tailwind CSS 3**, reusing the existing `tailwind.config.js` theme (`brand`, `ink`) and `src/index.css` so the markup and classes can be ported with little restyling.
- **Alpine.js** for in-page behaviour: modals, the POS cart, live calculators, the multi-step form.
- **Built assets are committed** (`public/build`), so the lecturer does not need Node to run the site. Node is only needed to change styles.
- **Staff-only login** with one seeded admin account. Beli Mobil and Jual Mobil are public; everything under the showroom panel requires login.
- **Favorites stay in the browser** (`localStorage`), since buyers have no accounts. This is the only data kept outside MySQL.
- **Catalog filtering and sorting happen on the server** through query-string parameters, so the URL is shareable and the SQL does the work.
- **Animations are simplified** to CSS transitions: modal fade and slide, a TERJUAL stamp that appears with a short CSS animation, toasts. The flying photo, the shifting grid and the rolling numbers are dropped.
- **Work happens on a new branch**, `feat/laravel-rewrite`. The Laravel project replaces the React files at the repository root. The React version stays in git history and on `main` until the team merges.
- **The GitHub Pages workflow and the standalone HTML build are removed**, because Pages cannot run PHP.

## Features to carry over

Behaviour should match the React version. The React source is the reference: `src/components/*.jsx` for screens, `src/context/AppContext.jsx` for the rules, `src/utils/carUtils.js` for the calculations, `src/data/initialData.js` for the demo data and fixed lists.

### Public

**Beli Mobil** (home page)
- Hero search by keyword, body type and price range.
- Brand and body-type shortcuts with unit counts.
- Filters: brand (multi-select), price, body type, transmission, fuel, year, mileage, location, favorites only, include sold cars.
- Sorting: recommended, newest, price, mileage, year.
- Car cards: photo, status badge, inspection score, cash price, estimated installment.
- Car detail: full specs, features, inspection report across 7 categories with the diagram, credit simulator, test-drive booking form, WhatsApp and call-center buttons.
- Favorites toggle.
- Brand content sections: benefits, buying tips, how it works, FAQ.

**Jual Mobil**
- Three-step form: car data, inspection schedule, contact data.
- Instant price estimate from brand, model, year, mileage, transmission and condition.
- Trade-in mode: choose a target car from stock and see the estimated top-up.
- Submitting creates a request with a code that appears in the showroom panel.

### Showroom panel (login required)

**Dashboard**
- Daily KPIs and showroom KPIs: cars available, sold this month, new requests, test drives.
- 7-day revenue chart split into car sales and accessory sales.

**Stok Mobil**
- Add, edit and delete cars. Set status: available, booked, sold. Mark as "Pilihan Auto18".
- Shows cost, selling price and margin.
- Process a sale: cash, transfer or credit (leasing partner, down payment, tenor, flat rate), with optional discount and trade-in.
- Cancel a sale: the transaction is marked void and excluded from Dashboard and Laporan, and the car returns to available. A trade-in car that already entered stock from that sale stays in stock.
- Sell and trade-in requests: schedule inspection, record result and offer, buy and add to stock, reject.
- Test drives: confirm, complete, cancel, with a "today" marker.

**Kasir Aksesoris**
- Product grid with category filter and search.
- Cart with quantity limits from stock, discount as amount or percent, optional PPN.
- Payment by cash, QRIS or transfer, with change calculation.
- Printable thermal-style receipt.

**Produk & Stok**
- Product CRUD, category add and delete, automatic margin, low-stock warning, manual stock adjustment.

**Suplier**
- Supplier CRUD.
- Record incoming stock: adds quantity to products and updates their cost price.

**Laporan**
- Sales list filtered by date range, sale type and payment method.
- CSV export.
- Reprint a receipt.

**Pengaturan**
- Store profile, contacts and social accounts, cashier name, receipt footer, PPN on or off and rate, credit rate, minimum down payment.
- Reset to demo data.

## Rules that must hold on the server

These are taken from `AppContext.jsx` and must be enforced in PHP inside database transactions:

- **Accessory sale:** quantity cannot exceed stock. Discount is capped at the subtotal. Tax applies to subtotal minus discount when enabled. Stock is reduced and the transaction with its items is saved together or not at all. Profit is the sum of (price minus cost) times quantity, minus the discount.
- **Car sale:** refuse if the car is already sold. Discount must be at least 0 and less than the price. Trade-in value must be between 0 and the total. Amount due is total minus trade-in. For cash, the amount received must cover the amount due. For credit, the down payment and installment come from the flat-rate formula in `calcInstallment`. The car becomes sold with sale date, sale price and a link to the transaction.
- **Trade-in into stock:** a trade-in car enters stock automatically only when it comes from an existing sell request. A trade-in typed in manually only reduces the amount due.
- **Buying from a sell request:** creates a car with status available, cost equal to the offer, and a default selling price of cost times 1.12 rounded to the nearest Rp 500.000. The request becomes purchased and links to the new car. A purchased request cannot be bought twice.
- **Cancelling a sale:** marks the transaction void with a timestamp and clears the car's sale fields. Void transactions are excluded from every total.
- **Car data:** inspection scores are clamped to 0..100 and the overall score is their rounded average.
- **Phone numbers:** Indonesian mobile format, as in `isValidPhone`.

The price estimate (`estimateCarPrice`) and the installment formula are ported to PHP. The browser may run the same formulas for instant feedback, but the values that get saved are the ones PHP computes.

## Data

Tables, roughly one per collection in the current app:

- `users`: staff accounts.
- `settings`: the store profile and rates (single row or key-value).
- `categories`, `products`, `suppliers`.
- `cars`: specs, prices, status, featured flag, image key and image URL, features, inspection scores, sale fields.
- `sell_requests`: customer and car data, type (sell or trade-in), schedule, estimate, offer, status (new, scheduled, inspected, purchased, rejected), links to the target car and the purchased car.
- `test_drives`: car, customer, date and time, location, status (pending, confirmed, done, cancelled).
- `transactions`: code, date, sale type (retail or car), customer, cashier, amounts, payment method, status, plus the snapshot of car, credit and trade-in details for car sales.
- `transaction_items`: line items with name, price and cost copied at the time of sale, so later product edits do not change old receipts.

Fixed lists (brands, body types, transmissions, fuels, conditions, colors, locations, price ranges, tenors, leasing partners, inspection categories) and the brand content (benefits, tips, FAQ) stay as configuration in code, not tables.

Seeders load the demo data from `initialData.js`: 12 cars, the products, categories and suppliers, 3 sell requests, 3 test drives, the sample transactions, and one admin user. Dates are relative to the day of seeding, as they are now, so the dashboard looks alive. The 12 car photos move to `public/`.

## Setup the lecturer will follow

1. Start Apache and MySQL in XAMPP.
2. Create a database named `auto18`.
3. `composer install`, copy `.env.example` to `.env`, `php artisan key:generate`.
4. `php artisan migrate --seed`.
5. `php artisan serve` and open the address it prints.

The README is rewritten around these steps and states the admin login.

## Order of work

One pass, ordered so there is always something that runs:

1. Laravel project, theme, layout (navbar, footer), database tables and demo data, login.
2. Beli Mobil and car detail, test-drive booking.
3. Jual Mobil.
4. Stok Mobil: car CRUD, sale, cancel sale, requests, test drives.
5. Kasir Aksesoris, payment, receipt.
6. Produk & Stok, Suplier.
7. Dashboard, Laporan, Pengaturan.
8. README, cleanup of the React files.

## Left to the builder

- Exact table columns, indexes and foreign keys.
- Whether settings is one row or key-value.
- Whether inspection scores and features are JSON columns or separate columns.
- How login is built (Laravel's built-in auth by hand, or a starter kit), as long as there are no public registration pages.
- How icons are rendered (the current site uses Lucide).
- How the revenue chart is drawn.
- Which flows get automated tests. At minimum the money rules above should be covered: accessory sale, car sale with each payment method, trade-in, cancel sale, buy from request.
- Route names and URL structure.
