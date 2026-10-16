<?php

use Illuminate\Support\Facades\Route;
use Modules\CBT\Http\Controllers\CBTController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cbts', CBTController::class)->names('cbt');
});
