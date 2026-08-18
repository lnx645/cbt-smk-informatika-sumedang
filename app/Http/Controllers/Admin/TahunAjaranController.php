<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class TahunAjaranController extends Controller
{
    /**
     * Display a listing of the tahun ajaran.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $active = $request->query('active');

        $query = TahunAjaran::query();
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($active === '1') {
            $query->where('active', true);
        } elseif ($active === '0') {
            $query->where('active', false);
        }

        $tahunAjarans = $query->orderByDesc('active')
            ->orderBy('name')
            ->paginate(10)
            ->through(fn (TahunAjaran $tahunAjaran) => [
                'id' => $tahunAjaran->id,
                'name' => $tahunAjaran->name,
                'active' => $tahunAjaran->active,
            ]);

        return Inertia::render('admin/TahunAjaran/Index', [
            'tahunAjarans' => Inertia::merge($tahunAjarans),
            'filters' => [
                'search' => $search ?? '',
                'active' => $active ?? '',
            ],
        ]);
    }

    /**
     * Store a newly created tahun ajaran in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tahun_ajaran,name'],
            'active' => ['boolean'],
        ], [], [
            'name' => 'Tahun Ajaran',
            'active' => 'Aktif',
        ]);

        if ($data['active']) {
            TahunAjaran::where('active', true)->update(['active' => false]);
        }

        TahunAjaran::create($data);

        Cache::forget('tahun-ajaran-aktif');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tahun ajaran berhasil ditambahkan.',
        ]);

        return Redirect::route('admin.tahun-ajaran.index');
    }

    /**
     * Update the specified tahun ajaran in storage.
     */
    public function update(Request $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tahun_ajaran,name,'.$tahunAjaran->id],
            'active' => ['boolean'],
        ], [], [
            'name' => 'Tahun Ajaran',
            'active' => 'Aktif',
        ]);

        if ($data['active']) {
            TahunAjaran::where('active', true)->update(['active' => false]);
        }

        $tahunAjaran->update($data);

        Cache::forget('tahun-ajaran-aktif');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tahun ajaran berhasil diperbarui.',
        ]);

        return Redirect::route('admin.tahun-ajaran.index');
    }

    /**
     * Remove the specified tahun ajaran from storage.
     */
    public function destroy(TahunAjaran $tahunAjaran): RedirectResponse
    {
        $tahunAjaran->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tahun ajaran berhasil dihapus.',
        ]);

        return Redirect::route('admin.tahun-ajaran.index');
    }
}
