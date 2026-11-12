<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Public\CarCatalogController;
use App\Http\Controllers\Showroom\CarController;
use App\Http\Controllers\Showroom\CarSaleController;
use App\Http\Controllers\Showroom\CategoryController;
use App\Http\Controllers\Showroom\PosController;
use App\Http\Controllers\Showroom\ProductController;
use App\Http\Controllers\Showroom\StockInController;
use App\Http\Controllers\Showroom\SupplierController;
use App\Http\Controllers\Showroom\ReceiptController;
use App\Http\Controllers\Showroom\SellRequestController;
use App\Http\Controllers\Showroom\TestDriveController;
use App\Http\Controllers\Public\SellCarController;
use App\Http\Controllers\Public\TestDriveBookingController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Publik
// ---------------------------------------------------------------------------
Route::get('/', [CarCatalogController::class, 'index'])->name('cars.index');
Route::get('/mobil/{car}', [CarCatalogController::class, 'show'])->name('cars.show');
Route::post('/mobil/{car}/test-drive', [TestDriveBookingController::class, 'store'])->name('cars.test-drive');
Route::get('/jual-mobil', [SellCarController::class, 'create'])->name('sell.create');
Route::post('/jual-mobil', [SellCarController::class, 'store'])->name('sell.store');

// ---------------------------------------------------------------------------
// Login staf showroom (tanpa registrasi publik)
// ---------------------------------------------------------------------------
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ---------------------------------------------------------------------------
// Panel showroom (wajib login)
// ---------------------------------------------------------------------------
Route::middleware('auth')->prefix('showroom')->name('showroom.')->group(function () {
    Route::view('/', 'showroom.placeholder', ['title' => 'Dashboard'])->name('dashboard');

    // Stok mobil, penjualan, permintaan jual/tukar tambah, test drive
    Route::resource('mobil', CarController::class)->parameters(['mobil' => 'car'])->except('show')->names('cars');
    Route::patch('mobil/{car}/status', [CarController::class, 'status'])->name('cars.status');
    Route::patch('mobil/{car}/unggulan', [CarController::class, 'feature'])->name('cars.feature');
    Route::get('mobil/{car}/jual', [CarSaleController::class, 'create'])->name('cars.sale.create');
    Route::post('mobil/{car}/jual', [CarSaleController::class, 'store'])->name('cars.sale.store');
    Route::post('mobil/{car}/batal-jual', [CarSaleController::class, 'void'])->name('cars.sale.void');

    Route::patch('permintaan/{sellRequest}', [SellRequestController::class, 'update'])->name('requests.update');
    Route::post('permintaan/{sellRequest}/beli', [SellRequestController::class, 'purchase'])->name('requests.purchase');
    Route::delete('permintaan/{sellRequest}', [SellRequestController::class, 'destroy'])->name('requests.destroy');

    Route::patch('test-drive/{testDrive}', [TestDriveController::class, 'update'])->name('test-drives.update');
    Route::delete('test-drive/{testDrive}', [TestDriveController::class, 'destroy'])->name('test-drives.destroy');

    // Kasir aksesoris
    Route::get('kasir', [PosController::class, 'index'])->name('pos');
    Route::post('kasir', [PosController::class, 'checkout'])->name('pos.checkout');

    // Produk & stok, kategori, suplier, barang masuk
    Route::resource('produk', ProductController::class)->parameters(['produk' => 'product'])->only(['index', 'store', 'update', 'destroy'])->names('products');
    Route::post('produk/{product}/stok', [ProductController::class, 'adjust'])->name('products.adjust');
    Route::post('kategori', [CategoryController::class, 'store'])->name('categories.store');
    Route::delete('kategori/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::resource('suplier', SupplierController::class)->parameters(['suplier' => 'supplier'])->only(['index', 'store', 'update', 'destroy'])->names('suppliers');
    Route::post('stok-masuk', [StockInController::class, 'store'])->name('stock-in');

    Route::get('transaksi/{transaction}/nota', [ReceiptController::class, 'show'])->name('receipt');
});
