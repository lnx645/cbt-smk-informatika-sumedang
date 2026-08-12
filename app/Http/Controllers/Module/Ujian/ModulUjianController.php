<?php

namespace App\Http\Controllers\Module\Ujian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModulUjianController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return inertia("modul/ujian/index");
    }
}
