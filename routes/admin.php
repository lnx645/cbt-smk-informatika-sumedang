<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JurusanController;
use Illuminate\Support\Facades\Route;

Route::get('', DashboardController::class)->name('index');

Route::get('jurusan', [JurusanController::class, 'index'])->name('jurusan.index');
Route::post('jurusan', [JurusanController::class, 'store'])->name('jurusan.store');
Route::put('jurusan/{jurusan}', [JurusanController::class, 'update'])->name('jurusan.update');
Route::delete('jurusan/{jurusan}', [JurusanController::class, 'destroy'])->name('jurusan.destroy');
