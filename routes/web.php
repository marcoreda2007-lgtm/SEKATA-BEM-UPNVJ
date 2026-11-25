<?php

use App\Http\Controllers\Admin\BookingQueueController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
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

Route::get('/admin/peminjaman', [BookingQueueController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.peminjaman.index');

Route::middleware('auth')->group(function () {
    Route::get('/notifikasi', [NotificationController::class, 'index'])
        ->name('notifikasi.index');
    Route::patch('/notifikasi/{notification}', [NotificationController::class, 'markAsRead'])
        ->name('notifikasi.read');
});