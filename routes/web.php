<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingDetailController;
use App\Http\Controllers\Admin\BookingQueueController;

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

Route::get('/peminjaman/{id}', [BookingDetailController::class, 'show'])
    ->middleware('auth')
    ->whereNumber('id')
    ->name('peminjaman.show');

Route::get('/admin/peminjaman', [BookingQueueController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.peminjaman.index');
