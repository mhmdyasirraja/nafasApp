<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RiwayatController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat');

    // Polling tiap 5 dtk (+ grafik) = ~15 req/mnt per tab. Batas 120/mnt aman untuk beberapa tab.
    Route::middleware('throttle:120,1')->group(function () {
        Route::get('/perangkat/{perangkat}/snapshot', [DashboardController::class, 'snapshot'])
            ->whereNumber('perangkat')->name('perangkat.snapshot');
        Route::get('/perangkat/{perangkat}/riwayat', [DashboardController::class, 'riwayat'])
            ->whereNumber('perangkat')->name('perangkat.riwayat');
    });
});

// !!! SEMENTARA — HAPUS saat auth (login/register, SCS-10) sudah jadi. !!!
// Login otomatis sebagai user #1. Hanya terdaftar kalau APP_ENV=local.
// Jangan sampai APP_ENV=local di hosting: itu = siapa pun bisa masuk.
if (app()->isLocal()) {
    Route::get('/dev-login', function () {
        Auth::loginUsingId(1);

        return redirect()->route('dashboard');
    });
}
