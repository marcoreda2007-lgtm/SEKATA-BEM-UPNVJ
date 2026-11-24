<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
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

Route::get('/jadwal', [CalendarController::class, 'index'])
    ->middleware('auth')
    ->name('jadwal.index');

Route::get('/admin/peminjaman', function () {
    return view('layouts.app');
})->middleware(['auth', 'role:admin'])->name('admin.peminjaman.index');