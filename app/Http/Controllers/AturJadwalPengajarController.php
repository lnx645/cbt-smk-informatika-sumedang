<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Support\Toast;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class AturJadwalPengajarController extends Controller
{
    /**
     * Show the teaching schedule page for the given guru.
     */
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

        $jadwalList = JadwalPelajaran::with(['matpel', 'kelas'])
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
                    'jam_mulai' => Carbon::parse($j->jam_mulai)->format('H:i'),
                    'jam_selesai' => Carbon::parse($j->jam_selesai)->format('H:i'),
                    'ruangan' => $j->kelas->ruangan ?? '',
                    'color' => $this->colorForMatpel($j->matpel->name),
                ];
            })
            ->all();

        $matpelOptions = Matpel::pluck('name', 'id')->all();
        $kelasOptions = $this->buildLeafKelasOptions();

        return Inertia::render('admin/AturJadwal/Index', [
            'guru' => $guruData,
            'jadwal' => $jadwalList,
            'matpelOptions' => $matpelOptions,
            'kelasOptions' => $kelasOptions,
        ]);
    }

    /**
     * Build id => full hierarchy path for leaf classes only.
     */
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

    /**
     * Store a new jadwal entry for the given guru.
     */
    public function store(Request $request, string $guru_id): RedirectResponse
    {
        Guru::findOrFail($guru_id);

        $data = $this->validateJadwal($request);

        // Prevent duplicate: same guru + hari + matpel
        if ($this->duplicateGuruMatpel($guru_id, $data['hari'], $data['matpel_id'])) {
            Toast::error('Guru sudah mengajar mata pelajaran ini pada hari yang sama.');

            return Redirect::back();
        }

        // Prevent duplicate: same kelas + matpel
        if ($this->duplicateKelasMatpel($data['kelas_id'], $data['matpel_id'])) {
            Toast::error('Kelas sudah memiliki jadwal untuk mata pelajaran ini.');

            return Redirect::back();
        }

        // Prevent schedule clash: same kelas + hari + overlapping time (other teacher)
        if (($clash = $this->timeClashKelas($data['kelas_id'], $data['hari'], $data['jam_mulai'], $data['jam_selesai'], $guru_id))) {
            Toast::error(sprintf(
                'Jadwal bentrok: kelas %s sudah diajar oleh guru %s untuk %s pada %s, %s - %s.',
                $clash->kelas->nama ?? $clash->kelas_id,
                $clash->guru->nama_lengkap ?? $clash->guru_id,
                $clash->matpel->name ?? $clash->matpel_id,
                $clash->hari,
                $clash->jam_mulai,
                $clash->jam_selesai,
            ));

            return Redirect::back();
        }

        // Prevent schedule clash: same guru + hari + overlapping time
        if (($clash = $this->timeClashGuru($guru_id, $data['hari'], $data['jam_mulai'], $data['jam_selesai']))) {
            Toast::error(sprintf(
                'Jadwal bentrok: guru ini sudah mengajar kelas %s untuk %s pada %s, %s - %s.',
                $clash->kelas->nama ?? $clash->kelas_id,
                $clash->matpel->name ?? $clash->matpel_id,
                $clash->hari,
                $clash->jam_mulai,
                $clash->jam_selesai,
            ));

            return Redirect::back();
        }

        JadwalPelajaran::create([
            'guru_id' => $guru_id,
            'matpel_id' => $data['matpel_id'],
            'kelas_id' => $data['kelas_id'],
            'hari' => $data['hari'],
            'jam_mulai' => $data['jam_mulai'],
            'jam_selesai' => $data['jam_selesai'],
        ]);

        Toast::success('Jadwal berhasil ditambahkan.');

        return Redirect::back();
    }

    /**
     * Update an existing jadwal entry.
     */
    public function update(Request $request, string $guru_id, JadwalPelajaran $jadwal): RedirectResponse
    {
        $jadwal->load('guru');
        if ((string) $jadwal->guru_id !== $guru_id) {
            abort(404);
        }

        $data = $this->validateJadwal($request, $jadwal->id);

        // Prevent duplicate: same guru + hari + matpel (excluding self)
        if ($this->duplicateGuruMatpel($guru_id, $data['hari'], $data['matpel_id'], $jadwal->id)) {
            Toast::error('Guru sudah mengajar mata pelajaran ini pada hari yang sama.');

            return Redirect::back();
        }

        // Prevent duplicate: same kelas + matpel (excluding self)
        if ($this->duplicateKelasMatpel($data['kelas_id'], $data['matpel_id'], $jadwal->id)) {
            Toast::error('Kelas sudah memiliki jadwal untuk mata pelajaran ini.');

            return Redirect::back();
        }

        // Prevent schedule clash: same kelas + hari + overlapping time (other teacher)
        if (($clash = $this->timeClashKelas($data['kelas_id'], $data['hari'], $data['jam_mulai'], $data['jam_selesai'], $guru_id, $jadwal->id))) {
            Toast::error(sprintf(
                'Jadwal bentrok: kelas %s sudah diajar oleh guru %s untuk %s pada %s, %s - %s.',
                $clash->kelas->nama ?? $clash->kelas_id,
                $clash->guru->nama_lengkap ?? $clash->guru_id,
                $clash->matpel->name ?? $clash->matpel_id,
                $clash->hari,
                $clash->jam_mulai,
                $clash->jam_selesai,
            ));

            return Redirect::back();
        }

        // Prevent schedule clash: same guru + hari + overlapping time
        if (($clash = $this->timeClashGuru($guru_id, $data['hari'], $data['jam_mulai'], $data['jam_selesai'], $jadwal->id))) {
            Toast::error(sprintf(
                'Jadwal bentrok: guru ini sudah mengajar kelas %s untuk %s pada %s, %s - %s.',
                $clash->kelas->nama ?? $clash->kelas_id,
                $clash->matpel->name ?? $clash->matpel_id,
                $clash->hari,
                $clash->jam_mulai,
                $clash->jam_selesai,
            ));

            return Redirect::back();
        }

        $jadwal->update([
            'matpel_id' => $data['matpel_id'],
            'kelas_id' => $data['kelas_id'],
            'hari' => $data['hari'],
            'jam_mulai' => $data['jam_mulai'],
            'jam_selesai' => $data['jam_selesai'],
        ]);

        Toast::success('Jadwal berhasil diperbarui.');

        return Redirect::back();
    }

    /**
     * Delete a jadwal entry.
     */
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

    /**
     * Shared validation for store and update.
     */
    private function validateJadwal(Request $request, ?int $excludeId = null): array
    {
        return $request->validate([
            'matpel_id' => ['required', 'exists:matpels,id'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'hari' => ['required', 'string'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
        ]);
    }

    /**
     * Check if the same guru already teaches the same matpel on the same hari.
     */
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

    /**
     * Check if the same kelas already has the same matpel scheduled.
     */
    private function duplicateKelasMatpel(int $kelas_id, int $matpel_id, ?int $excludeId = null): bool
    {
        $q = JadwalPelajaran::where('kelas_id', $kelas_id)
            ->where('matpel_id', $matpel_id);

        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return $q->exists();
    }

    /**
     * Check for overlapping time slots for a given kelas on a given hari
     * where a *different* guru is already teaching.
     */
    private function timeClashKelas(int $kelas_id, string $hari, string $jamMulai, string $jamSelesai, string $guru_id, ?int $excludeId = null): ?JadwalPelajaran
    {
        $q = JadwalPelajaran::with(['guru', 'matpel', 'kelas'])
            ->where('kelas_id', $kelas_id)
            ->where('hari', $hari)
            ->where('guru_id', '!=', $guru_id)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai);

        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return $q->first();
    }

    /**
     * Check for overlapping time slots for the same guru.
     */
    private function timeClashGuru(string $guru_id, string $hari, string $jamMulai, string $jamSelesai, ?int $excludeId = null): ?JadwalPelajaran
    {
        $q = JadwalPelajaran::with(['guru', 'matpel', 'kelas'])
            ->where('guru_id', $guru_id)
            ->where('hari', $hari)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai);

        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return $q->first();
    }

    /**
     * Stable color per subject name for the legend.
     */
    private function colorForMatpel(string $matpel): string
    {
        $colors = ['primary', 'info', 'success', 'warning', 'danger', 'secondary'];
        $hash = crc32($matpel);

        return $colors[$hash % count($colors)];
    }
}
