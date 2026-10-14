<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RuangUjian;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RuangController extends Controller
{
    /**
     * Daftar ruang ujian (data master, dikelola admin).
     */
    public function index(): Response
    {
        return Inertia::render('admin/Ruang/Index', [
            'ruangs' => RuangUjian::orderBy('kode')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:ruang_ujians,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'kapasitas' => ['required', 'integer', 'min:0'],
            'lokasi' => ['nullable', 'string', 'max:255'],
        ]);

        RuangUjian::create($data);
        Toast::success('Ruang ujian ditambahkan.');

        return redirect()->route('admin.ruang.index');
    }

    public function update(Request $request, RuangUjian $ruang): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:ruang_ujians,kode,'.$ruang->id],
            'nama' => ['required', 'string', 'max:100'],
            'kapasitas' => ['required', 'integer', 'min:0'],
            'lokasi' => ['nullable', 'string', 'max:255'],
        ]);

        $ruang->update($data);
        Toast::success('Ruang ujian diperbarui.');

        return redirect()->route('admin.ruang.index');
    }

    public function destroy(RuangUjian $ruang): RedirectResponse
    {
        $ruang->delete();
        Toast::success('Ruang ujian dihapus.');

        return redirect()->route('admin.ruang.index');
    }
}
