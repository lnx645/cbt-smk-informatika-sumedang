<?php

namespace App\Http\Controllers;

use App\Models\Materi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends BaseAppController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $props = match ($user?->role) {
            'siswa' => $this->siswaProps($user),
            'guru' => ['nama' => $user->guru?->nama_lengkap],
            default => [],
        };

        return Inertia::render('Dashboard', $props);
    }

    /**
     * Data dashboard siswa: sambutan, kutipan, ringkasan matpel, materi terbaru.
     */
    private function siswaProps(User $user): array
    {
        $siswa = $user->siswa;
        $kelas = $siswa?->kelas;

        $scoped = function ($query) use ($kelas) {
            return $query->whereHas('guruKelas', function ($q) use ($kelas): void {
                $q->where('aktif', true)
                    ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
                    ->when($kelas, fn ($w) => $w->where('kelas_id', $kelas->id));
            });
        };

        $materiTerbaru = $scoped(Materi::query()->with(['guruKelas.matpel', 'guru']))
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn (Materi $materi): array => [
                'id' => $materi->id,
                'judul' => $materi->judul,
                'matpel' => $materi->guruKelas?->matpel?->name,
                'guru' => $materi->guru?->nama_lengkap,
                'dibuat_pada' => $materi->created_at?->translatedFormat('d M Y'),
            ])
            ->all();

        $ringkasan = $scoped(Materi::query()->with('guruKelas.matpel'))
            ->get()
            ->groupBy(fn (Materi $materi): mixed => $materi->guruKelas?->matpel_id)
            ->map(fn (Collection $group): array => [
                'id' => $group->first()->guruKelas?->matpel_id,
                'matpel' => $group->first()->guruKelas?->matpel?->name ?? 'Matpel',
                'total' => $group->count(),
            ])
            ->values()
            ->all();

        return [
            'nama' => $siswa?->nama_lengkap,
            'kelas' => $kelas?->nama,
            'tahunAjaran' => $this->tahunAjaran?->name,
            'kutipan' => $this->kutipanAcak(),
            'materiTerbaru' => $materiTerbaru,
            'ringkasan' => $ringkasan,
        ];
    }

    /**
     * Kutipan motivasi acak untuk dashboard.
     */
    private function kutipanAcak(): array
    {
        $kutipan = [
            ['teks' => 'Ilmu itu diperoleh dengan belajar, bukan dengan angan-angan.', 'penulis' => 'Ali bin Abi Thalib'],
            ['teks' => 'Barang siapa menempuh jalan untuk mencari ilmu, maka Allah mudahkan baginya jalan menuju surga.', 'penulis' => 'HR. Muslim'],
            ['teks' => 'Belajarlah dari kemarin, hiduplah untuk hari ini, berharaplah untuk besok.', 'penulis' => 'Albert Einstein'],
            ['teks' => 'Sukses adalah jumlah dari usaha kecil yang diulangi setiap hari.', 'penulis' => 'Robert Collier'],
            ['teks' => 'Jangan pernah berhenti belajar, karena hidup tidak pernah berhenti mengajar.', 'penulis' => 'Anonim'],
            ['teks' => 'Sesungguhnya bersama kesulitan ada kemudahan.', 'penulis' => 'QS. Al-Insyirah: 6'],
            ['teks' => 'Kesuksesan berawal dari kemauan untuk mencoba.', 'penulis' => 'Anonim'],
            ['teks' => 'Orang bijak belajar dari kesalahan orang lain, orang bodoh belajar dari kesalahannya sendiri.', 'penulis' => 'Pepatah'],
            ['teks' => 'Tekad yang kuat mengalahkan bakat yang luar biasa.', 'penulis' => 'Anonim'],
            ['teks' => 'Masa depan adalah milik mereka yang belajar lebih banyak keterampilan.', 'penulis' => 'Carl Rogers'],
        ];

        return $kutipan[array_rand($kutipan)];
    }
}
