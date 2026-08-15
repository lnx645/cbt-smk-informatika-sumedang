<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JamPelajaranController;
use App\Http\Controllers\Admin\JurusanController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\MatpelController;
use App\Http\Controllers\Admin\PengajarController;
use App\Http\Controllers\Admin\TahunAjaranController;
use App\Http\Controllers\AturJadwalPengajarController;
use Illuminate\Support\Facades\Route;

Route::get('', DashboardController::class)->name('index');

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

Route::resource('jam-pelajaran', JamPelajaranController::class)->except(['show', 'create', 'edit']);

Route::get('pengajar/atur-jadwal/{guru_id}', [AturJadwalPengajarController::class, 'index'])
    ->name('pengajar.atur-jadwal');

Route::post('pengajar/atur-jadwal/{guru_id}', [AturJadwalPengajarController::class, 'store'])
    ->name('pengajar.atur-jadwal.store');

Route::put('pengajar/atur-jadwal/{guru_id}/{jadwal}', [AturJadwalPengajarController::class, 'update'])
    ->name('pengajar.atur-jadwal.update');

Route::delete('pengajar/atur-jadwal/{guru_id}/{jadwal}', [AturJadwalPengajarController::class, 'destroy'])
    ->name('pengajar.atur-jadwal.destroy');
