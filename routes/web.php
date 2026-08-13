<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\LinkExternalController;
use App\Http\Controllers\SocialiteController;
use Illuminate\Support\Facades\Route;


Route::get("link/external",[LinkExternalController::class,"link"])->name("link.external");

Route::middleware('guest')->group(function (): void {
    Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('index');
    Route::get('/gate/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/gate/login', [AuthenticatedSessionController::class, 'store'])->name('login');
    Route::get('/auth/google/redirect', [SocialiteController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [SocialiteController::class, 'callback'])->name('google.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::get("/gate",GateController::class)->name("app.gate");
    Route::prefix("m/ujian")->group(base_path("routes/cbt.php"))->name("app.gate.ujian.");
    Route::prefix("m/pembelajaran")->group(base_path("routes/classroom.php"))->name("app.gate.classroom.");
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});