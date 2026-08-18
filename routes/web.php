<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Guru\MateriController as GuruMateriController;
use App\Http\Controllers\Guru\TugasController as GuruTugasController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\LinkExternalController;
use App\Http\Controllers\MataPelajaranGuruController;
use App\Http\Controllers\Siswa\MateriController as SiswaMateriController;
use App\Http\Controllers\Siswa\TugasController as SiswaTugasController;
use App\Http\Controllers\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::get('link/external', [LinkExternalController::class, 'link'])->name('external.link');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('auth.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('auth.login');
    Route::get('/auth/google/redirect', [SocialiteController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [SocialiteController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', 'app-only'])->prefix('app')->name('app.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('matpel/{matpel}/kelas-{id}/manage', KelasController::class)->name('kelas.room');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('auth.logout');
    Route::get('matpel-saya', [MataPelajaranGuruController::class, 'index'])->name('matpel');
    Route::get('guru/materi', [GuruMateriController::class, 'index'])->name('guru.materi.index');
    Route::get('guru/materi/katalog', [GuruMateriController::class, 'katalog'])->name('guru.materi.katalog');
    Route::post('guru/materi', [GuruMateriController::class, 'store'])->name('guru.materi.store');
    Route::post('guru/materi/salin', [GuruMateriController::class, 'salin'])->name('guru.materi.salin');
    Route::get('guru/materi/{materi}/edit', [GuruMateriController::class, 'edit'])->name('guru.materi.edit');
    Route::put('guru/materi/{materi}', [GuruMateriController::class, 'update'])->name('guru.materi.update');
    Route::delete('guru/materi/{materi}', [GuruMateriController::class, 'destroy'])->name('guru.materi.destroy');
    Route::get('guru/materi/{materi}/unduh', [GuruMateriController::class, 'unduh'])->name('guru.materi.unduh');
    Route::get('guru/tugas', [GuruTugasController::class, 'index'])->name('guru.tugas.index');
    Route::post('guru/tugas', [GuruTugasController::class, 'store'])->name('guru.tugas.store');
    Route::get('guru/tugas/{tugas}/edit', [GuruTugasController::class, 'edit'])->name('guru.tugas.edit');
    Route::put('guru/tugas/{tugas}', [GuruTugasController::class, 'update'])->name('guru.tugas.update');
    Route::delete('guru/tugas/{tugas}', [GuruTugasController::class, 'destroy'])->name('guru.tugas.destroy');
    Route::get('guru/tugas/{tugas}/unduh', [GuruTugasController::class, 'unduh'])->name('guru.tugas.unduh');
    Route::get('guru/tugas/{tugas}/pengumpulan', [GuruTugasController::class, 'pengumpulan'])->name('guru.tugas.pengumpulan');
    Route::get('guru/tugas/{tugas}/pengumpulan/{pengumpulan}/unduh', [GuruTugasController::class, 'pengumpulanUnduh'])->name('guru.tugas.pengumpulan.unduh');
    Route::get('materi', [SiswaMateriController::class, 'index'])->name('siswa.materi.index');
    Route::get('materi/{materi}', [SiswaMateriController::class, 'show'])->name('siswa.materi.show');
    Route::get('materi/{materi}/lihat', [SiswaMateriController::class, 'lihat'])->name('siswa.materi.lihat');
    Route::get('materi/{materi}/unduh', [SiswaMateriController::class, 'unduh'])->name('siswa.materi.unduh');
    Route::get('tugas', [SiswaTugasController::class, 'index'])->name('siswa.tugas.index');
    Route::get('tugas/{tugas}', [SiswaTugasController::class, 'show'])->name('siswa.tugas.show');
    Route::post('tugas/{tugas}/kumpul', [SiswaTugasController::class, 'kumpul'])->name('siswa.tugas.kumpul');
    Route::get('tugas/{tugas}/unduh', [SiswaTugasController::class, 'unduh'])->name('siswa.tugas.unduh');
});

Route::prefix('admin')->middleware(['auth', 'admin-only'])->name('admin.')->group(base_path('routes/admin.php'));
