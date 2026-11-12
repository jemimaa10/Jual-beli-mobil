<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Public\CarCatalogController;
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
});
