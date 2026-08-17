<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

class NaikKelasService
{
    /**
     * Preview pemetaan kenaikan kelas dari TA sumber.
     *
     * @return array{sumber: array<string, mixed>, target: array<string, mixed>, kelas: array<int, array<string, mixed>>, ringkasan: array<string, int>}
     */
    public function preview(TahunAjaran $sumber, TahunAjaran $target): array
    {
        $assignments = SiswaKelas::query()
            ->where('tahun_ajaran_id', $sumber->id)
            ->where('active', true)
            ->whereHas('kelas', fn ($q) => $q->leaf())
            ->with(['kelas', 'siswa'])
            ->get()
            ->groupBy('kelas_id');

        $kelas = [];
        $ringkasan = ['naik' => 0, 'tinggal' => 0, 'lulus' => 0];

        foreach ($assignments as $kelasId => $items) {
            /** @var SiswaKelas $first */
            $first = $items->first();
            $kelasModel = $first->kelas;
            $tingkat = $kelasModel->tingkatSekarang();
            $targetModel = $kelasModel->promoteTarget();

            if ($targetModel) {
                $defaultStatus = 'naik';
                $ringkasan['naik'] += $items->count();
            } elseif ($tingkat === 'XII') {
                $defaultStatus = 'lulus';
                $ringkasan['lulus'] += $items->count();
            } else {
                $defaultStatus = 'tinggal';
                $ringkasan['tinggal'] += $items->count();
            }

            $kelas[] = [
                'kelas_asal' => $kelasModel->nama,
                'kelas_target' => $targetModel?->nama ?? ($tingkat === 'XII' ? 'LULUS' : null),
                'tingkat' => $tingkat,
                'siswa' => $items->map(fn (SiswaKelas $assignment) => [
                    'nisn' => $assignment->siswa_nisn,
                    'nama' => $assignment->siswa?->nama_lengkap,
                    'status' => $defaultStatus,
                ])->values()->all(),
            ];
        }

        usort($kelas, fn (array $a, array $b): int => strcmp($a['kelas_asal'], $b['kelas_asal']));

        return [
            'sumber' => ['id' => $sumber->id, 'name' => $sumber->name],
            'target' => ['id' => $target->id, 'name' => $target->name],
            'kelas' => $kelas,
            'ringkasan' => $ringkasan,
        ];
    }

    /**
     * Eksekusi kenaikan kelas dalam satu transaksi.
     *
     * @param  array<int, array{nisn: string, status: string}>  $pilihan
     * @return array{naik: int, tinggal: int, lulus: int}
     */
    public function execute(TahunAjaran $sumber, TahunAjaran $target, array $pilihan): array
    {
        return DB::transaction(function () use ($sumber, $target, $pilihan): array {
            $hasil = ['naik' => 0, 'tinggal' => 0, 'lulus' => 0];

            foreach ($pilihan as $item) {
                $nisn = $item['nisn'];
                $status = $item['status'];

                $assignment = SiswaKelas::query()
                    ->where('siswa_nisn', $nisn)
                    ->where('tahun_ajaran_id', $sumber->id)
                    ->where('active', true)
                    ->whereHas('kelas', fn ($q) => $q->leaf())
                    ->with('kelas')
                    ->first();

                if (! $assignment) {
                    continue;
                }

                $kelas = $assignment->kelas;

                if ($status === 'lulus') {
                    Siswa::where('nisn', $nisn)->update(['status' => 'lulus']);
                    $assignment->update(['active' => false]);
                    $hasil['lulus']++;

                    continue;
                }

                $targetKelas = $status === 'naik' ? $kelas->promoteTarget() : $kelas;

                if ($status === 'naik' && $targetKelas === null) {
                    $targetKelas = $kelas;
                }

                SiswaKelas::updateOrCreate(
                    [
                        'siswa_nisn' => $nisn,
                        'kelas_id' => $targetKelas->id,
                        'tahun_ajaran_id' => $target->id,
                    ],
                    [
                        'active' => true,
                        'pertama_masuk' => false,
                    ],
                );

                $assignment->update(['active' => false]);
                $hasil[$status]++;
            }

            return $hasil;
        });
    }
}
