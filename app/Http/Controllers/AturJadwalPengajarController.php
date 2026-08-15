<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class AturJadwalPengajarController extends Controller
{
    public function index(Request $request, string $guru_id): Response
    {
        $guru = Guru::with('walikelas')->findOrFail($guru_id);

        $guruData = [
            'id' => $guru->id,
            'nama' => $guru->nama_lengkap,
            'nip' => $guru->nip,
            'jabatan' => 'Guru',
            'walikelas' => $guru->walikelas->pluck('nama')->all(),
            'foto' => $guru->foto_profil,
        ];

        $hariOrder = "CASE
            WHEN hari = 'Minggu' THEN 0
            WHEN hari = 'Senin' THEN 1
            WHEN hari = 'Selasa' THEN 2
            WHEN hari = 'Rabu' THEN 3
            WHEN hari = 'Kamis' THEN 4
            WHEN hari = 'Jumat' THEN 5
            WHEN hari = 'Sabtu' THEN 6
            ELSE 7
        END";

        $jadwalList = JadwalPelajaran::with(['matpel', 'kelas', 'jamPelajaran'])
            ->where('guru_id', $guru->id)
            ->orderByRaw($hariOrder)
            ->orderBy('jam_mulai')
            ->get()
            ->map(function (JadwalPelajaran $j) {
                return [
                    'id' => $j->id,
                    'hari' => $j->hari,
                    'matpel' => $j->matpel->name,
                    'matpel_id' => $j->matpel_id,
                    'kelas' => $j->kelas->nama,
                    'kelas_id' => $j->kelas_id,
                    'jam_mulai' => $j->jamPelajaran->mulai ?? '-',
                    'jam_selesai' => $j->jamPelajaran->selesai ?? '-',
                    'ruangan' => $j->kelas->ruangan ?? '',
                    'color' => $this->colorForMatpel($j->matpel->name),
                ];
            })
            ->all();

        $matpelOptions = Matpel::pluck('name', 'id')->all();
        $kelasOptions = $this->buildLeafKelasOptions();
        $jpSlots = $this->getJpSlots();

        return Inertia::render('admin/AturJadwal/Index', [
            'guru' => $guruData,
            'jadwal' => $jadwalList,
            'matpelOptions' => $matpelOptions,
            'kelasOptions' => $kelasOptions,
            'jpSlots' => $jpSlots,
        ]);
    }

    private function getJpSlots(): array
    {
        return JamPelajaran::where('is_break', false)
            ->orderBy('urutan')
            ->get()
            ->mapWithKeys(fn (JamPelajaran $jp) => [$jp->id => [
                'mulai' => $jp->mulai,
                'selesai' => $jp->selesai,
                'label' => $jp->label,
            ]])
            ->all();
    }

    private function buildLeafKelasOptions(): array
    {
        $all = Kelas::all(['id', 'nama', 'parent_id'])->keyBy('id');

        $leaves = $all->filter(fn ($k) => ! $all->contains('parent_id', $k->id));

        $options = [];
        foreach ($leaves as $leaf) {
            $path = [];
            $node = $leaf;
            while ($node) {
                $path[] = $node->nama;
                $parentId = $node->parent_id;
                $node = $parentId ? $all[$parentId] ?? null : null;
            }
            $path = array_reverse($path);
            $options[$leaf->id] = implode(' / ', $path);
        }

        return $options;
    }

    public function store(Request $request, string $guru_id): RedirectResponse
    {
        Guru::findOrFail($guru_id);

        $data = $this->validateJadwal($request);

        if ($this->duplicateGuruMatpel($guru_id, $data['hari'], $data['matpel_id'])) {
            Toast::error('Guru sudah mengajar mata pelajaran ini pada hari yang sama.');

            return Redirect::back();
        }

        if ($this->duplicateKelasMatpel($data['kelas_id'], $data['matpel_id'])) {
            Toast::error('Kelas sudah memiliki jadwal untuk mata pelajaran ini.');

            return Redirect::back();
        }

        $jpSlot = JamPelajaran::findOrFail($data['jp']);

        JadwalPelajaran::create([
            'guru_id' => $guru_id,
            'matpel_id' => $data['matpel_id'],
            'kelas_id' => $data['kelas_id'],
            'jam_pelajaran_id' => $jpSlot->id,
            'hari' => $data['hari'],
            'jam_mulai' => $jpSlot->jam_mulai,
            'jam_selesai' => $jpSlot->jam_selesai,
        ]);

        Toast::success('Jadwal berhasil ditambahkan.');

        return Redirect::back();
    }

    public function update(Request $request, string $guru_id, JadwalPelajaran $jadwal): RedirectResponse
    {
        $jadwal->load('guru');
        if ((string) $jadwal->guru_id !== $guru_id) {
            abort(404);
        }

        $data = $this->validateJadwal($request, $jadwal->id);

        if ($this->duplicateGuruMatpel($guru_id, $data['hari'], $data['matpel_id'], $jadwal->id)) {
            Toast::error('Guru sudah mengajar mata pelajaran ini pada hari yang sama.');

            return Redirect::back();
        }

        if ($this->duplicateKelasMatpel($data['kelas_id'], $data['matpel_id'], $jadwal->id)) {
            Toast::error('Kelas sudah memiliki jadwal untuk mata pelajaran ini.');

            return Redirect::back();
        }

        $jpSlot = JamPelajaran::findOrFail($data['jp']);

        $jadwal->update([
            'matpel_id' => $data['matpel_id'],
            'kelas_id' => $data['kelas_id'],
            'jam_pelajaran_id' => $jpSlot->id,
            'hari' => $data['hari'],
            'jam_mulai' => $jpSlot->jam_mulai,
            'jam_selesai' => $jpSlot->jam_selesai,
        ]);

        Toast::success('Jadwal berhasil diperbarui.');

        return Redirect::back();
    }

    public function destroy(string $guru_id, JadwalPelajaran $jadwal): RedirectResponse
    {
        $jadwal->load('guru');
        if ((string) $jadwal->guru_id !== $guru_id) {
            abort(404);
        }

        $jadwal->delete();

        Toast::success('Jadwal berhasil dihapus.');

        return Redirect::back();
    }

    private function validateJadwal(Request $request, ?int $excludeId = null): array
    {
        return $request->validate([
            'matpel_id' => ['required', 'exists:matpels,id'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'hari' => ['required', 'string'],
            'jp' => ['required', 'integer'],
        ]);
    }

    private function duplicateGuruMatpel(string $guru_id, string $hari, int $matpel_id, ?int $excludeId = null): bool
    {
        $q = JadwalPelajaran::where('guru_id', $guru_id)
            ->where('hari', $hari)
            ->where('matpel_id', $matpel_id);

        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return $q->exists();
    }

    private function duplicateKelasMatpel(int $kelas_id, int $matpel_id, ?int $excludeId = null): bool
    {
        $q = JadwalPelajaran::where('kelas_id', $kelas_id)
            ->where('matpel_id', $matpel_id);

        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return $q->exists();
    }

    private function colorForMatpel(string $matpel): string
    {
        $colors = ['primary', 'info', 'success', 'warning', 'danger', 'secondary'];
        $hash = crc32($matpel);

        return $colors[$hash % count($colors)];
    }
}
