<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GymSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('users', UserController::class)->except(['show']);

        Route::get('settings', [GymSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [GymSettingController::class, 'update'])->name('settings.update');
    });
});
