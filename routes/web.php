<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LinkExternalController;
use App\Http\Controllers\MataPelajaranGuruController;
use App\Http\Controllers\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::get('link/external', [LinkExternalController::class, 'link'])->name('link.external');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');
    Route::get('/auth/google/redirect', [SocialiteController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [SocialiteController::class, 'callback'])->name('google.callback');
});

Route::middleware('auth')->prefix("app")->group(function (): void {
    Route::get('/', DashboardController::class)->name('app.index');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('matpel-saya', [MataPelajaranGuruController::class, 'index'])->name('app.matpel');
});
