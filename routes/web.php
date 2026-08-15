<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\LinkExternalController;
use App\Http\Controllers\MataPelajaranGuruController;
use App\Http\Controllers\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::get('link/external', [LinkExternalController::class, 'link'])->name('external.link');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('auth.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('auth.login');
    Route::get('/auth/google/redirect', [SocialiteController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [SocialiteController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth',"app-only"])->prefix('app')->name("app.")->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get("matpel/{matpel}/kelas-{id}/manage", KelasController::class)->name("kelas.room");
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('auth.logout');
    Route::get('matpel-saya', [MataPelajaranGuruController::class, 'index'])->name('app.matpel');
});

Route::prefix('admin')->middleware(['auth', 'admin-only'])->name('admin.')->group(base_path('routes/admin.php'));
