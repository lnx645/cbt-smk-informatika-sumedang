<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(): Response
    {
        $groups = Group::withCount('kelas')
            ->with(['kelas:id,nama', 'matpel:id,name', 'guru:id,nama_lengkap'])
            ->orderBy('nama')
            ->paginate(20);

        // Matpel + pengampu (guru yang ngajar matpel tsb)
        $matpelList = Matpel::orderBy('name')->get(['id', 'name']);
        $pengampuList = GuruKelas::with(['guru:id,nama_lengkap', 'matpel:id,name'])
            ->get()
            ->groupBy('matpel_id')
            ->map(fn ($items, $matpelId) => [
                'matpel_id' => $matpelId,
                'matpel_nama' => $items->first()->matpel?->name ?? '-',
                'gurus' => $items->unique('guru_id')->map(fn ($i) => [
                    'id' => $i->guru_id,
                    'nama' => $i->guru?->nama_lengkap ?? '-',
                    'kelas' => GuruKelas::where('guru_id', $i->guru_id)
                        ->where('matpel_id', $matpelId)
                        ->with('kelas:id,nama')
                        ->get()
                        ->map(fn ($gk) => ['id' => $gk->kelas_id, 'nama' => $gk->kelas?->nama ?? '-'])
                        ->values(),
                ])->values(),
            ])
            ->values();

        return Inertia::render('panitia/Group/Index', [
            'groups' => $groups,
            'kelasList' => Kelas::orderBy('nama')->get(['id', 'nama']),
            'matpelList' => $matpelList,
            'pengampuList' => $pengampuList,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'matpel_id' => ['nullable', 'exists:matpels,id'],
            'guru_id' => ['nullable', 'exists:gurus,id'],
                        'kelas_ids' => ['required', 'array', 'min:1'],
            'kelas_ids.*' => ['exists:kelas,id'],
        ]);

        $group = Group::create([
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'matpel_id' => $data['matpel_id'] ?? null,
            'guru_id' => $data['guru_id'] ?? null,
                    ]);
        $group->kelas()->sync($data['kelas_ids']);

        return back()->with('success', 'Group berhasil dibuat.');
    }

    public function update(Request $request, Group $group): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'matpel_id' => ['nullable', 'exists:matpels,id'],
            'guru_id' => ['nullable', 'exists:gurus,id'],
            'kelas_ids' => ['required', 'array', 'min:1'],
            'kelas_ids.*' => ['exists:kelas,id'],
        ]);

        $group->update([
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'matpel_id' => $data['matpel_id'] ?? null,
            'guru_id' => $data['guru_id'] ?? null,
                    ]);
        $group->kelas()->sync($data['kelas_ids']);

        return back()->with('success', 'Group berhasil diupdate.');
    }

    public function destroy(Group $group): RedirectResponse
    {
        $group->delete();

        return back()->with('success', 'Group berhasil dihapus.');
    }
}
