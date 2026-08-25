<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Guru\BankSoalController as GuruBankSoalController;
use App\Http\Controllers\Guru\HasilUjianController as GuruHasilUjianController;
use App\Http\Controllers\Guru\MateriController as GuruMateriController;
use App\Http\Controllers\Guru\PenilaianController as GuruPenilaianController;
use App\Http\Controllers\Guru\SoalController as GuruSoalController;
use App\Http\Controllers\Guru\TugasController as GuruTugasController;
use App\Http\Controllers\Guru\UjianController as GuruUjianController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\LinkExternalController;
use App\Http\Controllers\MataPelajaranGuruController;
use App\Http\Controllers\Siswa\MateriController as SiswaMateriController;
use App\Http\Controllers\Siswa\PenilaianController as SiswaPenilaianController;
use App\Http\Controllers\Siswa\TugasController as SiswaTugasController;
use App\Http\Controllers\Siswa\UjianController as SiswaUjianController;
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
    Route::put('guru/tugas/{tugas}/nilai', [GuruTugasController::class, 'nilai'])->name('guru.tugas.nilai');
    Route::get('guru/tugas/{tugas}/pengumpulan/{pengumpulan}/unduh', [GuruTugasController::class, 'pengumpulanUnduh'])->name('guru.tugas.pengumpulan.unduh');
    Route::get('guru/penilaian', [GuruPenilaianController::class, 'index'])->name('guru.penilaian.index');
    Route::get('guru/penilaian/rekap', [GuruPenilaianController::class, 'rekap'])->name('guru.penilaian.rekap');
    Route::get('guru/penilaian/{penilaian}/{guruKelas}', [GuruPenilaianController::class, 'show'])->name('guru.penilaian.show');
    Route::post('guru/penilaian/{penilaian}/{guruKelas}', [GuruPenilaianController::class, 'store'])->name('guru.penilaian.store');
    Route::get('materi', [SiswaMateriController::class, 'index'])->name('siswa.materi.index');
    Route::get('materi/{materi}', [SiswaMateriController::class, 'show'])->name('siswa.materi.show');
    Route::get('materi/{materi}/lihat', [SiswaMateriController::class, 'lihat'])->name('siswa.materi.lihat');
    Route::get('materi/{materi}/unduh', [SiswaMateriController::class, 'unduh'])->name('siswa.materi.unduh');
    Route::get('tugas', [SiswaTugasController::class, 'index'])->name('siswa.tugas.index');
    Route::get('tugas/{tugas}', [SiswaTugasController::class, 'show'])->name('siswa.tugas.show');
    Route::post('tugas/{tugas}/kumpul', [SiswaTugasController::class, 'kumpul'])->name('siswa.tugas.kumpul');
    Route::get('tugas/{tugas}/unduh', [SiswaTugasController::class, 'unduh'])->name('siswa.tugas.unduh');
    Route::get('nilai', [SiswaPenilaianController::class, 'index'])->name('siswa.penilaian.index');

    // Guru: Ujian (CBT)
    Route::get('guru/ujian', [GuruUjianController::class, 'index'])->name('guru.ujian.index');
    Route::post('guru/ujian', [GuruUjianController::class, 'store'])->name('guru.ujian.store');
    Route::get('guru/ujian/{ujian}/edit', [GuruUjianController::class, 'edit'])->name('guru.ujian.edit');
    Route::put('guru/ujian/{ujian}', [GuruUjianController::class, 'update'])->name('guru.ujian.update');
    Route::delete('guru/ujian/{ujian}', [GuruUjianController::class, 'destroy'])->name('guru.ujian.destroy');
    Route::post('guru/ujian/{ujian}/terbit', [GuruUjianController::class, 'terbit'])->name('guru.ujian.terbit');
    Route::post('guru/ujian/{ujian}/token', [GuruUjianController::class, 'generateToken'])->name('guru.ujian.token');
    Route::post('guru/ujian/{ujian}/token/toggle', [GuruUjianController::class, 'toggleToken'])->name('guru.ujian.token.toggle');
    Route::get('guru/ujian/{ujian}/soal', [GuruSoalController::class, 'index'])->name('guru.ujian.soal.index');
    Route::post('guru/ujian/{ujian}/soal', [GuruSoalController::class, 'store'])->name('guru.ujian.soal.store');
    Route::put('guru/ujian/{ujian}/soal/{soal}', [GuruSoalController::class, 'update'])->name('guru.ujian.soal.update');
    Route::delete('guru/ujian/{ujian}/soal/{soal}', [GuruSoalController::class, 'destroy'])->name('guru.ujian.soal.destroy');
    Route::post('guru/ujian/{ujian}/soal-ambil-bank', [GuruSoalController::class, 'ambilDariBank'])->name('guru.ujian.soal.ambil-bank');
    Route::post('guru/ujian/{ujian}/soal-generate-bank', [GuruSoalController::class, 'generateDariBank'])->name('guru.ujian.soal.generate-bank');
    Route::post('guru/ujian/{ujian}/soal/{soal}/simpan-bank', [GuruSoalController::class, 'simpanKeBank'])->name('guru.ujian.soal.simpan-bank');

    // Guru: Bank Soal
    Route::get('guru/bank-soal', [GuruBankSoalController::class, 'index'])->name('guru.bank-soal.index');
    Route::post('guru/bank-soal', [GuruBankSoalController::class, 'store'])->name('guru.bank-soal.store');
    Route::put('guru/bank-soal/{bankSoal}', [GuruBankSoalController::class, 'update'])->name('guru.bank-soal.update');
    Route::delete('guru/bank-soal/{bankSoal}', [GuruBankSoalController::class, 'destroy'])->name('guru.bank-soal.destroy');
    Route::get('guru/ujian/{ujian}/hasil', [GuruHasilUjianController::class, 'index'])->name('guru.ujian.hasil.index');
    Route::get('guru/ujian/{ujian}/hasil/{pengerjaan}', [GuruHasilUjianController::class, 'show'])->name('guru.ujian.hasil.show');
    Route::post('guru/ujian/{ujian}/hasil/{pengerjaan}/jawaban/{jawaban}/nilai', [GuruHasilUjianController::class, 'nilaiEsai'])->name('guru.ujian.hasil.nilai');

    // Siswa: Ujian (CBT)
    Route::get('ujian', [SiswaUjianController::class, 'index'])->name('siswa.ujian.index');
    Route::post('ujian/{ujian}/mulai', [SiswaUjianController::class, 'mulai'])->name('siswa.ujian.mulai');
    Route::get('ujian/{ujian}/kerjakan', [SiswaUjianController::class, 'kerjakan'])->name('siswa.ujian.kerjakan');
    Route::post('ujian/{ujian}/jawaban', [SiswaUjianController::class, 'simpanJawaban'])->name('siswa.ujian.jawaban');
    Route::post('ujian/{ujian}/submit', [SiswaUjianController::class, 'submit'])->name('siswa.ujian.submit');
    Route::post('ujian/{ujian}/lapor', [SiswaUjianController::class, 'lapor'])->name('siswa.ujian.lapor');
    Route::get('ujian/{ujian}/hasil', [SiswaUjianController::class, 'hasil'])->name('siswa.ujian.hasil');
});

Route::prefix('admin')->middleware(['auth', 'admin-only'])->name('admin.')->group(base_path('routes/admin.php'));
