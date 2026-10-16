<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Login khusus ujian CBT — terpisah dari login utama.
 * Siswa masuk dengan NISN + password ujian (auto-generate, tercetak di kartu),
 * langsung ke daftar ujian tanpa dashboard.
 */
class UjianLoginController extends Controller
{
    /**
     * Tampilkan halaman login ujian.
     */
    public function create(): Response
    {
        return Inertia::render('ujian/Login');
    }

    /**
     * Autentikasi siswa untuk ujian.
     */
    public function store(Request $request): RedirectResponse
    {

        $data = $request->validate([
            'nisn' => ['required', 'string', 'size:10'],
            'password' => ['required', 'string'],
        ]);


        $siswa = Siswa::where('nisn', $data['nisn'])->first();

        $valid = false;
        if ($siswa && $siswa->password_ujian) {
            try {
                $tersimpan = Crypt::decryptString($siswa->password_ujian);
                $valid = hash_equals($tersimpan, $data['password']);
            } catch (\Exception $e) {
                $valid = false;
            }
        }

        if (! $valid) {
            throw ValidationException::withMessages([
                'nisn' => 'NISN atau password ujian salah.',
            ]);
        }


        // Simpan NISN di session saja (tanpa Auth::login biar terpisah dari dashboard)
        $request->session()->regenerate();
        $request->session()->put('ujian_nisn', $siswa->nisn);
        $request->session()->put('mode_ujian', true);

        Toast::success('Selamat datang, '.$siswa->nama_lengkap.'!');

        return redirect()->route('ujian.daftar');
    }

    /**
     * Logout dari mode ujian.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('mode_ujian');
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('ujian.login');
    }
}
