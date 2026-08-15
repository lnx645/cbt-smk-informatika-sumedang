<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MataPelajaranGuruController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function index(Request $request)
    {
        return inertia('guru/Matpel');
    }

    public function detail(Request $erquest,string $kelas,string $matpel){
        return "OKE";
    }
}
