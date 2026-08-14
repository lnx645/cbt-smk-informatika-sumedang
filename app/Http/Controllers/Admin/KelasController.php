<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class KelasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kelas = Kelas::with(['jurusan', 'walikelas'])->get();

        $buildTree = function ($parentId) use ($kelas, &$buildTree) {
            return $kelas
                ->where('parent_id', $parentId)
                ->sortBy('nama')
                ->map(function ($item) use ($buildTree) {
                    $node = $item->toArray();
                    $node['children'] = $buildTree($item->id);

                    return $node;
                })
                ->values()
                ->all();
        };

        return inertia('admin/Kelas/Index', [
            'kelas_parent' => $buildTree(null),
            'kelas_list' => $kelas->map(fn (Kelas $k) => [
                'id' => $k->id,
                'nama' => $k->nama,
                'parent_id' => $k->parent_id,
            ]),
            'jurusans' => Jurusan::orderBy('name')->get(['id', 'name', 'kode']),
            'gurus' => Guru::orderBy('nama_lengkap')->get(['id', 'nama_lengkap']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateKelas($request);

        Kelas::create($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kelas berhasil ditambahkan.',
        ]);

        return Redirect::route('admin.kelas.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Kelas $kelas): RedirectResponse
    {
        $data = $this->validateKelas($request, $kelas);

        $kelas->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kelas berhasil diperbarui.',
        ]);

        return Redirect::route('admin.kelas.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Kelas $kelas): RedirectResponse
    {
        $kelas->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kelas berhasil dihapus.',
        ]);

        return Redirect::route('admin.kelas.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateKelas(Request $request, ?Kelas $kelas = null): array
    {
        $request->merge(collect(['jurusan_id', 'guru_id', 'parent_id'])
            ->mapWithKeys(function (string $key) use ($request) {
                $value = $request->input($key);

                if ($value === '' || $value === null) {
                    return [$key => null];
                }

                if (is_array($value) || is_object($value)) {
                    $value = is_array($value)
                        ? reset($value)
                        : ($value->value ?? null);
                }

                return [$key => $value];
            })
            ->all());

        $request->merge([
            'deskripsi' => $request->input('deskripsi') ?? '',
            'ruangan' => $request->input('ruangan') ?? '',
        ]);

        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'ruangan' => ['nullable', 'string', 'max:255'],
            'jurusan_id' => ['nullable', 'exists:jurusans,id'],
            'guru_id' => ['nullable', 'exists:gurus,id'],
            'parent_id' => ['nullable', 'exists:kelas,id'],
            'active' => ['boolean'],
        ], [], [
            'nama' => 'Nama Kelas',
            'deskripsi' => 'Deskripsi',
            'ruangan' => 'Ruangan',
            'jurusan_id' => 'Jurusan',
            'guru_id' => 'Wali Kelas',
            'parent_id' => 'Kelas Induk',
            'active' => 'Aktif',
        ]);
    }
}
