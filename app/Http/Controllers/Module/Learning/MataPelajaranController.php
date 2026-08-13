<?php

namespace App\Http\Controllers\Module\Learning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MataPelajaranController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return inertia("modul/learning/mata-pelajaran");
    }
}
