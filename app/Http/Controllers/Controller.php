<?php

namespace App\Http\Controllers;

use App\Models\User;
use Auth;
use Carbon\Carbon;
use Inertia\Inertia;

abstract class Controller
{   
    public ?User $user;
    


    public function __construct()
    {
        Inertia::share([
            "tanggal_sekarang"=>Carbon::now()->translatedFormat("D, d-M-Y"),
        ]);
        $this->user = Auth::guard('web')->user();
    }
}
