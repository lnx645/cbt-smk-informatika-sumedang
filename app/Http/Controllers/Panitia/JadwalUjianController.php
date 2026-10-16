<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\JadwalUjian;
use App\Models\JadwalUjianSesi;
use App\Models\Kelas;
use App\Models\KelompokBankSoal;
use App\Models\RuangUjian;
use App\Models\SiswaKelas;
use App\Services\JadwalUjianSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JadwalUjianController extends Controller
{
    public function index(Request $request): Response
    {
        $jadwals = JadwalUjian::with(['event', 'banksoal', 'kelas'])
            ->when($request->tanggal, fn ($q) => $q->whereDate('tanggal', $request->tanggal))
            ->orderBy('tanggal')
            ->paginate(20);

        $jadwals->getCollection()->transform(fn ($j) => [
            'id' => $j->id,
            'nama' => $j->nama,
            'event' => $j->event?->nama ?? '-',
            'tanggal' => $j->tanggal->format('d M Y'),
            'mode' => $j->mode,
            'status' => $j->status,
            'kelas_count' => $j->kelas->count(),
            'sesi_count' => $j->sesi()->count(),
        ]);

        return Inertia::render('panitia/JadwalUjian/Index', [
            'jadwals' => $jadwals,
            'filters' => $request->only(['tanggal']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('panitia/JadwalUjian/Form', [
            'events' => Event::orderByDesc('created_at')->get(['id', 'nama']),
            'kelasList' => Kelas::orderBy('nama')->get(['id', 'nama']),
            'banksoalList' => KelompokBankSoal::orderBy('nama')->get()->map(fn ($b) => [
                'id' => $b->id,
                'nama' => $b->nama,
                'jumlah_soal' => $b->soals()->count(),
            ]),
            'ruangList' => RuangUjian::orderBy('nama')->get(['id', 'nama', 'kapasitas']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:cbt_events,id'],
            'kelompok_bank_soal_id' => ['required', 'exists:cbt_kelompok_bank_soals,id'],
            'nama' => ['required', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'durasi_menit' => ['required', 'integer', 'min:1'],
            'mode' => ['required', 'in:luring,daring'],
            'kelas_ids' => ['required', 'array', 'min:1'],
            'kelas_ids.*' => ['exists:kelas,id'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'ruang_id' => ['nullable', 'exists:cbt_ruang_ujians,id'],
            'acak_soal' => ['boolean'],
            'acak_opsi' => ['boolean'],
            'aktifkan_token' => ['boolean'],
            'tampilkan_nilai' => ['boolean'],
        ]);

        // Luring wajib pilih ruangan
        if ($data['mode'] === 'luring' && empty($data['ruang_id'])) {
            return back()->withErrors(['ruang_id' => 'Ruangan wajib dipilih untuk mode luring.'])->withInput();
        }

        $jadwal = JadwalUjian::create([
            'event_id' => $data['event_id'],
            'kelompok_bank_soal_id' => $data['kelompok_bank_soal_id'],
            'nama' => $data['nama'],
            'tanggal' => $data['tanggal'],
            'durasi_menit' => $data['durasi_menit'],
            'mode' => $data['mode'],
            'status' => 'draft',
            'acak_soal' => $data['acak_soal'] ?? true,
            'acak_opsi' => $data['acak_opsi'] ?? true,
            'aktifkan_token' => $data['aktifkan_token'] ?? true,
            'tampilkan_nilai' => $data['tampilkan_nilai'] ?? true,
        ]);

        $jadwal->kelas()->sync($data['kelas_ids']);

        // Hitung sesi otomatis
        $this->buatSesiOtomatis($jadwal, $data['jam_mulai'], $data['ruang_id'] ?? null);

        return redirect()->route('app.panitia.jadwal-ujian.show', $jadwal)
            ->with('success', 'Jadwal berhasil dibuat dengan '.$jadwal->sesi()->count().' sesi.');
    }

    public function show(JadwalUjian $jadwalUjian): Response
    {
        $jadwalUjian->load(['event', 'banksoal', 'kelas', 'sesi.ruang']);

        return Inertia::render('panitia/JadwalUjian/Detail', [
            'jadwal' => [
                'id' => $jadwalUjian->id,
                'nama' => $jadwalUjian->nama,
                'event' => $jadwalUjian->event?->nama ?? '-',
                'banksoal' => $jadwalUjian->banksoal?->nama ?? '-',
                'tanggal' => $jadwalUjian->tanggal->format('d M Y'),
                'durasi' => $jadwalUjian->durasi_menit,
                'mode' => $jadwalUjian->mode,
                'status' => $jadwalUjian->status,
                'kelas' => $jadwalUjian->kelas->pluck('nama')->join(', '),
                'sesi' => $jadwalUjian->sesi->map(fn ($s) => [
                    'id' => $s->id,
                    'nama' => $s->nama,
                    'jam_mulai' => substr($s->jam_mulai, 0, 5),
                    'jam_selesai' => substr($s->jam_selesai, 0, 5),
                    'ruang' => $s->ruang?->nama ?? '-',
                    'kapasitas' => $s->kapasitas,
                    'token' => $s->token,
                ]),
            ],
        ]);
    }

    public function terbitkan(JadwalUjian $jadwalUjian): RedirectResponse
    {
        $hasil = app(JadwalUjianSyncService::class)->sync($jadwalUjian);

        return back()->with('success', 'Diterbitkan! '.count($hasil['ujian_ids']).' ujian, '.$hasil['jumlah_soal'].' soal, '.$hasil['jumlah_sesi'].' sesi. Siswa sudah bisa mengerjakan.');
    }

    public function destroy(JadwalUjian $jadwalUjian): RedirectResponse
    {
        $jadwalUjian->delete();

        return redirect()->route('app.panitia.jadwal-ujian.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }

    /**
     * Buat sesi otomatis berdasarkan jumlah siswa dan kapasitas.
     */
    private function buatSesiOtomatis(JadwalUjian $jadwal, string $jamMulai, ?int $ruangId): void
    {
        // Hitung total siswa
        $kelasIds = $jadwal->kelas()->pluck('kelas.id');
        $totalSiswa = SiswaKelas::whereIn('kelas_id', $kelasIds)
            ->where('active', true)
            ->distinct('siswa_nisn')
            ->count('siswa_nisn');

        // Kapasitas
        $kapasitas = 40;
        if ($ruangId) {
            $ruang = RuangUjian::find($ruangId);
            $kapasitas = $ruang?->kapasitas ?? 40;
        }

        // Jumlah sesi
        $jumlahSesi = max(1, (int) ceil($totalSiswa / $kapasitas));

        // Buat sesi
        [$jam, $menit] = array_map('intval', explode(':', $jamMulai));
        $durasi = $jadwal->durasi_menit;
        $jeda = 30; // menit

        for ($i = 1; $i <= $jumlahSesi; $i++) {
            $mulai = sprintf('%02d:%02d', $jam, $menit);
            $totalMenit = $jam * 60 + $menit + $durasi;
            $selesaiJam = intdiv($totalMenit, 60) % 24;
            $selesaiMenit = $totalMenit % 60;
            $selesai = sprintf('%02d:%02d', $selesaiJam, $selesaiMenit);

            JadwalUjianSesi::create([
                'jadwal_ujian_id' => $jadwal->id,
                'nama' => 'Sesi '.$i,
                'urutan' => $i,
                'jam_mulai' => $mulai,
                'jam_selesai' => $selesai,
                'ruang_ujian_id' => $ruangId,
                'kapasitas' => $kapasitas,
                'terisi' => 0,
            ]);

            // Sesi berikutnya: selesai + jeda
            $nextMenit = $totalMenit + $jeda;
            $jam = intdiv($nextMenit, 60) % 24;
            $menit = $nextMenit % 60;
        }
    }
}
