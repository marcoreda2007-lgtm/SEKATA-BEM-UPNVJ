<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingProofController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    return Auth::user()->isAdmin()
        ? redirect('/admin/peminjaman')
        : redirect('/jadwal');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/jadwal', function () {
    return view('layouts.app');
})->middleware('auth')->name('jadwal.index');

Route::get('/admin/peminjaman', function () {
    return view('layouts.app');
})->middleware(['auth', 'role:admin'])->name('admin.peminjaman.index');

Route::middleware('auth')->group(function () {
    Route::post('/peminjaman/{booking}/bukti', [BookingProofController::class, 'store'])
        ->name('peminjaman.bukti.store');
    Route::get('/peminjaman/{booking}/bukti/{category}', [BookingProofController::class, 'show'])
        ->whereIn('category', ['ruangan', 'kunci'])
        ->name('peminjaman.bukti.show');
    Route::post('/admin/peminjaman/{booking}/pemeriksaan', [BookingProofController::class, 'inspect'])
        ->middleware('role:admin')
        ->name('admin.peminjaman.pemeriksaan.store');
});
