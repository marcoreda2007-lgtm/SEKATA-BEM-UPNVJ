<?php

use App\Http\Controllers\Admin\BookingQueueController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingDetailController;
use App\Http\Controllers\BookingProofController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    return Auth::user()->isAdmin()
        ? redirect()->route('admin.peminjaman.index')
        : redirect()->route('jadwal.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/jadwal', function () {
    return view('layouts.app');
})->middleware('auth')->name('jadwal.index');

Route::get('/admin/peminjaman', [BookingQueueController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.peminjaman.index');

Route::patch(
    '/admin/peminjaman/{id}/keputusan',
    [BookingQueueController::class, 'decide']
)->middleware(['auth', 'role:admin'])
    ->whereNumber('id')
    ->name('admin.peminjaman.decide');

Route::get('/peminjaman/{id}', [BookingDetailController::class, 'show'])
    ->middleware('auth')
    ->whereNumber('id')
    ->name('peminjaman.show');

Route::middleware('auth')->group(function () {
    Route::post('/peminjaman/{booking}/bukti', [BookingProofController::class, 'store'])
        ->name('peminjaman.bukti.store');
    Route::get('/peminjaman/{booking}/bukti/{category}', [BookingProofController::class, 'show'])
        ->whereIn('category', ['ruangan', 'kunci'])
        ->name('peminjaman.bukti.show');
    Route::get('/peminjaman/{booking}/bukti/{category}/unduh', [BookingProofController::class, 'download'])
        ->whereIn('category', ['ruangan', 'kunci'])
        ->name('peminjaman.bukti.download');
    Route::post('/admin/peminjaman/{booking}/pemeriksaan', [BookingProofController::class, 'inspect'])
        ->middleware('role:admin')
        ->name('admin.peminjaman.pemeriksaan.store');

    Route::get('/notifikasi', [NotificationController::class, 'index'])
        ->name('notifikasi.index');
    Route::patch('/notifikasi/{notification}', [NotificationController::class, 'markAsRead'])
        ->name('notifikasi.read');
});