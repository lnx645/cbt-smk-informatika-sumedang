<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\RuangUjian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RuangController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('panitia/Ruang/Index', [
            'ruangs' => RuangUjian::orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:50', 'unique:cbt_ruang_ujians,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'lokasi' => ['nullable', 'string', 'max:255'],
        ]);

        RuangUjian::create($data);

        return back()->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function update(Request $request, RuangUjian $ruang): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:50', 'unique:cbt_ruang_ujians,kode,'.$ruang->id],
            'nama' => ['required', 'string', 'max:255'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'lokasi' => ['nullable', 'string', 'max:255'],
        ]);

        $ruang->update($data);

        return back()->with('success', 'Ruangan berhasil diperbarui.');
    }

    public function destroy(RuangUjian $ruang): RedirectResponse
    {
        $ruang->delete();

        return back()->with('success', 'Ruangan berhasil dihapus.');
    }
}
