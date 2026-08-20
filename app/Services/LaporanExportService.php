<?php

namespace App\Services;

use App\Models\DetailPenilaian;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Materi;
use App\Models\Matpel;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\TugasPengumpulan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanExportService
{
    /**
     * Dataset seluruh data untuk laporan (sheet XLSX / bagian PDF).
     *
     * @return array<int, array{title: string, headers: string[], rows: array<int, array<int, mixed>>}>
     */
    public function datasets(): array
    {
        return [
            $this->sheet('Jurusan', ['Kode', 'Nama Jurusan'], $this->jurusan()),
            $this->sheet('Mata Pelajaran', ['Nama', 'Deskripsi'], $this->matpel()),
            $this->sheet('Tahun Ajaran', ['Tahun Ajaran', 'Status'], $this->tahunAjaran()),
            $this->sheet('Kelas', ['Nama Kelas', 'Tingkat', 'Jurusan', 'Wali Kelas', 'Status'], $this->kelas()),
            $this->sheet('Guru', ['NIP', 'Nama Lengkap', 'Pendidikan Terakhir', 'Jenis Kelamin', 'Alamat', 'Status'], $this->guru()),
            $this->sheet('Siswa', ['NISN', 'NIS', 'Nama Lengkap', 'Tempat Lahir', 'Tanggal Lahir', 'Jenis Kelamin', 'Alamat', 'Kelas Aktif', 'Status'], $this->siswa()),
            $this->sheet('Penugasan Guru-Kelas', ['Guru', 'Kelas', 'Mata Pelajaran', 'Tahun Ajaran', 'Status'], $this->penugasan()),
            $this->sheet('Materi', ['Judul', 'Guru', 'Kelas', 'Mata Pelajaran', 'File', 'Ukuran (KB)', 'Dibuat Pada'], $this->materi()),
            $this->sheet('Tugas', ['Judul', 'Guru', 'Kelas', 'Mata Pelajaran', 'Tanggal Terbit', 'Deadline', 'Jenis Pengumpulan', 'Poin', 'File'], $this->tugas()),
            $this->sheet('Pengumpulan Tugas', ['Tugas', 'Siswa', 'Waktu Kumpul', 'Nilai', 'Jawaban Teks', 'File'], $this->pengumpulan()),
            $this->sheet('Penilaian', ['Nama', 'Tipe', 'Nilai Maks', 'Bobot', 'Sumber', 'Status'], $this->penilaian()),
            $this->sheet('Kelas Penilaian', ['Penilaian', 'Kelas'], $this->penilaianKelas()),
            $this->sheet('Detail Nilai', ['Penilaian', 'Siswa', 'Kelas', 'Mata Pelajaran', 'Tahun Ajaran', 'Guru', 'Nilai', 'Keterangan'], $this->detailNilai()),
            $this->sheet('Riwayat Kelas Siswa', ['Siswa', 'Kelas', 'Tahun Ajaran', 'Aktif', 'Pertama Masuk'], $this->siswaKelas()),
            $this->sheet('Akun Pengguna', ['Nama', 'Email', 'Peran', 'Terhubung dengan'], $this->users()),
        ];
    }

    /**
     * Jumlah data per entitas untuk halaman indeks.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        return [
            'jurusan' => Jurusan::count(),
            'matpel' => Matpel::count(),
            'tahunAjaran' => TahunAjaran::count(),
            'kelas' => Kelas::count(),
            'guru' => Guru::count(),
            'siswa' => Siswa::count(),
            'penugasan' => GuruKelas::count(),
            'materi' => Materi::count(),
            'tugas' => Tugas::count(),
            'pengumpulan' => TugasPengumpulan::count(),
            'penilaian' => Penilaian::count(),
            'detailNilai' => DetailPenilaian::count(),
            'siswaKelas' => SiswaKelas::count(),
            'users' => User::count(),
            'penilaianKelas' => DB::table('penilaian_kelas')->count(),
        ];
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{title: string, headers: string[], rows: array<int, array<int, mixed>>}
     */
    private function sheet(string $title, array $headers, array $rows): array
    {
        return ['title' => $title, 'headers' => $headers, 'rows' => $rows];
    }

    private function tanggal(null|string|\DateTimeInterface $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return Carbon::parse($value)->format('d-m-Y');
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function jurusan(): array
    {
        return Jurusan::orderBy('name')->get()
            ->map(fn(Jurusan $j) => [$j->kode, $j->name])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function matpel(): array
    {
        return Matpel::orderBy('name')->get()
            ->map(fn(Matpel $m) => [$m->name, $m->description])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function tahunAjaran(): array
    {
        return TahunAjaran::orderByDesc('active')->orderBy('name')->get()
            ->map(fn(TahunAjaran $t) => [$t->name, $t->active ? 'Aktif' : 'Nonaktif'])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function kelas(): array
    {
        return Kelas::with(['jurusan', 'walikelas'])->orderBy('nama')->get()
            ->map(fn(Kelas $k) => [$k->nama, $k->tingkat, $k->jurusan?->name ?? '-', $k->walikelas?->nama_lengkap ?? '-', $k->active ? 'Aktif' : 'Nonaktif'])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function guru(): array
    {
        return Guru::orderBy('nama_lengkap')->get()
            ->map(fn(Guru $g) => [$g->nip, $g->nama_lengkap, $g->pendidikan_terakhir ?? '-', $g->jenis_kelamin, $g->alamat ?? '-', $g->is_aktif ? 'Aktif' : 'Nonaktif'])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function siswa(): array
    {
        return Siswa::with('kelas')->orderBy('nama_lengkap')->get()
            ->map(fn(Siswa $s) => [$s->nisn, $s->nis, $s->nama_lengkap, $s->tempat_lahir ?? '-', $this->tanggal($s->tanggal_lahir), $s->jenis_kelamin, $s->alamat ?? '-', $s->kelas?->nama ?? '-', $s->status])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function penugasan(): array
    {
        return GuruKelas::with(['guru', 'kelas', 'matpel', 'tahunAjaran'])->orderBy('id')->get()
            ->map(fn(GuruKelas $gk) => [$gk->guru?->nama_lengkap ?? '-', $gk->kelas?->nama ?? '-', $gk->matpel?->name ?? '-', $gk->tahunAjaran?->name ?? '-', $gk->aktif ? 'Aktif' : 'Nonaktif'])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function materi(): array
    {
        return Materi::with(['guru', 'guruKelas.kelas', 'guruKelas.matpel'])->orderByDesc('created_at')->get()
            ->map(fn(Materi $m) => [$m->judul, $m->guru?->nama_lengkap ?? '-', $m->guruKelas?->kelas?->nama ?? '-', $m->guruKelas?->matpel?->name ?? '-', $m->file_name ?? '-', $m->file_size ? round($m->file_size / 1024, 1) : '-', $m->created_at?->format('d-m-Y') ?? '-'])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function tugas(): array
    {
        return Tugas::with(['guru', 'guruKelas.kelas', 'guruKelas.matpel'])->orderByDesc('created_at')->get()
            ->map(fn(Tugas $t) => [$t->judul, $t->guru?->nama_lengkap ?? '-', $t->guruKelas?->kelas?->nama ?? '-', $t->guruKelas?->matpel?->name ?? '-', $t->tanggal_terbit?->format('d-m-Y') ?? '-', $t->deadline?->format('d-m-Y H:i') ?? '-', $t->jenis_pengumpulan, $t->poin ?? '-', $t->file_name ?? '-'])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function pengumpulan(): array
    {
        return TugasPengumpulan::with(['tugas', 'siswa'])->orderByDesc('submitted_at')->get()
            ->map(fn(TugasPengumpulan $p) => [$p->tugas?->judul ?? '-', $p->siswa?->nama_lengkap ?? $p->siswa_nisn, $p->submitted_at?->format('d-m-Y H:i') ?? '-', $p->nilai ?? '-', $p->jawaban_teks ?? '-', $p->file_name ?? '-'])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function penilaian(): array
    {
        return Penilaian::orderBy('nama')->get()
            ->map(fn(Penilaian $p) => [$p->nama, $p->tipe, $p->nilai_maks, $p->bobot, $p->sumber ?? '-', $p->aktif ? 'Aktif' : 'Nonaktif'])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function penilaianKelas(): array
    {
        return Penilaian::with('kelas')->orderBy('nama')->get()
            ->flatMap(fn(Penilaian $p) => $p->kelas->isEmpty()
                ? [[$p->nama, '-']]
                : $p->kelas->map(fn($k) => [$p->nama, $k->nama]))->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function siswaKelas(): array
    {
        return SiswaKelas::with(['siswa', 'kelas', 'tahunAjaran'])->orderByDesc('active')->get()
            ->map(fn(SiswaKelas $sk) => [$sk->siswa?->nama_lengkap ?? $sk->siswa_nisn, $sk->kelas?->nama ?? '-', $sk->tahunAjaran?->name ?? '-', $sk->active ? 'Aktif' : 'Nonaktif', $sk->pertama_masuk ? 'Ya' : 'Tidak'])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function users(): array
    {
        return User::with(['guru', 'siswa'])->orderBy('name')->get()
            ->map(fn(User $u) => [$u->name, $u->email, $u->role, $u->guru?->nama_lengkap ?? $u->siswa?->nama_lengkap ?? '-'])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function detailNilai(): array
    {
        return DetailPenilaian::with(['penilaian', 'siswa', 'guruKelas.kelas', 'guruKelas.matpel', 'tahunAjaran', 'guru'])
            ->orderBy('id')->get()
            ->map(fn(DetailPenilaian $d) => [
                $d->penilaian?->nama ?? '-',
                $d->siswa?->nama_lengkap ?? $d->siswa_nisn,
                $d->guruKelas?->kelas?->nama ?? '-',
                $d->guruKelas?->matpel?->name ?? '-',
                $d->tahunAjaran?->name ?? '-',
                $d->guru?->nama_lengkap ?? '-',
                $d->nilai,
                $d->keterangan ?? '-',
            ])->all();
    }
}
