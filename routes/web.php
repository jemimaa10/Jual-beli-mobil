<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Publik
// ---------------------------------------------------------------------------
Route::view('/', 'public.placeholder', ['title' => 'Beli Mobil'])->name('cars.index');
Route::view('/jual-mobil', 'public.placeholder', ['title' => 'Jual Mobil'])->name('sell.create');

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
