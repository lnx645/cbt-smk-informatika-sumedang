<?php

use App\Http\Controllers\Module\Learning\LearningModuleController;
use App\Http\Controllers\Module\Learning\MataPelajaranController;
use Illuminate\Support\Facades\Route;

Route::get('/', LearningModuleController::class);
Route::get('mata-pelajaran', MataPelajaranController::class)->name('module.pembelajaran.mata-pelajaran');
