# Auto18 Laravel Rewrite Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the Auto18 used-car showroom site, currently a browser-only React app, as a Laravel 12 application with Blade pages and a MySQL database, keeping every feature and the Auto18 look.

**Architecture:** A standard Laravel monolith. Controllers stay thin and render Blade views; the money and stock rules live in service classes under `app/Services` and run inside database transactions. Alpine.js handles in-page behaviour (modals, POS cart, live calculators), and the server recomputes everything it saves.

**Tech Stack:** PHP 8.2.12 (XAMPP), Laravel 12, MySQL/MariaDB, Blade, Tailwind CSS 3, Alpine.js 3, Vite, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-12-laravel-rewrite-design.md`

## Global Constraints

- Laravel 12 on PHP 8.2.12. Do not require a newer PHP.
- Blade only. No Livewire, no Inertia, no React, no JSON-API-plus-SPA.
- Tailwind CSS 3 with the existing theme (`brand`, `ink`) from the current `tailwind.config.js` and `src/index.css`.
- All UI text in Indonesian, copied from the React components.
- Every total, stock change, discount, credit figure and status change is computed and validated in PHP. JavaScript may preview, never decide.
- Only favorites live in `localStorage` (key `auto18_favoriteCarIds`). Everything else is in MySQL.
- Database name `auto18`. Seeded admin: `admin@auto18.test` / `auto18admin`. No public registration.
- `public/build` is committed. The site must run with XAMPP + Composer only.
- Work on branch `feat/laravel-rewrite`. Commit messages follow the repo style (Indonesian, `feat:` / `chore:` prefixes). No Claude attribution lines in commits or PRs.
- The React source is the behavioural reference. During the work it lives in `legacy-react/`; it is deleted in the last task.
- Money is stored as unsigned big integers in Rupiah. No floats for money.

## Review Focus

1. **Double submit of a car sale** (two tabs, back button): the second request must be refused with "Mobil ini sudah terjual." and create no second transaction. Test in Task 5.
2. **Stock changed between loading the kasir page and paying:** checkout must fail as a whole with a validation error naming the product, leaving stock and transactions untouched. Test in Task 6.
3. **Deleting a product, car, category or supplier that old records point to:** old receipts and reports must still render. Items keep their copied name and price; foreign keys are nullable and set null on delete. Tests in Tasks 5 and 7.
4. **Bad money input** (empty, negative, text, absurdly large, decimals with dots or commas): the form returns with a validation message, never a 500 page. Tests in Tasks 5 and 6.
5. **Guest opening any `/showroom/*` URL, or booking a test drive for a sold car:** guest is redirected to `/login`; the booking is refused with a message. Tests in Tasks 1 and 3.

---

## File Structure

```
app/
  helpers.php                      rupiah(), rupiah_short(), km(), car_name()
  Models/                          User, Setting, Category, Product, Supplier, Car,
                                   SellRequest, TestDrive, Transaction, TransactionItem
  Services/
    CarPricing.php                 estimate + installment formulas
    CodeGenerator.php              A18-0001, JUAL-0001, TD-0001, TRX-A18-1001
    RetailSaleService.php          kasir checkout
    CarSaleService.php             sell + void
    SellRequestService.php         purchase into stock
    DemoData.php                   reset to demo data
  Http/Controllers/
    AuthController.php
    Public/CarCatalogController.php, TestDriveBookingController.php, SellCarController.php
    Showroom/DashboardController.php, CarController.php, CarSaleController.php,
             SellRequestController.php, TestDriveController.php, PosController.php,
             ProductController.php, CategoryController.php, SupplierController.php,
             StockInController.php, ReportController.php, SettingController.php
config/auto18.php                  fixed lists + brand content
database/migrations/, database/seeders/
resources/views/
  layouts/app.blade.php, partials/ (navbar, footer, toast, logo, sold-stamp)
  public/ (catalog, car-detail, sell)
  showroom/ (dashboard, cars/, pos, products, suppliers, reports, settings, receipt)
  auth/login.blade.php
resources/js/app.js, resources/css/app.css
public/images/cars/*.jpg
tests/Unit/, tests/Feature/
```

## Routes (names are what views and tests use)

| Method | URL | Name |
|---|---|---|
| GET | `/` | `cars.index` |
| GET | `/mobil/{car}` | `cars.show` |
| POST | `/mobil/{car}/test-drive` | `cars.test-drive` |
| GET, POST | `/jual-mobil` | `sell.create`, `sell.store` |
| GET, POST | `/login` | `login`, `login.attempt` |
| POST | `/logout` | `logout` |
| GET | `/showroom` | `showroom.dashboard` |
| resource | `/showroom/mobil` | `showroom.cars.*` |
| PATCH | `/showroom/mobil/{car}/status` | `showroom.cars.status` |
| GET, POST | `/showroom/mobil/{car}/jual` | `showroom.cars.sale.create`, `.store` |
| POST | `/showroom/mobil/{car}/batal-jual` | `showroom.cars.sale.void` |
| PATCH, DELETE | `/showroom/permintaan/{sellRequest}` | `showroom.requests.update`, `.destroy` |
| POST | `/showroom/permintaan/{sellRequest}/beli` | `showroom.requests.purchase` |
| PATCH, DELETE | `/showroom/test-drive/{testDrive}` | `showroom.test-drives.update`, `.destroy` |
| GET, POST | `/showroom/kasir` | `showroom.pos`, `showroom.pos.checkout` |
| GET | `/showroom/transaksi/{transaction}/nota` | `showroom.receipt` |
| resource | `/showroom/produk` | `showroom.products.*` |
| POST | `/showroom/produk/{product}/stok` | `showroom.products.adjust` |
| POST, DELETE | `/showroom/kategori` | `showroom.categories.store`, `.destroy` |
| resource | `/showroom/suplier` | `showroom.suppliers.*` |
| POST | `/showroom/stok-masuk` | `showroom.stock-in` |
| GET | `/showroom/laporan`, `/showroom/laporan/export` | `showroom.reports`, `showroom.reports.export` |
| GET, PUT | `/showroom/pengaturan` | `showroom.settings`, `.update` |
| POST | `/showroom/pengaturan/reset` | `showroom.settings.reset` |

The car detail, which is a modal in React, becomes its own page. The car sale form also becomes its own page. Sell requests and test drives are tabs on the Stok Mobil page, as they are now.

---

### Task 1: Project, theme, layout, login

**Files:**
- Move: all React files (`src/`, `index.html`, `package*.json`, `vite.config.js`, `postcss.config.js`, `tailwind.config.js`, `scripts/`, `aplikasi-kasir-langsung-buka.html`) into `legacy-react/`
- Delete: `.github/workflows/deploy.yml`
- Create: Laravel 12 skeleton at repo root; `app/helpers.php`; `config/auto18.php`; `resources/views/layouts/app.blade.php`; `resources/views/partials/{navbar,footer,toast,logo}.blade.php`; `resources/views/auth/login.blade.php`; `app/Http/Controllers/AuthController.php`; `tailwind.config.js`; `postcss.config.js`; `resources/css/app.css`; `resources/js/app.js`
- Test: `tests/Unit/HelpersTest.php`, `tests/Feature/AuthTest.php`

**Interfaces:**
- Produces: `rupiah(int|float|null $n): string` ("Rp 1.250.000"); `rupiah_short($n): string` ("Rp 185 jt", "Rp 1,25 M", "Rp 30 rb"); `km($n): string` ("45.000 km"); `car_name(Car|array $car, bool $withYear = false): string`; `config('auto18.brands' | 'body_types' | 'transmissions' | 'fuels' | 'conditions' | 'colors' | 'locations' | 'price_ranges' | 'tenors' | 'leasing_partners' | 'inspection_categories' | 'benefits' | 'buying_tips' | 'how_it_works_buy' | 'how_it_works_sell' | 'faqs_buy' | 'faqs_sell')`; layout `layouts.app` with `@yield('content')` and a session-flash toast reading `session('success')` and `session('error')`; route names `login`, `logout`; middleware `auth` on the `showroom.` group.

- [ ] **Step 1:** Create branch `feat/laravel-rewrite`. `git mv` the React files into `legacy-react/`, delete the Pages workflow.
- [ ] **Step 2:** Create the Laravel project in the scratchpad with `composer create-project laravel/laravel:^12.0`, copy it into the repo root (merge `.gitignore`, drop its `README.md`). Set `.env` and `.env.example` to `DB_CONNECTION=mysql`, `DB_DATABASE=auto18`, `DB_USERNAME=root`, empty password, `APP_NAME=Auto18`, `APP_LOCALE=id`, `APP_TIMEZONE=Asia/Jakarta`. Remove `/public/build` from `.gitignore`.
- [ ] **Step 3:** Replace the skeleton's Tailwind 4 setup with Tailwind 3: install `tailwindcss@^3.4`, `postcss`, `autoprefixer`, `alpinejs`; port `legacy-react/tailwind.config.js` with `content` set to `resources/**/*.blade.php` and `resources/js/**/*.js`; port `legacy-react/src/index.css` into `resources/css/app.css`. Start Alpine in `resources/js/app.js`.
- [ ] **Step 4:** Write `tests/Unit/HelpersTest.php`:

```php
$this->assertSame('Rp 1.250.000', rupiah(1250000));
$this->assertSame('Rp 0', rupiah(null));
$this->assertSame('Rp 185 jt', rupiah_short(185000000));
$this->assertSame('Rp 1,25 M', rupiah_short(1250000000));
$this->assertSame('Rp 30 rb', rupiah_short(30000));
$this->assertSame('45.000 km', km(45000));
$this->assertSame('Toyota Fortuner 2.4 VRZ AT 2020',
    car_name(['brand' => 'Toyota', 'model' => 'Fortuner', 'variant' => '2.4 VRZ AT', 'year' => 2020], true));
```

- [ ] **Step 5:** Run `php artisan test --filter=HelpersTest`. Expected: FAIL, functions undefined. If the test runner cannot start because `pdo_sqlite` is missing, enable `extension=pdo_sqlite` in `C:\xampp\php\php.ini`.
- [ ] **Step 6:** Implement `app/helpers.php` (port `formatters.js` and the formatters in `carUtils.js`), register it under `autoload.files` in `composer.json`, run `composer dump-autoload`. Fill `config/auto18.php` from the constants in `legacy-react/src/data/initialData.js`.
- [ ] **Step 7:** Run the test. Expected: PASS.
- [ ] **Step 8:** Write `tests/Feature/AuthTest.php`: `guest_is_redirected_from_showroom` (`get('/showroom')` → `assertRedirect('/login')`); `admin_can_log_in` (post `login.attempt` with a factory user's credentials → redirect to `/showroom`, `assertAuthenticated`); `wrong_password_is_rejected` (→ session error on `email`, `assertGuest`); `no_register_route` (`get('/register')` → 404).
- [ ] **Step 9:** Run it. Expected: FAIL.
- [ ] **Step 10:** Implement `AuthController` (`showLogin`, `login`, `logout`) with `Auth::attempt` and session regeneration, the routes above, a placeholder `showroom.dashboard` view, and the layout: port `Navbar.jsx`, `Footer.jsx`, `Logo.jsx`, `Toaster.jsx` to Blade partials. The navbar shows the two public links always, and the six showroom links plus "Keluar" only when logged in; guests see "Masuk". Mobile menu is an Alpine toggle.
- [ ] **Step 11:** Run `php artisan test`. Expected: PASS. Run `npm run build`, start `php artisan serve`, open `/` and `/login` and confirm the black and red Auto18 styling renders.
- [ ] **Step 12:** Commit: `feat: kerangka Laravel 12, tema Auto18, dan login showroom`.

### Task 2: Database and demo data

**Files:**
- Create: migrations for `settings`, `categories`, `suppliers`, `products`, `cars`, `sell_requests`, `test_drives`, `transactions`, `transaction_items`; the ten models; `database/seeders/{DatabaseSeeder,AdminSeeder,SettingSeeder,CatalogSeeder,CarSeeder,RequestSeeder,TransactionSeeder}.php`; `app/Services/CodeGenerator.php`; `public/images/cars/*.jpg` (moved from `legacy-react/src/assets/cars/`)
- Test: `tests/Feature/SeedTest.php`, `tests/Unit/CodeGeneratorTest.php`

**Interfaces:**
- Produces (column names later tasks rely on, snake_case of the React fields):
  - `Setting::current(): Setting` (single row, id 1). Columns: `name, tagline, address, phone, call_center, whatsapp, email, instagram, tiktok, youtube, support_partner, cashier_name, receipt_footer, tax_enabled (bool), tax_rate, credit_rate, min_dp_percent`.
  - `Category`: `name, icon, color`. `Supplier`: fields from `INITIAL_SUPPLIERS`.
  - `Product`: `sku (unique), name, category_id (nullable, null on delete), supplier_id (nullable, null on delete), unit, buy_price, sell_price, stock, min_stock`.
  - `Car`: `code (unique), brand, model, variant, year, body_type, transmission, fuel, mileage, engine_cc, seats, color, color_hex, plate, location, price, buy_price, status ('available'|'booked'|'sold'), is_featured, owner_count, tax_valid_until, service_record, features (json array), description, image_key, image_url, inspection (json: key => 0..100), inspection_score, sold_at, sold_price, sold_transaction_id (nullable)`. Accessor `image_src` returns `image_url` if set, else `asset('images/cars/'.$image_key.'.jpg')`, else null. Scopes `available()`, `notSold()`.
  - `SellRequest`: `code, type ('sell'|'trade-in'), customer_name, phone, email, brand, model, variant, year, body_type, transmission, fuel, mileage, color, location, condition, asking_price, estimate_low, estimate_high, inspection_date, inspection_time, trade_in_car_id (nullable), notes, status ('new'|'scheduled'|'inspected'|'purchased'|'rejected'), offer_price, purchased_car_id (nullable)`.
  - `TestDrive`: `code, car_id (nullable, null on delete), car_name, customer_name, phone, date, time, location ('showroom'|'rumah'), address, notes, status ('pending'|'confirmed'|'done'|'cancelled')`.
  - `Transaction`: `code (unique), date, sale_type ('retail'|'car'), customer_name, customer_phone, cashier, subtotal, discount, tax, total, amount_due, payment_method ('cash'|'qris'|'transfer'|'credit'), paid_amount, change_amount, total_profit, status ('completed'|'void'), voided_at, car_id (nullable, null on delete), car_snapshot (json), credit (json), trade_in (json), notes`. Relation `items()`. Scope `completed()`.
  - `TransactionItem`: `transaction_id, product_id (nullable, null on delete), sku, name, unit, price, buy_price, qty, subtotal`.
  - `CodeGenerator::car(): string`, `::sellRequest(): string`, `::testDrive(): string`, `::transaction(): string`. Each returns the prefix plus the highest existing number plus one, zero-padded to 4 digits: `A18-0013`, `JUAL-0004`, `TD-0004`, `TRX-A18-1009`.

- [ ] **Step 1:** Write `tests/Feature/SeedTest.php` (uses `RefreshDatabase`, runs `$this->seed()`): asserts `Car::count() === 12`, `SellRequest::count() === 3`, `TestDrive::count() === 3`, `User::where('email','admin@auto18.test')->exists()`, `Setting::current()->name === 'Auto18'`, `Setting::current()->credit_rate == 7`, product and category counts equal to the lengths of `INITIAL_PRODUCTS` and `INITIAL_CATEGORIES`, every `Transaction` has at least one item, and `Car::where('code','A18-0001')->first()->inspection_score` equals the rounded average of its seven scores.
- [ ] **Step 2:** Write `tests/Unit/CodeGeneratorTest.php`: after seeding, `CodeGenerator::car() === 'A18-0013'`, `sellRequest() === 'JUAL-0004'`, `testDrive() === 'TD-0004'`; on an empty database `car() === 'A18-0001'`.
- [ ] **Step 3:** Run both. Expected: FAIL.
- [ ] **Step 4:** Write the migrations, models (casts for json, bool, date columns), and `CodeGenerator`.
- [ ] **Step 5:** Write the seeders by porting `legacy-react/src/data/initialData.js` record for record. Keep dates relative to `now()` the way `at()` and `dateStr()` do. Seed transaction items by matching `sku` to products. Move the 12 photos; `image_key` is the file name without extension.
- [ ] **Step 6:** Create the `auto18` database in XAMPP MySQL and run `php artisan migrate:fresh --seed`. Expected: no errors. Run `php artisan test`. Expected: PASS.
- [ ] **Step 7:** Commit: `feat: skema database MySQL dan data demo Auto18`.

### Task 3: Beli Mobil, car detail, test-drive booking

**Files:**
- Create: `app/Services/CarPricing.php`; `app/Http/Controllers/Public/{CarCatalogController,TestDriveBookingController}.php`; `app/Http/Requests/StoreTestDriveRequest.php`; `app/Rules/IndonesianPhone.php`; `resources/views/public/{catalog,car-detail}.blade.php`; `resources/views/partials/{car-card,sold-stamp,inspection-diagram,credit-simulator,auto18-sections}.blade.php`
- Test: `tests/Unit/CarPricingTest.php`, `tests/Feature/CatalogTest.php`, `tests/Feature/TestDriveBookingTest.php`

**Interfaces:**
- Produces: `CarPricing::installment(int $price, float $dpPercent, int $tenorMonths, float $annualRate): array{dpAmount:int, principal:int, totalInterest:int, monthly:int, totalPayment:int}`; `CarPricing::estimate(array $car): array{low:int, high:int, mid:int}` with keys `brand, model, body_type, year, mileage, transmission, condition`; `App\Rules\IndonesianPhone`; a global JS function `window.auto18.installment({price, dpPercent, tenorMonths, annualRate})` in `resources/js/app.js` with the same formula, used by the simulator and by Task 5.
- Catalog query parameters: `q`, `brand[]`, `body`, `transmission`, `fuel`, `price_min`, `price_max`, `year_min`, `year_max`, `km_max`, `location`, `sold=1`, `sort` (`recommended|newest|price_asc|price_desc|km_asc|year_desc`). "Favorites only" is filtered in the browser with Alpine, because favorites are not on the server.

- [ ] **Step 1:** Write `tests/Unit/CarPricingTest.php`:

```php
$r = CarPricing::installment(200_000_000, 20, 36, 7);
$this->assertSame(40_000_000, $r['dpAmount']);
$this->assertSame(160_000_000, $r['principal']);
$this->assertSame(33_600_000, $r['totalInterest']);
$this->assertSame(5_378_000, $r['monthly']);
$this->assertSame(233_608_000, $r['totalPayment']);
```

Add `estimate_matches_the_javascript_version`: generate three expected results by running `estimateCarPrice` from `legacy-react/src/utils/carUtils.js` with Node for (Toyota Avanza MPV, 2019, 60000 km, Manual, Baik), (BMW unknown-model Sedan, 2016, 90000 km, Otomatis, Cukup), (Honda Brio Hatchback, current year, 5000 km, Otomatis, Sangat Baik), freeze the year with `Carbon::setTestNow`, and assert PHP returns the same `low`, `mid`, `high`.
- [ ] **Step 2:** Run. Expected: FAIL. Implement `CarPricing` by porting `calcInstallment`, `roundUpTo` and `estimateCarPrice` with their tables. Run. Expected: PASS.
- [ ] **Step 3:** Write `tests/Feature/CatalogTest.php` (seeded): home returns 200 and shows a seeded car name; `?brand[]=Toyota` shows only Toyota cards (assert a Honda model name is absent); `?q=fortuner` finds the Fortuner; sold cars are hidden by default and shown with `?sold=1`; `?sort=price_asc` lists the cheapest car first (`assertSeeInOrder`); `?price_min=abc&km_max=-5` returns 200, not an error; `cars.show` returns 200 and shows the inspection category labels.
- [ ] **Step 4:** Write `tests/Feature/TestDriveBookingTest.php`: valid post creates a `pending` test drive with code `TD-0004` and `car_name` filled, redirects back with `success`; phone `12345` fails validation on `phone`; a date in the past fails on `date`; booking a car with status `sold` is refused with an `error` flash and creates nothing.
- [ ] **Step 5:** Run. Expected: FAIL.
- [ ] **Step 6:** Implement the controllers and views. Port `CarMarketplace.jsx`, `CarCard.jsx`, `CarImage.jsx`, `CarDetailModal.jsx`, `InspectionDiagram.jsx`, `CreditSimulator.jsx`, `SoldStamp.jsx`, `Auto18Sections.jsx`. Filters are one GET form; the filter panel is a drawer on mobile. Recommended order: featured first, then inspection score descending, then newest. Favorites: an Alpine store backed by `localStorage` key `auto18_favoriteCarIds`. The sold stamp uses a CSS keyframe that respects `prefers-reduced-motion`. WhatsApp and call buttons use `Setting::current()`.
- [ ] **Step 7:** Run `php artisan test`. Expected: PASS. Rebuild assets and click through the catalog and one detail page in the browser at phone and desktop width.
- [ ] **Step 8:** Commit: `feat: halaman Beli Mobil, detail mobil, dan booking test drive`.

### Task 4: Jual Mobil

**Files:**
- Create: `app/Http/Controllers/Public/SellCarController.php`; `app/Http/Requests/StoreSellRequest.php`; `resources/views/public/sell.blade.php`
- Test: `tests/Feature/SellCarTest.php`

**Interfaces:**
- Consumes: `CarPricing::estimate`, `CodeGenerator::sellRequest`, `IndonesianPhone`.
- Produces: `window.auto18.estimate(car)` in `resources/js/app.js`, same formula as PHP, for the live preview. The lookup tables are passed from PHP to JS once (a `@json` block from `CarPricing::tables()`) so they exist in one place.

- [ ] **Step 1:** Write `tests/Feature/SellCarTest.php`: the page returns 200; a valid `sell` post creates a request with status `new`, code `JUAL-0004`, and `estimate_low`/`estimate_high` equal to `CarPricing::estimate(...)` for the posted car, ignoring any estimate values sent by the browser; a `trade-in` post without `trade_in_car_id` fails validation; a `trade-in` post pointing at a sold car fails validation; missing `customer_name` or an invalid phone fails; `year` of 1800 or next year plus two fails.
- [ ] **Step 2:** Run. Expected: FAIL.
- [ ] **Step 3:** Implement. Port `SellCar.jsx`: three steps in one form with Alpine showing one step at a time, per-step checks before "Lanjut", live estimate, trade-in mode with the target-car picker and the estimated top-up (target price minus estimate mid). After saving, show the confirmation with the request code. If server validation fails, reopen the step that holds the first error.
- [ ] **Step 4:** Run `php artisan test`. Expected: PASS. Submit one request in the browser.
- [ ] **Step 5:** Commit: `feat: formulir Jual Mobil dan tukar tambah`.

### Task 5: Stok Mobil, penjualan mobil, permintaan, test drive

**Files:**
- Create: `app/Services/{CarSaleService,SellRequestService}.php`; `app/Exceptions/SaleException.php`; `app/Http/Controllers/Showroom/{CarController,CarSaleController,SellRequestController,TestDriveController}.php`; `app/Http/Requests/{SaveCarRequest,SellCarRequest}.php`; `resources/views/showroom/cars/{index,form,sale}.blade.php`; `resources/views/showroom/receipt.blade.php`
- Test: `tests/Feature/CarSaleTest.php`, `tests/Feature/SellRequestPurchaseTest.php`, `tests/Feature/CarManagementTest.php`

**Interfaces:**
- Consumes: `CarPricing::installment`, `CodeGenerator`, `Setting::current()`.
- Produces:
  - `CarSaleService::sell(Car $car, array $data): Transaction`. `$data` keys: `customer_name, customer_phone, payment_method ('cash'|'transfer'|'credit'), discount, paid_amount, credit{leasing, dp_percent, tenor_months, annual_rate}, trade_in{request_id, car_name, value}, notes`. Throws `SaleException` with the Indonesian message on any rule violation.
  - `CarSaleService::void(Car $car): ?Transaction`.
  - `SellRequestService::purchase(SellRequest $r, ?int $offerPrice = null, ?int $sellPrice = null): Car`. Throws `SaleException` if already purchased.
  - View `showroom.receipt` taking a `Transaction`, used again by Tasks 6 and 7. It renders both the car sale note and the thermal retail receipt, with a print stylesheet.
  - Controllers catch `SaleException` and redirect back with `error` and old input.

- [ ] **Step 1:** Write `tests/Feature/CarSaleTest.php`. Use a factory car with `price 250_000_000`, `buy_price 220_000_000`.
  - `cash_sale`: discount `5_000_000`, paid `250_000_000` → transaction `sale_type car`, `subtotal 250_000_000`, `total 245_000_000`, `amount_due 245_000_000`, `change_amount 5_000_000`, `total_profit 25_000_000`; car is `sold`, `sold_price 245_000_000`, `sold_transaction_id` set; one item with `qty 1`.
  - `cash_with_empty_paid_amount_means_exact`: `paid_amount` null → `paid_amount 245_000_000`, `change_amount 0`.
  - `cash_underpaid_is_refused`: message "Uang diterima kurang dari sisa yang harus dibayar."; car stays available; no transaction.
  - `credit_sale`: no discount, dp 20, tenor 36, rate 7 on a `200_000_000` car → `paid_amount 40_000_000`, `credit.monthly 5_378_000`, `credit.principal 160_000_000`.
  - `credit_defaults_come_from_settings` when dp and rate are omitted.
  - `discount_equal_to_price_is_refused` and `negative_discount_is_refused`: "Diskon tidak valid atau melebihi harga mobil."
  - `manual_trade_in_reduces_amount_due_only`: value `100_000_000` → `amount_due 145_000_000`, `Car::count()` unchanged.
  - `trade_in_from_request_enters_stock`: with `request_id` → a new available car with `buy_price 100_000_000`, the request is `purchased` and linked.
  - `trade_in_above_total_is_refused`: "Nilai tukar tambah melebihi total harga mobil."
  - `selling_a_sold_car_is_refused` (Review Focus 1): second call → "Mobil ini sudah terjual.", `Transaction::count() === 1`.
  - `void_marks_transaction_and_frees_car`: transaction `status void`, `voided_at` set; car `available` with sale fields null; the trade-in car from the sale is still in stock.
  - `non_numeric_money_returns_validation_error` (Review Focus 4): HTTP post with `discount=abc` and with `discount=99999999999999999999` → redirect with errors, status not 500.
- [ ] **Step 2:** Write `tests/Feature/SellRequestPurchaseTest.php`: offer `96_000_000` and no sell price → car `buy_price 96_000_000`, `price 107_500_000`, `status available`, description mentions the request code; request `purchased`, `offer_price 96_000_000`, `purchased_car_id` set; purchasing again throws; status transitions `new → scheduled → inspected` via `showroom.requests.update` persist date, time and offer; `rejected` works; a `purchased` request cannot be edited back.
- [ ] **Step 3:** Write `tests/Feature/CarManagementTest.php`: create a car with features `"Sunroof, Kamera 360 , "` → stored as `['Sunroof','Kamera 360']`, code generated, inspection scores above 100 clamped to 100 and below 0 to 0, `inspection_score` is the rounded average; update and delete work; deleting a car that has a test drive and a completed transaction leaves both rows with `car_id` null and the receipt page still returns 200 (Review Focus 3); status change to `booked` and back; changing status away from `sold` through `showroom.cars.status` is rejected (cancelling a sale goes through void); test-drive update to `confirmed`, `done`, `cancelled` and delete.
- [ ] **Step 4:** Run. Expected: FAIL.
- [ ] **Step 5:** Implement the services inside `DB::transaction`, locking the car row with `lockForUpdate()` before checking its status. Then the controllers, form requests and views: port `CarInventory.jsx` (three tabs: stok, permintaan, test drive, with the "hari ini" marker and the cost, price and margin columns), `CarFormModal.jsx`, `CarCheckoutModal.jsx` (live amount due and installment preview with `window.auto18.installment`; a dropdown of inspected trade-in requests fills the trade-in fields), and the car part of `ReceiptModal.jsx`.
- [ ] **Step 6:** Run `php artisan test`. Expected: PASS. In the browser: add a car, sell it on credit with a trade-in from a request, print the note, cancel the sale.
- [ ] **Step 7:** Commit: `feat: panel Stok Mobil, penjualan mobil, permintaan, dan test drive`.

### Task 6: Kasir Aksesoris

**Files:**
- Create: `app/Services/RetailSaleService.php`; `app/Http/Controllers/Showroom/PosController.php`; `app/Http/Requests/CheckoutRequest.php`; `resources/views/showroom/pos.blade.php`
- Modify: `resources/views/showroom/receipt.blade.php` (retail layout)
- Test: `tests/Feature/PosCheckoutTest.php`

**Interfaces:**
- Consumes: `SaleException`, `CodeGenerator::transaction`, `Setting::current()`, view `showroom.receipt`.
- Produces: `RetailSaleService::checkout(array $items, array $data): Transaction`. `$items` is `[['product_id' => int, 'qty' => int], ...]`. `$data` keys: `discount_type ('nominal'|'percent'), discount_value, payment_method ('cash'|'qris'|'transfer'), paid_amount, customer_name`. Prices and costs are read from the database, never from the request.

- [ ] **Step 1:** Write `tests/Feature/PosCheckoutTest.php`. Products: A `sell_price 335_000`, `buy_price 285_000`, `stock 10`; B `sell_price 30_000`, `buy_price 18_000`, `stock 5`. Cart: A × 1, B × 2.
  - `plain_cash_checkout`: paid `400_000` → `subtotal 395_000`, `discount 0`, `tax 0`, `total 395_000`, `change_amount 5_000`, `total_profit 74_000`; stock A 9, B 3; two items with copied `name`, `sku`, `price`, `buy_price`; `customer_name` defaults to "Pelanggan Umum"; `cashier` equals the setting's `cashier_name`.
  - `percent_discount_and_tax`: tax enabled at 11, discount 10 percent → `discount 39_500`, `tax 39_105`, `total 394_605`, `total_profit 34_500`.
  - `nominal_discount_is_capped_at_subtotal`: discount `9_999_999` → `discount 395_000`, `total 0`.
  - `percent_is_clamped_to_0_100`.
  - `qty_above_stock_fails_whole_checkout` (Review Focus 2): B × 6 → validation error naming B, stock unchanged, `Transaction::count() === 0`.
  - `cash_underpaid_is_refused`; `qris_and_transfer_set_paid_amount_to_total`.
  - `empty_cart_is_refused`; `qty_zero_or_negative_is_refused`; `unknown_product_id_is_refused`.
  - `client_supplied_prices_are_ignored`: posting `price=1` for A changes nothing.
  - `bad_money_input_returns_validation_error` (Review Focus 4): `paid_amount=abc`.
  - `successful_checkout_redirects_to_receipt`: redirect to `showroom.receipt` for the new transaction.
- [ ] **Step 2:** Run. Expected: FAIL.
- [ ] **Step 3:** Implement `RetailSaleService` in `DB::transaction` with `lockForUpdate()` on the product rows. Port `PosTerminal.jsx` and `PaymentModal.jsx`: product grid with category chips and search (filtered in Alpine over the products passed with `@json`), cart as an Alpine component with stock limits, discount type toggle, live totals, payment modal with quick-cash buttons and change. Paying submits a normal form with `items[i][product_id]`, `items[i][qty]`. Port the retail part of `ReceiptModal.jsx` into `showroom.receipt` with a thermal-width print style and a "Cetak" button.
- [ ] **Step 4:** Run `php artisan test`. Expected: PASS. Ring up one sale in the browser and print the receipt.
- [ ] **Step 5:** Commit: `feat: Kasir Aksesoris dengan pembayaran dan struk`.

### Task 7: Produk & Stok, Suplier

**Files:**
- Create: `app/Http/Controllers/Showroom/{ProductController,CategoryController,SupplierController,StockInController}.php`; `app/Http/Requests/{SaveProductRequest,SaveSupplierRequest,StockInRequest}.php`; `resources/views/showroom/{products,suppliers}.blade.php`
- Test: `tests/Feature/ProductManagementTest.php`, `tests/Feature/SupplierStockInTest.php`

**Interfaces:**
- Consumes: models from Task 2.
- Produces: nothing later tasks depend on.

- [ ] **Step 1:** Write `tests/Feature/ProductManagementTest.php`: create with empty `min_stock` → 5; duplicate `sku` fails validation; update; delete a product that appears in an old transaction → item keeps its name and `product_id` is null, and the receipt and report pages still return 200 (Review Focus 3); adjust stock `+5` and `-3`; adjusting below zero stops at 0; the page marks products with `stock <= min_stock` as "Stok menipis"; add a category; deleting a category with products sets their `category_id` to null.
- [ ] **Step 2:** Write `tests/Feature/SupplierStockInTest.php`: supplier create, update, delete (products keep working with `supplier_id` null); stock-in with two lines adds each `qty` to stock and updates `buy_price` when a price is given, leaves it when empty; `qty` of 0 or negative fails; unknown product fails; the whole stock-in is one database transaction.
- [ ] **Step 3:** Run. Expected: FAIL.
- [ ] **Step 4:** Implement. Port `ProductManagement.jsx` and `SupplierManagement.jsx`: tables with search and category filter, add and edit in Alpine modals posting normal forms, automatic margin display, the stock-in form with addable rows.
- [ ] **Step 5:** Run `php artisan test`. Expected: PASS. Try each form once in the browser.
- [ ] **Step 6:** Commit: `feat: manajemen Produk & Stok dan Suplier`.

### Task 8: Dashboard, Laporan, Pengaturan

**Files:**
- Create: `app/Http/Controllers/Showroom/{DashboardController,ReportController,SettingController}.php`; `app/Services/DemoData.php`; `app/Http/Requests/SaveSettingRequest.php`; `resources/views/showroom/{dashboard,reports,settings}.blade.php`
- Test: `tests/Feature/DashboardTest.php`, `tests/Feature/ReportTest.php`, `tests/Feature/SettingTest.php`

**Interfaces:**
- Consumes: `Transaction::completed()`, view `showroom.receipt`, the seeders.
- Produces: `DemoData::reset(): void`, which empties every table except `users` and reruns the data seeders.

- [ ] **Step 1:** Write `tests/Feature/DashboardTest.php` with hand-built data: today's revenue, profit and transaction count ignore a `void` transaction; "terjual bulan ini" counts cars sold this month only; "permintaan baru" counts `new` requests; test drives today are counted; the 7-day series passed to the view has 7 entries, each with separate `car` and `retail` totals, and yesterday's entry equals the sum of yesterday's completed transactions per type.
- [ ] **Step 2:** Write `tests/Feature/ReportTest.php`: default list shows completed and void rows with void labelled "Dibatalkan" and excluded from the totals; filters `from`, `to`, `sale_type`, `payment_method` narrow the list; an inverted or malformed date range returns 200; export returns `text/csv` with a header row and one row per filtered transaction, and a customer name containing a comma and a quote is escaped correctly; the receipt link reprints an old transaction.
- [ ] **Step 3:** Write `tests/Feature/SettingTest.php`: update saves all fields; `tax_rate` outside 0..100 fails; `min_dp_percent` outside 0..100 fails; after update the receipt footer and the public WhatsApp button reflect the new values; reset restores 12 cars and the seeded settings and keeps the admin user logged in.
- [ ] **Step 4:** Run. Expected: FAIL.
- [ ] **Step 5:** Implement. Port `Dashboard.jsx` (KPI tiles, low-stock list, recent transactions, the chart as inline SVG or CSS bars with a text table for screen readers), `SalesReports.jsx`, `SettingsModal.jsx` (as a page). CSV export streams with `fputcsv` and a UTF-8 BOM so Excel reads Indonesian text. The reset button asks for confirmation.
- [ ] **Step 6:** Run `php artisan test`. Expected: PASS. Check each page in the browser.
- [ ] **Step 7:** Commit: `feat: Dashboard, Laporan penjualan, dan Pengaturan showroom`.

### Task 9: README, cleanup, final check

**Files:**
- Modify: `README.md`
- Delete: `legacy-react/`
- Commit: `public/build`

- [ ] **Step 1:** Compare the finished site against "Features to carry over" in the spec, page by page, and fix anything missing.
- [ ] **Step 2:** Rewrite `README.md` in Indonesian: what Auto18 is, features, tech stack (Laravel 12, MySQL, Blade, Tailwind, Alpine), the five setup steps from the spec, the admin login, how to reset demo data, how to run the tests.
- [ ] **Step 3:** Delete `legacy-react/`, run `npm run build`, confirm `public/build` is tracked.
- [ ] **Step 4:** Fresh-install check: `php artisan migrate:fresh --seed`, `php artisan test` (expected: all pass), then `php artisan serve` and walk through: browse and filter cars, book a test drive, submit a trade-in request, log in, buy the request, sell a car on credit, cancel it, ring up a kasir sale, add stock from a supplier, open Dashboard and Laporan, export CSV, change a setting, reset demo data.
- [ ] **Step 5:** Commit: `chore: README Laravel, hapus sumber React lama, sertakan aset hasil build`.
