<?php

use App\Http\Controllers\Admin\AkunAdminController;
use App\Http\Controllers\Admin\AkunGuruController;
use App\Http\Controllers\Admin\AkunSiswaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GuruKelasController;
use App\Http\Controllers\Admin\JurusanController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\MatpelController;
use App\Http\Controllers\Admin\NaikKelasController;
use App\Http\Controllers\Admin\PengajarController;
use App\Http\Controllers\Admin\ProfilController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\SiswaKelasController;
use App\Http\Controllers\Admin\TahunAjaranController;
use Illuminate\Support\Facades\Route;

Route::get('', DashboardController::class)->name('index');
Route::get('profil', [ProfilController::class, 'index'])->name('profil.index');
Route::put('profil', [ProfilController::class, 'update'])->name('profil.update');
Route::get('akun-admin', [AkunAdminController::class, 'index'])->name('akun-admin.index');
Route::post('akun-admin', [AkunAdminController::class, 'store'])->name('akun-admin.store');
Route::put('akun-admin/{user}', [AkunAdminController::class, 'update'])->name('akun-admin.update');
Route::delete('akun-admin/{user}', [AkunAdminController::class, 'destroy'])->name('akun-admin.destroy');
Route::get('kelas', [KelasController::class, 'index'])->name('kelas.index');
Route::post('kelas', [KelasController::class, 'store'])->name('kelas.store');
Route::put('kelas/{kelas}', [KelasController::class, 'update'])->name('kelas.update');
Route::delete('kelas/{kelas}', [KelasController::class, 'destroy'])->name('kelas.destroy');
Route::get('tahun-ajaran', [TahunAjaranController::class, 'index'])->name('tahun-ajaran.index');
Route::post('tahun-ajaran', [TahunAjaranController::class, 'store'])->name('tahun-ajaran.store');
Route::put('tahun-ajaran/{tahunAjaran}', [TahunAjaranController::class, 'update'])->name('tahun-ajaran.update');
Route::delete('tahun-ajaran/{tahunAjaran}', [TahunAjaranController::class, 'destroy'])->name('tahun-ajaran.destroy');
Route::get('jurusan', [JurusanController::class, 'index'])->name('jurusan.index');
Route::post('jurusan', [JurusanController::class, 'store'])->name('jurusan.store');
Route::put('jurusan/{jurusan}', [JurusanController::class, 'update'])->name('jurusan.update');
Route::delete('jurusan/{jurusan}', [JurusanController::class, 'destroy'])->name('jurusan.destroy');
Route::get('matpel', [MatpelController::class, 'index'])->name('matpel.index');
Route::post('matpel', [MatpelController::class, 'store'])->name('matpel.store');
Route::put('matpel/{matpel}', [MatpelController::class, 'update'])->name('matpel.update');
Route::delete('matpel/{matpel}', [MatpelController::class, 'destroy'])->name('matpel.destroy');
Route::resource('pengajar', PengajarController::class)->except(['create', 'edit', 'show']);
Route::get('pengajar/{guru}/akun', [AkunGuruController::class, 'show'])->name('pengajar.akun');
Route::post('pengajar/{guru}/akun', [AkunGuruController::class, 'store'])->name('pengajar.akun.store');
Route::put('pengajar/{guru}/akun', [AkunGuruController::class, 'update'])->name('pengajar.akun.update');
Route::delete('pengajar/{guru}/akun', [AkunGuruController::class, 'destroy'])->name('pengajar.akun.destroy');
Route::get('pengajar/{guru}/penugasan', [GuruKelasController::class, 'index'])->name('pengajar.guru-kelas');
Route::post('pengajar/{guru}/penugasan', [GuruKelasController::class, 'store'])->name('pengajar.guru-kelas.store');
Route::put('pengajar/{guru}/penugasan/{guruKelas}', [GuruKelasController::class, 'update'])->name('pengajar.guru-kelas.update');
Route::delete('pengajar/{guru}/penugasan/{guruKelas}', [GuruKelasController::class, 'destroy'])->name('pengajar.guru-kelas.destroy');
Route::resource('siswa', SiswaController::class)->except(['create', 'edit', 'show']);
Route::get('siswa/{siswa}/akun', [AkunSiswaController::class, 'show'])->name('siswa.akun');
Route::post('siswa/{siswa}/akun', [AkunSiswaController::class, 'store'])->name('siswa.akun.store');
Route::put('siswa/{siswa}/akun', [AkunSiswaController::class, 'update'])->name('siswa.akun.update');
Route::delete('siswa/{siswa}/akun', [AkunSiswaController::class, 'destroy'])->name('siswa.akun.destroy');
Route::get('siswa/{siswa}/kelas', [SiswaKelasController::class, 'index'])->name('siswa.kelas');
Route::post('siswa/{siswa}/kelas', [SiswaKelasController::class, 'store'])->name('siswa.kelas.store');
Route::put('siswa/{siswa}/kelas/{siswaKelas}', [SiswaKelasController::class, 'update'])->name('siswa.kelas.update');
Route::delete('siswa/{siswa}/kelas/{siswaKelas}', [SiswaKelasController::class, 'destroy'])->name('siswa.kelas.destroy');
Route::get('naik-kelas', [NaikKelasController::class, 'index'])->name('naik-kelas.index');
Route::post('naik-kelas/preview', [NaikKelasController::class, 'preview'])->name('naik-kelas.preview');
Route::post('naik-kelas', [NaikKelasController::class, 'execute'])->name('naik-kelas.execute');

// -------------------------------------------------------------------
// Penilaian (Assessment) Feature
// -------------------------------------------------------------------
use App\Http\Controllers\Admin\DetailPenilaianController;
use App\Http\Controllers\Admin\PenilaianController;

// Master Penilaian CRUD (admin only)
Route::get('penilaian', [PenilaianController::class, 'index'])
    ->name('penilaian.index');
Route::get('penilaian/create', [PenilaianController::class, 'create'])
    ->name('penilaian.create');
Route::post('penilaian', [PenilaianController::class, 'store'])
    ->name('penilaian.store');
Route::get('penilaian/{penilaian}', [PenilaianController::class, 'show'])
    ->name('penilaian.show');
Route::get('penilaian/{penilaian}/edit', [PenilaianController::class, 'edit'])
    ->name('penilaian.edit');
Route::put('penilaian/{penilaian}', [PenilaianController::class, 'update'])
    ->name('penilaian.update');
Route::delete('penilaian/{penilaian}', [PenilaianController::class, 'destroy'])
    ->name('penilaian.destroy');

// Detail nilai per siswa (admin only)
Route::get('penilaian/{penilaian}/penugasan', [DetailPenilaianController::class, 'filterSiswa'])
    ->name('penilaian.penugasan.filter');
Route::get('penilaian/{penilaian}/penugasan/{guruKelas}/siswa/{siswa}', [DetailPenilaianController::class, 'detail'])
    ->name('penilaian.penugasan.detail');
Route::post('penilaian/{penilaian}/penugasan/{guruKelas}/siswa/{siswa}', [DetailPenilaianController::class, 'storeNilai'])
    ->name('penilaian.penugasan.store');
