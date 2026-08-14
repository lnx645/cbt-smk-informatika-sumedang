<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Matpel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class MatpelController extends Controller
{
    /**
     * Display a listing of the mata pelajaran.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $hasDescription = $request->query('has_description');

        $query = Matpel::query();
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($hasDescription === '1') {
            $query->where('description', '<>', '');
        } elseif ($hasDescription === '0') {
            $query->where('description', '');
        }

        $matpels = $query->orderBy('name')
            ->paginate(10)
            ->through(fn (Matpel $matpel) => [
                'id' => $matpel->id,
                'name' => $matpel->name,
                'description' => $matpel->description,
            ]);

        return Inertia::render('admin/Matpel/Index', [
            'matpels' => Inertia::merge($matpels),
            'filters' => [
                'search' => $search ?? '',
                'has_description' => $hasDescription ?? '',
            ],
        ]);
    }

    /**
     * Store a newly created mata pelajaran in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ], [], [
            'name' => 'Nama Mata Pelajaran',
            'description' => 'Deskripsi',
        ]);

        $data['description'] = $data['description'] ?? '';

        Matpel::create($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Mata pelajaran berhasil ditambahkan.',
        ]);

        return Redirect::route('admin.matpel.index');
    }

    /**
     * Update the specified mata pelajaran in storage.
     */
    public function update(Request $request, Matpel $matpel): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ], [], [
            'name' => 'Nama Mata Pelajaran',
            'description' => 'Deskripsi',
        ]);

        $data['description'] = $data['description'] ?? '';

        $matpel->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Mata pelajaran berhasil diperbarui.',
        ]);

        return Redirect::route('admin.matpel.index');
    }

    /**
     * Remove the specified mata pelajaran from storage.
     */
    public function destroy(Matpel $matpel): RedirectResponse
    {
        $matpel->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Mata pelajaran berhasil dihapus.',
        ]);

        return Redirect::route('admin.matpel.index');
    }
}
