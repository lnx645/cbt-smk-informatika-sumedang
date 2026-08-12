<?php

use App\Http\Controllers\Module\Learning\LearningModuleController;
use Illuminate\Support\Facades\Route;
Route::get("/",LearningModuleController::class);