<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\BaseAppController;
use App\Models\BeritaAcaraUjian;
use App\Models\Event;
use App\Models\Group;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\JadwalSesi;
use App\Models\Kelas;
use App\Models\KelompokBankSoal;
use App\Models\RuangUjian;
use App\Models\SesiUjian;
use App\Models\SesiUjianPeserta;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Services\KelengkapanUjianService;
use App\Services\NotifikasiService;
use App\Services\UjianService;
use App\Support\Toast;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CBT v2: Panitia Ujian — kelola ujian resmi (UTS/UAS/USBK) lintas kelas.
 *
 * Panitia punya akses penuh: paket soal, sesi, ruang, token, dokumen,
 * dan monitoring real-time. Guru tetap kelola ujiannya sendiri.
 */
class UjianController extends BaseAppController
{
    public function __construct(
        private readonly KelengkapanUjianService $kelengkapanService,
    ) {}

    /**
     * Dashboard panitia: daftar ujian resmi + status kelengkapan.
     */
    public function index(Request $request): Response
    {
        $ujians = Ujian::whereIn('kategori', Ujian::KATEGORI_BESAR)
            ->with(['guruKelas.kelas', 'guruKelas.matpel'])
            ->withCount(['paketSoals', 'sesis'])
            ->orderByDesc('id')
            ->paginate(15);

        $ujians->getCollection()->transform(function (Ujian $u) {
            $u->kelengkapan = $this->kelengkapanService->cek($u);
            $u->siap_terbit = $u->kelengkapan === [];

            return $u;
        });

        return Inertia::render('panitia/Ujian/Index', [
            'ujians' => $ujians,
            'kelasList' => Kelas::orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    /**
     * Form Tambah Jadwal ala Extraordinary CBT.
     */
    public function createJadwal(): Response
    {
        return Inertia::render('panitia/Ujian/TambahJadwal', [
            'events' => Event::orderByDesc('created_at')
                ->get(['id', 'nama', 'kategori']),
            'groupList' => Group::with('kelas:id,nama')->orderBy('nama')->get(['id', 'nama']),
            'kelasList' => Kelas::orderBy('nama')->get(['id', 'nama']),
            'banksoalList' => KelompokBankSoal::with('matpel:id,name')
                ->orderBy('nama')
                ->get()
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'nama' => $b->nama,
                    'matpel' => $b->matpel?->name ?? '-',
                ]),
            'ruangList' => RuangUjian::orderBy('nama')->get(['id', 'nama', 'kapasitas']),
        ]);
    }

    /**
     * Simpan Jadwal ala Extraordinary CBT.
     */
    public function storeJadwal(Request $request): RedirectResponse
    {
        $request->validate([
            'mode' => ['required', 'in:luring,daring'],
        ]);

        // Jika luring, ruangan wajib dipilih
        $ruangRules = $request->input('mode') === 'luring'
            ? ['required', 'array', 'min:1']
            : ['nullable', 'array'];

        $data = $request->validate([
            'event_id' => ['required', 'exists:cbt_events,id'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['exists:kelas,id'],
            'banksoal_id' => ['required', 'exists:cbt_kelompok_bank_soals,id'],
            'nama_ujian' => ['required', 'string', 'max:255'],
            'tanggal_ujian' => ['required', 'date'],
            'durasi' => ['required', 'integer', 'min:1'],
            'acak_soal' => ['boolean'],
            'acak_opsi' => ['boolean'],
            'aktifkan_token' => ['boolean'],
            'tampilkan_nilai' => ['boolean'],
            'sesi' => ['required', 'array', 'min:1'],
            'sesi.*' => ['required', 'date_format:H:i'],
            'mode' => ['required', 'in:luring,daring'],
            'ruang_sesi' => $ruangRules,
            'ruang_sesi.*' => ['nullable', 'exists:cbt_ruang_ujians,id'],
        ]);

        $event = Event::findOrFail($data['event_id']);
        $banksoal = KelompokBankSoal::findOrFail($data['banksoal_id']);

        // Ambil kelas yang dipilih
        $kelasIds = $data['group_ids'] ?? [];

        // Buat 1 jadwal (sistem baru: cbt_jadwals)
        $jadwal = Jadwal::create([
            'event_id' => $event->id,
            'kelompok_bank_soal_id' => $banksoal->id,
            'nama' => strtoupper($data['nama_ujian']),
            'tanggal' => $data['tanggal_ujian'],
            'durasi_menit' => $data['durasi'],
            'acak_soal' => $data['acak_soal'] ?? true,
            'acak_opsi' => $data['acak_opsi'] ?? true,
            'butuh_token' => $data['aktifkan_token'] ?? true,
            'tampilkan_hasil' => $data['tampilkan_nilai'] ?? true,
            'status' => 'draft',
            'mode' => $data['mode'],
        ]);

        // Simpan relasi kelas
        if (! empty($kelasIds)) {
            $jadwal->kelas()->sync($kelasIds);
        }

        // Buat sesi OTOMATIS: jumlah siswa ÷ kapasitas
        $jamAwal = $data['sesi'][0] ?? '08:00';
        $ruangSesi = $data['ruang_sesi'] ?? [];
        $ruangId = $data['mode'] === 'luring' ? ($ruangSesi[0] ?? null) : null;

        app(\App\Services\SesiAssignService::class)->bagiSiswaKeSesi(
            $jadwal->id,
            $kelasIds,
            $jamAwal,
            30,
            $ruangId,
            40,
        );

        return redirect()->route('app.panitia.jadwal.index')
            ->with('success', 'Jadwal ujian berhasil dibuat.');
    }

    /**
     * Jadwal Ujian ala Extraordinary/ExoCBT: tabel semua sesi lintas ujian.
     */
    public function jadwal(Request $request): Response
    {
        $query = Jadwal::with(['event', 'banksoal.matpel', 'sesi']);

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->date('tanggal'));
        }

        $jadwals = $query->orderBy('tanggal')->paginate(20)->withQueryString();

        $jadwals->getCollection()->transform(fn ($j) => [
            'id' => $j->id,
            'nama' => $j->nama,
            'event' => $j->event?->nama ?? '-',
            'banksoal' => $j->banksoal?->nama ?? '-',
            'matpel' => $j->banksoal?->matpel?->name ?? '-',
            'tanggal' => $j->tanggal->format('d M Y'),
            'durasi' => $j->durasi_menit,
            'jumlah_sesi' => $j->sesi->count(),
            'status' => $j->status,
        ]);

        return Inertia::render('panitia/Ujian/Jadwal', [
            'jadwals' => $jadwals,
            'filters' => $request->only(['tanggal']),
        ]);
    }

    /**
     * Detail jadwal: lihat sesi-sesi.
     */
    public function showJadwal(\App\Models\Jadwal $jadwal): \Inertia\Response
    {
        $jadwal->load(['event', 'banksoal.matpel', 'sesi.ruang', 'kelas']);

        return \Inertia\Inertia::render('panitia/Ujian/DetailJadwal', [
            'jadwal' => [
                'id' => $jadwal->id,
                'nama' => $jadwal->nama,
                'event' => $jadwal->event?->nama ?? '-',
                'banksoal' => $jadwal->banksoal?->nama ?? '-',
                'matpel' => $jadwal->banksoal?->matpel?->name ?? '-',
                'tanggal' => $jadwal->tanggal->format('d M Y'),
                'durasi' => $jadwal->durasi_menit,
                'mode' => $jadwal->mode,
                'status' => $jadwal->status,
                'kelas' => $jadwal->kelas->pluck('nama')->join(', '),
                'sesi' => $jadwal->sesi->map(fn ($s) => [
                    'id' => $s->id,
                    'nama' => $s->nama,
                    'jam_mulai' => substr($s->jam_mulai, 0, 5),
                    'jam_selesai' => substr($s->jam_selesai, 0, 5),
                    'ruang' => $s->ruang?->nama ?? '-',
                    'kapasitas' => $s->kapasitas,
                    'terisi' => $s->terisi,
                ]),
            ],
        ]);
    }

    public function editJadwal(\App\Models\Jadwal $jadwal): \Inertia\Response
    {
        $jadwal->load(['kelas']);
        return \Inertia\Inertia::render('panitia/Ujian/EditJadwal', [
            'jadwal' => [
                'id' => $jadwal->id,
                'nama' => $jadwal->nama,
                'event_id' => $jadwal->event_id,
                'kelompok_bank_soal_id' => $jadwal->kelompok_bank_soal_id,
                'tanggal' => $jadwal->tanggal->format('Y-m-d'),
                'durasi_menit' => $jadwal->durasi_menit,
                'mode' => $jadwal->mode,
                'kelas_ids' => $jadwal->kelas->pluck('id')->all(),
            ],
            'events' => \App\Models\Event::orderByDesc('created_at')->get(['id', 'nama']),
            'kelasList' => \App\Models\Kelas::orderBy('nama')->get(['id', 'nama']),
            'banksoalList' => \App\Models\KelompokBankSoal::orderBy('nama')->get()->map(fn ($b) => ['id' => $b->id, 'nama' => $b->nama]),
        ]);
    }

    public function updateJadwal(\Illuminate\Http\Request $request, \App\Models\Jadwal $jadwal): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'event_id' => ['required', 'exists:cbt_events,id'],
            'kelompok_bank_soal_id' => ['required', 'exists:cbt_kelompok_bank_soals,id'],
            'tanggal' => ['required', 'date'],
            'durasi_menit' => ['required', 'integer', 'min:1'],
            'mode' => ['required', 'in:luring,daring'],
            'kelas_ids' => ['nullable', 'array'],
            'kelas_ids.*' => ['exists:kelas,id'],
        ]);
        $jadwal->update($data);
        $jadwal->kelas()->sync($data['kelas_ids'] ?? []);
        return redirect()->route('app.panitia.jadwal.show', $jadwal)->with('success', 'Jadwal diperbarui.');
    }

    public function destroyJadwal(\App\Models\Jadwal $jadwal): \Illuminate\Http\RedirectResponse
    {
        $jadwal->delete();
        return redirect()->route('app.panitia.jadwal.index')->with('success', 'Jadwal dihapus.');
    }

    /**
     * Form buat ujian resmi baru (UTS/UAS/USBK) oleh panitia.
     * Guru TIDAK bisa bikin ujian resmi, hanya panitia.
     */
    public function create(): Response
    {
        $gurus = Guru::with(['guruKelas.kelas', 'guruKelas.matpel'])
            ->orderBy('nama_lengkap')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'nama' => $g->nama_lengkap,
                'mapel' => $g->guruKelas->first()?->matpel?->name ?? '-',
            ]);

        return Inertia::render('panitia/Ujian/Create', [
            'gurus' => $gurus,
            'kategoris' => [
                ['value' => 'uts', 'label' => 'UTS'],
                ['value' => 'uas', 'label' => 'UAS'],
                ['value' => 'usbk', 'label' => 'USBK'],
            ],
        ]);
    }

    /**
     * Simpan ujian resmi baru dari panitia.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:uts,uas,usbk'],
            'guru_id' => ['required', 'exists:gurus,id'],
            'durasi_menit' => ['required', 'integer', 'min:1'],
            'nilai_maks' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $guru = Guru::findOrFail($data['guru_id']);
        $guruKelas = $guru->guruKelas()->first();

        abort_unless($guruKelas, 422, 'Guru belum memiliki kelas/mapel.');

        $ujian = Ujian::create([
            'judul' => $data['judul'],
            'kategori' => $data['kategori'],
            'guru_id' => $guru->id,
            'guru_kelas_id' => $guruKelas->id,
            'durasi_menit' => $data['durasi_menit'],
            'nilai_maks' => $data['nilai_maks'],
            'status' => 'draf',
            'tahun_ajaran_id' => TahunAjaran::where('active', true)->first()?->id,
        ]);

        return redirect()->route('app.panitia.ujian.show', $ujian)
            ->with('success', 'Ujian resmi dibuat. Atur sesi, ruang, dan terbitkan.');
    }

    /**
     * Cetak jadwal ujian keseluruhan (PDF timetable).
     */
    public function jadwalPdf(Request $request): \Illuminate\Http\Response
    {
        $ujians = Ujian::whereIn('kategori', Ujian::KATEGORI_BESAR)
            ->with(['guruKelas.kelas', 'guruKelas.matpel', 'sesis.ruang'])
            ->whereIn('status', ['terbit', 'diajukan'])
            ->orderBy('id')
            ->get();

        $pdf = Pdf::loadView('pdf.jadwal-ujian', [
            'ujians' => $ujians,
            'tanggal_cetak' => now()->translatedFormat('d F Y'),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('jadwal-ujian.pdf');
    }

    /**
     * Monitoring real-time: status pengerjaan per sesi.
     */
    public function monitoring(Request $request, Ujian $ujian): Response
    {
        $ujian->load(['sesis.ruang', 'paketSoals']);

        $sesiId = $request->integer('sesi_id') ?: null;

        $query = UjianPengerjaan::where('ujian_id', $ujian->id)
            ->with(['siswa', 'paketSoal', 'sesi'])
            ->orderBy('siswa_nisn');

        if ($sesiId) {
            $query->where('sesi_ujian_id', $sesiId);
        }

        $pengerjaans = $query->get()->map(fn (UjianPengerjaan $p) => [
            'id' => $p->id,
            'nisn' => $p->siswa_nisn,
            'nama' => $p->siswa?->nama_lengkap ?? $p->siswa_nisn,
            'paket' => $p->paketSoal?->kode,
            'sesi' => $p->sesi?->nama,
            'status' => $p->status,
            'mulai_at' => $p->mulai_at?->toDateTimeString(),
            'batas_at' => $p->batas_at?->toDateTimeString(),
            'jumlah_pelanggaran' => $p->jumlah_pelanggaran,
            'nilai_total' => $p->nilai_total,
            'is_remedial' => $p->is_remedial,
        ]);

        $ringkasan = [
            'total' => $pengerjaans->count(),
            'mengerjakan' => $pengerjaans->where('status', 'berlangsung')->count(),
            'selesai' => $pengerjaans->whereIn('status', ['selesai', 'auto_submit'])->count(),
            'pelanggaran' => $pengerjaans->where('jumlah_pelanggaran', '>', 0)->count(),
        ];

        return Inertia::render('panitia/Ujian/Monitoring', [
            'ujian' => $ujian->only(['id', 'judul', 'kategori', 'durasi_menit']),
            'sesis' => $ujian->sesis->map(fn ($s) => ['id' => $s->id, 'nama' => $s->nama]),
            'sesi_id' => $sesiId,
            'ringkasan' => $ringkasan,
            'pengerjaans' => $pengerjaans,
        ]);
    }

    /**
     * Reset attempt siswa bermasalah (dari monitoring).
     */
    public function resetPengerjaan(Request $request, Ujian $ujian, UjianPengerjaan $pengerjaan): RedirectResponse
    {
        abort_unless($pengerjaan->ujian_id === $ujian->id, 404);

        $pengerjaan->jawabans()->delete();
        $pengerjaan->delete();

        return redirect()->back()->with('success', 'Pengerjaan siswa direset. Siswa bisa mulai ulang.');
    }

    /**
     * Detail pengaturan panitia: sesi, ruang, jadwal, token, tata tertib, KKM.
     * Alur sekolah beneran: guru serahkan soal → panitia atur & terbitkan.
     */
    public function show(Request $request, Ujian $ujian): Response
    {
        $ujian->load(['paketSoals.soals', 'sesis.ruang', 'guruKelas.kelas', 'guruKelas.matpel', 'guru']);

        return Inertia::render('panitia/Ujian/Show', [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'status' => $ujian->status,
                'durasi_menit' => $ujian->durasi_menit,
                'token' => $ujian->token,
                'token_dibuka' => $ujian->token_released_at !== null,
                'tata_tertib' => $ujian->tata_tertib,
                'kkm' => $ujian->kkm,
                'tampilkan_hasil' => (bool) $ujian->tampilkan_hasil,
                'kartu_diterbitkan' => (bool) $ujian->kartu_diterbitkan,
                'kelas' => $ujian->guruKelas?->kelas?->nama,
                'matpel' => $ujian->guruKelas?->matpel?->name,
                'guru' => $ujian->guru?->nama_lengkap,
                'paket_soals' => $ujian->paketSoals->map(fn ($p) => [
                    'id' => $p->id, 'kode' => $p->kode, 'nama' => $p->nama,
                    'jumlah_soal' => $p->soals->count(),
                ]),
                'sesis' => $ujian->sesis->map(fn ($s) => [
                    'id' => $s->id, 'nama' => $s->nama,
                    'waktu_mulai' => $s->waktu_mulai?->format('Y-m-d\TH:i'),
                    'waktu_selesai' => $s->waktu_selesai?->format('Y-m-d\TH:i'),
                    'ruang_ujian_id' => $s->ruang_ujian_id,
                    'ruang' => $s->ruang?->nama,
                    'kapasitas' => $s->kapasitas,
                    'token' => $s->token,
                    'token_dibuka' => $s->token_released_at !== null,
                    'peserta_nisns' => $s->pesertas()->pluck('siswa_nisn')->toArray(),
                    'jumlah_peserta' => $s->pesertas()->count(),
                ]),
            ],
            'ruangs' => RuangUjian::orderBy('nama')->get(['id', 'nama', 'kapasitas']),
            'siswas' => $this->daftarSiswaKelas($ujian),
            'petaSesi' => SesiUjianPeserta::where('ujian_id', $ujian->id)
                ->with('sesi:id,nama')
                ->get(['siswa_nisn', 'sesi_ujian_id'])
                ->mapWithKeys(fn ($p) => [$p->siswa_nisn => $p->sesi?->nama ?? '?']),
            'kelengkapan' => $this->kelengkapanService->cek($ujian),
        ]);
    }

    /**
     * Daftar siswa di kelas ujian (untuk penjadwalan sesi).
     */
    private function daftarSiswaKelas(Ujian $ujian): array
    {
        $kelasId = $ujian->guruKelas?->kelas_id;
        if (! $kelasId) {
            return [];
        }

        $nisns = SiswaKelas::where('kelas_id', $kelasId)
            ->where('active', true)->pluck('siswa_nisn');

        return Siswa::whereIn('nisn', $nisns)
            ->orderBy('nama_lengkap')
            ->get(['nisn', 'nama_lengkap'])
            ->map(fn ($s) => ['nisn' => $s->nisn, 'nama' => $s->nama_lengkap])
            ->toArray();
    }

    /**
     * Panitia simpan sesi & ruang (mengganti semua).
     */
    public function storeSesi(Request $request, Ujian $ujian): RedirectResponse
    {
        $data = $request->validate([
            'sesis' => ['required', 'array', 'min:1'],
            'sesis.*.nama' => ['required', 'string', 'max:100'],
            'sesis.*.waktu_mulai' => ['required', 'date'],
            'sesis.*.waktu_selesai' => ['required', 'date', 'after:sesis.*.waktu_mulai'],
            'sesis.*.ruang_ujian_id' => ['nullable', 'integer', 'exists:cbt_ruang_ujians,id'],
            'sesis.*.kapasitas' => ['nullable', 'integer', 'min:1'],
        ]);

        $sesis = array_values($data['sesis']);

        // Cegah bentrok: ruang yang sama tidak boleh dipakai sesi lain
        // (ujian lain maupun sesi lain dalam form ini) yang waktunya tumpang tindih.
        foreach ($sesis as $s) {
            if (empty($s['ruang_ujian_id'])) {
                continue;
            }

            $mulai = Carbon::parse($s['waktu_mulai']);
            $selesai = Carbon::parse($s['waktu_selesai']);

            // Bentrok dengan sesi ujian lain di database (termasuk se-ujian).
            $bentrok = SesiUjian::query()
                ->where('ruang_ujian_id', $s['ruang_ujian_id'])
                ->where('waktu_mulai', '<', $selesai)
                ->where('waktu_selesai', '>', $mulai)
                ->with('ujian')
                ->first();

            if ($bentrok) {
                $label = $bentrok->ujian?->judul ?? 'ujian lain';
                $ruang = RuangUjian::find($s['ruang_ujian_id'])?->nama ?? 'ruang';

                return redirect()->back()->with(
                    'error',
                    "Ruang {$ruang} bentrok dengan {$bentrok->nama} ({$label}) pada {$s['nama']}. Pilih ruang atau waktu lain."
                );
            }

            // Bentrok antar sesi dalam form yang sama.
            foreach ($sesis as $lain) {
                if ($lain === $s || empty($lain['ruang_ujian_id'])) {
                    continue;
                }
                $lainMulai = Carbon::parse($lain['waktu_mulai']);
                $lainSelesai = Carbon::parse($lain['waktu_selesai']);
                if (
                    $lain['ruang_ujian_id'] == $s['ruang_ujian_id']
                    && $lainMulai < $selesai
                    && $lainSelesai > $mulai
                ) {
                    $ruang = RuangUjian::find($s['ruang_ujian_id'])?->nama ?? 'ruang';

                    return redirect()->back()->with(
                        'error',
                        "Ruang {$ruang} dipakai dua sesi yang waktunya tumpang tindih ({$s['nama']} & {$lain['nama']})."
                    );
                }
            }
        }

        $ujian->sesis()->delete();

        foreach ($sesis as $i => $s) {
            $ujian->sesis()->create([
                'nama' => $s['nama'],
                'urutan' => $i + 1,
                'waktu_mulai' => $s['waktu_mulai'],
                'waktu_selesai' => $s['waktu_selesai'],
                'ruang_ujian_id' => $s['ruang_ujian_id'] ?? null,
                'kapasitas' => $s['kapasitas'] ?? null,
            ]);
        }

        return redirect()->back()->with('success', 'Jadwal sesi tersimpan.');
    }

    /**
     * Tambah satu sesi (via modal).
     */
    public function storeSesiSatu(Request $request, Ujian $ujian): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'waktu_mulai' => ['required', 'date'],
            'waktu_selesai' => ['required', 'date', 'after:waktu_mulai'],
            'ruang_ujian_id' => ['nullable', 'integer', 'exists:cbt_ruang_ujians,id'],
        ]);

        // Cek bentrok ruang (termasuk se-ujian, kecuali sesi ini sendiri)
        if (! empty($data['ruang_ujian_id'])) {
            $mulai = Carbon::parse($data['waktu_mulai']);
            $selesai = Carbon::parse($data['waktu_selesai']);
            $bentrok = SesiUjian::query()
                ->where('ruang_ujian_id', $data['ruang_ujian_id'])
                ->where('id', '!=', $sesi->id)
                ->where('waktu_mulai', '<', $selesai)
                ->where('waktu_selesai', '>', $mulai)
                ->with('ujian')->first();
            if ($bentrok) {
                $ruang = RuangUjian::find($data['ruang_ujian_id'])?->nama ?? 'ruang';

                return redirect()->back()->with('error', "Ruang {$ruang} bentrok dengan {$bentrok->nama}. Pilih ruang atau waktu lain.");
            }
        }

        $ujian->sesis()->create([
            'nama' => $data['nama'],
            'urutan' => $ujian->sesis()->count() + 1,
            'waktu_mulai' => $data['waktu_mulai'],
            'waktu_selesai' => $data['waktu_selesai'],
            'ruang_ujian_id' => $data['ruang_ujian_id'] ?? null,
        ]);

        Toast::success('Sesi ditambahkan.');

        return Redirect::back();
    }

    /**
     * Update satu sesi (via modal).
     */
    public function updateSesiSatu(Request $request, Ujian $ujian, SesiUjian $sesi): RedirectResponse
    {
        abort_unless($sesi->ujian_id === $ujian->id, 404);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'waktu_mulai' => ['required', 'date'],
            'waktu_selesai' => ['required', 'date', 'after:waktu_mulai'],
            'ruang_ujian_id' => ['nullable', 'integer', 'exists:cbt_ruang_ujians,id'],
        ]);

        $sesi->update($data);
        Toast::success('Sesi diperbarui.');

        return Redirect::back();
    }

    /**
     * Hapus satu sesi (via modal).
     */
    public function destroySesiSatu(Request $request, Ujian $ujian, SesiUjian $sesi): RedirectResponse
    {
        abort_unless($sesi->ujian_id === $ujian->id, 404);

        if ($sesi->pengerjaans()->exists()) {
            Toast::error('Sesi tidak dapat dihapus karena sudah dipakai.');

            return Redirect::back();
        }

        $sesi->delete();
        Toast::success('Sesi dihapus.');

        return Redirect::back();
    }

    /**
     * Simpan peserta sesi (siswa yang dijadwalkan ke sesi ini).
     * Siswa yang dipilih otomatis PINDAH dari sesi lain (1 siswa = 1 sesi per ujian).
     */
    public function storeSesiPeserta(Request $request, Ujian $ujian, SesiUjian $sesi): RedirectResponse
    {
        abort_unless($sesi->ujian_id === $ujian->id, 404);

        $data = $request->validate([
            'nisns' => ['required', 'array'],
            'nisns.*' => ['string', 'exists:siswa,nisn'],
        ]);

        // Hapus siswa-siswa ini dari SEMUA sesi ujian ini dulu (biar pindah, bukan duplikat)
        SesiUjianPeserta::where('ujian_id', $ujian->id)
            ->whereIn('siswa_nisn', $data['nisns'])
            ->delete();

        // Hapus peserta lama sesi ini yang tidak dipilih lagi
        $sesi->pesertas()->delete();

        // Simpan yang baru
        foreach ($data['nisns'] as $nisn) {
            $sesi->pesertas()->create([
                'ujian_id' => $ujian->id,
                'siswa_nisn' => $nisn,
            ]);
        }

        Toast::success(count($data['nisns']).' siswa dijadwalkan ke '.$sesi->nama.'.');

        return Redirect::back();
    }

    /**
     * Panitia simpan token, tata tertib, KKM.
     * Token dibuat tapi BELUM dibuka — panitia buka manual saat ujian mau mulai.
     */
    public function storePengaturan(Request $request, Ujian $ujian): RedirectResponse
    {
        $data = $request->validate([
            'tata_tertib' => ['required', 'string', 'min:10'],
            'kkm' => ['required', 'integer', 'min:0', 'max:100'],
            'tampilkan_hasil' => ['sometimes', 'boolean'],
        ]);

        $ujian->update([
            'token' => $ujian->token ?? app(UjianService::class)->generateToken(),
            'tata_tertib' => $data['tata_tertib'],
            'kkm' => $data['kkm'],
            'tampilkan_hasil' => $request->boolean('tampilkan_hasil'),
        ]);

        return redirect()->back()->with('success', 'Token, tata tertib & KKM tersimpan. Token belum dibuka — buka saat ujian mau dimulai.');
    }

    /**
     * Panitia generate password untuk semua siswa (universal, berlaku semua ujian).
     */
    public function terbitkanKartu(Request $request, Ujian $ujian): RedirectResponse
    {
        $kelasId = $ujian->guruKelas?->kelas_id;
        abort_unless($kelasId, 404);

        $nisns = SiswaKelas::where('kelas_id', $kelasId)
            ->where('active', true)
            ->pluck('siswa_nisn');

        $dibuat = 0;
        foreach ($nisns as $nisn) {
            $siswa = Siswa::where('nisn', $nisn)->first();
            if ($siswa && ! $siswa->password_ujian) {
                $plain = \Str::upper(\Str::random(6));
                $plain = strtr($plain, ['O' => 'A', '0' => 'B', 'I' => 'C', '1' => 'D']);
                $siswa->update(['password_ujian' => Crypt::encryptString($plain)]);
                $dibuat++;
            }
        }

        Toast::success("{$dibuat} password dibuat. Siswa bisa download kartu dari dashboard.");

        return Redirect::back();
    }

    /**
     * Panitia buat token per sesi.
     */
    public function buatTokenSesi(Request $request, Ujian $ujian, SesiUjian $sesi): RedirectResponse
    {
        abort_unless($sesi->ujian_id === $ujian->id, 404);

        $sesi->update([
            'token' => app(UjianService::class)->generateToken(),
            'token_released_at' => null,
        ]);

        Toast::success("Token {$sesi->nama} dibuat.");

        return Redirect::back();
    }

    /**
     * Panitia buka token sesi.
     */
    public function bukaTokenSesi(Request $request, Ujian $ujian, SesiUjian $sesi): RedirectResponse
    {
        abort_unless($sesi->ujian_id === $ujian->id, 404);

        $sesi->update(['token_released_at' => now()]);
        Toast::success("Token {$sesi->nama} dibuka.");

        return Redirect::back();
    }

    /**
     * Panitia tutup token sesi.
     */
    public function tutupTokenSesi(Request $request, Ujian $ujian, SesiUjian $sesi): RedirectResponse
    {
        abort_unless($sesi->ujian_id === $ujian->id, 404);

        $sesi->update(['token_released_at' => null]);
        Toast::success("Token {$sesi->nama} ditutup.");

        return Redirect::back();
    }

    /**
     * Panitia buat token baru.
     */
    public function buatToken(Request $request, Ujian $ujian): RedirectResponse
    {
        $ujian->update([
            'token' => app(UjianService::class)->generateToken(),
            'token_released_at' => null,
        ]);

        Toast::success('Token dibuat. Token belum dibuka — buka saat ujian mau dimulai.');

        return Redirect::back();
    }

    /**
     * Panitia buka token (siswa bisa lihat & pakai).
     */
    public function bukaToken(Request $request, Ujian $ujian): RedirectResponse
    {
        if (! $ujian->token) {
            $ujian->update(['token' => app(UjianService::class)->generateToken()]);
        }

        $ujian->update(['token_released_at' => now()]);

        return redirect()->back()->with('success', 'Token dibuka. Siswa sekarang bisa melihat token.');
    }

    /**
     * Panitia tutup token (siswa tidak bisa mulai ujian baru).
     */
    public function tutupToken(Request $request, Ujian $ujian): RedirectResponse
    {
        $ujian->update(['token_released_at' => null]);

        return redirect()->back()->with('success', 'Token ditutup. Siswa tidak bisa memulai ujian baru.');
    }

    /**
     * Panitia terbitkan ujian resmi.
     */
    public function terbit(Request $request, Ujian $ujian): RedirectResponse
    {
        $masalah = $this->kelengkapanService->cek($ujian);
        if ($masalah !== []) {
            return redirect()->back()->withErrors(['kelengkapan' => 'Belum bisa diterbitkan: '.implode(' ', $masalah)]);
        }

        $ujian->update(['status' => 'terbit']);
        app(NotifikasiService::class)->jadwalUjian($ujian->fresh());

        return redirect()->route('app.panitia.ujian.index')->with('success', 'Ujian diterbitkan!');
    }

    /**
     * Panitia kembalikan ke guru (revisi).
     */
    public function kembalikan(Request $request, Ujian $ujian): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['nullable', 'string', 'max:500']]);

        $ujian->update([
            'status' => 'draft',
            'catatan_panitia' => $data['catatan'] ?? null,
        ]);

        return redirect()->route('app.panitia.ujian.index')->with(
            'success',
            'Ujian dikembalikan ke guru untuk direvisi.'
        );
    }

    /**
     * Cetak kartu peserta per sesi (PDF).
     */
    public function kartuPeserta(Request $request, Ujian $ujian, SesiUjian $sesi): \Illuminate\Http\Response
    {
        abort_unless($sesi->ujian_id === $ujian->id, 404);

        $sesi->load(['ruang']);

        $pesertas = $this->pesertaSesi($ujian, $sesi)->map(function ($s) {
            $s->password_ujian_plain = null;
            if ($s->password_ujian) {
                try {
                    $s->password_ujian_plain = Crypt::decryptString($s->password_ujian);
                } catch (\Exception $e) {
                    $s->password_ujian_plain = null;
                }
            }

            return $s;
        });

        $pdf = Pdf::loadView('pdf.kartu-peserta', [
            'ujian' => $ujian,
            'sesi' => $sesi,
            'pesertas' => $pesertas,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("kartu-peserta-{$sesi->nama}.pdf");
    }

    /**
     * Cetak kartu peserta per kelas (PDF) — semua sesi digabung.
     */
    public function kartuPesertaKelas(Request $request, Ujian $ujian): \Illuminate\Http\Response
    {
        $ujian->load(['sesis.ruang', 'guruKelas.kelas']);

        $kelasId = $ujian->guruKelas?->kelas_id;
        $pesertas = Siswa::query()->orderBy('nama_lengkap')
            ->whereIn('nisn', SiswaKelas::where('kelas_id', $kelasId)
                ->where('active', true)->pluck('siswa_nisn'))
            ->get()
            ->map(function ($s) {
                $s->password_ujian_plain = null;
                if ($s->password_ujian) {
                    try {
                        $s->password_ujian_plain = Crypt::decryptString($s->password_ujian);
                    } catch (\Exception $e) {
                        $s->password_ujian_plain = null;
                    }
                }

                return $s;
            });

        $pdf = Pdf::loadView('pdf.kartu-peserta-kelas', [
            'ujian' => $ujian,
            'pesertas' => $pesertas,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("kartu-peserta-{$ujian->judul}.pdf");
    }

    /**
     * Cetak SATU kartu per siswa berisi SEMUA ujian terbit (per kelas).
     * Seperti kartu UNBK: 1 kartu = semua jadwal ujian siswa.
     */
    public function kartuPesertaSemua(Request $request): \Illuminate\Http\Response
    {
        $kelasId = $request->integer('kelas_id');
        abort_if($kelasId <= 0, 400, 'Pilih kelas terlebih dahulu.');

        $kelas = Kelas::findOrFail($kelasId);

        // Pastikan format tanggal Indonesia
        Carbon::setLocale('id');

        // Semua ujian RESMI (UTS/UAS/USBK) yang TERBIT untuk kelas ini — kuis/UH tidak ikut
        $ujians = Ujian::query()
            ->where('status', 'terbit')
            ->whereIn('kategori', Ujian::KATEGORI_BESAR)
            ->whereHas('guruKelas', fn ($q) => $q->where('kelas_id', $kelasId))
            ->with(['sesis.ruang', 'guruKelas.matpel'])
            ->orderBy('id')
            ->get();

        // Semua siswa aktif di kelas ini
        $nisns = SiswaKelas::where('kelas_id', $kelasId)
            ->where('active', true)->pluck('siswa_nisn');
        $pesertas = Siswa::query()->orderBy('nama_lengkap')
            ->whereIn('nisn', $nisns)->get();

        $pdf = Pdf::loadView('pdf.kartu-peserta-semua', [
            'kelas' => $kelas,
            'ujians' => $ujians,
            'pesertas' => $pesertas,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("kartu-peserta-{$kelas->nama}.pdf");
    }

    /**
     * Daftar berita acara ujian.
     */
    public function beritaAcaraIndex(Request $request, Ujian $ujian): Response
    {
        $beritaAcaras = $ujian->beritaAcaras()->with('sesi')->orderByDesc('tanggal')->get();

        return Inertia::render('panitia/Ujian/BeritaAcara', [
            'ujian' => ['id' => $ujian->id, 'judul' => $ujian->judul],
            'sesis' => $ujian->sesis()->orderBy('urutan')->get(['id', 'nama']),
            'beritaAcaras' => $beritaAcaras->map(fn (BeritaAcaraUjian $b): array => [
                'id' => $b->id,
                'sesi_nama' => $b->sesi?->nama ?? 'Tanpa sesi',
                'tanggal' => $b->tanggal->translatedFormat('d M Y'),
                'jumlah_peserta' => $b->jumlah_peserta,
                'jumlah_hadir' => $b->jumlah_hadir,
                'jumlah_tidak_hadir' => $b->jumlah_tidak_hadir,
                'jumlah_pelanggaran' => $b->jumlah_pelanggaran,
            ]),
        ]);
    }

    /**
     * Simpan berita acara.
     */
    public function beritaAcaraStore(Request $request, Ujian $ujian): RedirectResponse
    {
        $data = $request->validate([
            'sesi_ujian_id' => ['nullable', 'integer', 'exists:cbt_sesi_ujians,id'],
            'tanggal' => ['required', 'date'],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'nama_pengawas' => ['nullable', 'string', 'max:100'],
            'nama_kepala_sekolah' => ['nullable', 'string', 'max:100'],
        ]);

        if ($data['sesi_ujian_id'] ?? null) {
            abort_unless(SesiUjian::where('id', $data['sesi_ujian_id'])->where('ujian_id', $ujian->id)->exists(), 404);
        }

        BeritaAcaraUjian::updateOrCreate(
            ['ujian_id' => $ujian->id, 'sesi_ujian_id' => $data['sesi_ujian_id'] ?? null],
            $data
        );

        return redirect()->back()->with('success', 'Berita acara disimpan.');
    }

    /**
     * Cetak berita acara (PDF).
     */
    public function beritaAcaraPdf(Request $request, Ujian $ujian, BeritaAcaraUjian $beritaAcara): \Illuminate\Http\Response
    {
        abort_unless($beritaAcara->ujian_id === $ujian->id, 404);
        $beritaAcara->load(['sesi.ruang']);

        $pdf = Pdf::loadView('pdf.berita-acara', [
            'ujian' => $ujian,
            'beritaAcara' => $beritaAcara,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('berita-acara.pdf');
    }

    /**
     * Cetak daftar hadir per sesi (PDF).
     */
    public function daftarHadir(Request $request, Ujian $ujian, SesiUjian $sesi): \Illuminate\Http\Response
    {
        abort_unless($sesi->ujian_id === $ujian->id, 404);
        $sesi->load(['ruang']);

        $pesertas = $this->pesertaSesi($ujian, $sesi);

        $pdf = Pdf::loadView('pdf.daftar-hadir', [
            'ujian' => $ujian,
            'sesi' => $sesi,
            'pesertas' => $pesertas,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("daftar-hadir-{$sesi->nama}.pdf");
    }

    /**
     * Daftar siswa peserta sesi.
     */
    public function terbitkanJadwal(\App\Models\Jadwal $jadwal): \Illuminate\Http\RedirectResponse
    {
        $jadwal->update(['status' => 'published']);
        return back()->with('success', 'Jadwal diterbitkan.');
    }

    public function buatTokenJadwalSesi(\App\Models\JadwalSesi $sesi): \Illuminate\Http\RedirectResponse
    {
        $sesi->update([
            'token' => strtoupper(\Illuminate\Support\Str::random(6)),
            'token_released_at' => now(),
        ]);
        return back()->with('success', 'Token dibuat: '.$sesi->token);
    }

    private function pesertaSesi(Ujian $ujian, SesiUjian $sesi): Collection
    {
        $kelasId = $ujian->guruKelas?->kelas_id;

        $query = Siswa::query()->orderBy('nama_lengkap');

        if ($ujian->sesiTerjadwal()) {
            $nisns = $sesi->pesertas()->pluck('siswa_nisn');
            $query->whereIn('nisn', $nisns);
        } elseif ($kelasId !== null) {
            $nisns = SiswaKelas::where('kelas_id', $kelasId)
                ->where('active', true)->pluck('siswa_nisn');
            $query->whereIn('nisn', $nisns);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->get();
    }
}
