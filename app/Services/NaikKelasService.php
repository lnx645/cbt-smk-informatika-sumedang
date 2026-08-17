<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;
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
            ->whereIn('kelas_id', Kelas::leaf()->select('id'))
            ->with(['kelas.parent.parent', 'siswa'])
            ->get()
            ->groupBy('kelas_id');

        $plan = $this->kelasPlan($assignments->keys());

        $kelas = [];
        $ringkasan = ['naik' => 0, 'tinggal' => 0, 'lulus' => 0];

        foreach ($assignments as $kelasId => $items) {
            /** @var SiswaKelas $first */
            $first = $items->first();
            $tingkat = $plan[$kelasId]['tingkat'];
            $targetModel = $plan[$kelasId]['target'];

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
                'kelas_asal' => $first->kelas->nama,
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

            $nisns = collect($pilihan)->pluck('nisn')->unique()->values()->all();

            $assignments = SiswaKelas::query()
                ->where('tahun_ajaran_id', $sumber->id)
                ->where('active', true)
                ->whereIn('siswa_nisn', $nisns)
                ->whereIn('kelas_id', Kelas::leaf()->select('id'))
                ->with(['kelas.parent.parent'])
                ->orderBy('id')
                ->get()
                ->groupBy('siswa_nisn')
                ->map->first();

            $plan = $this->kelasPlan($assignments->pluck('kelas_id')->unique());

            foreach ($pilihan as $item) {
                $nisn = $item['nisn'];
                $status = $item['status'];

                $assignment = $assignments->get($nisn);

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

                $targetKelas = $status === 'naik' ? $plan[$kelas->id]['target'] : $kelas;

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

    /**
     * Hitung tingkat & kelas tujuan untuk setiap kelas sekali, lalu reuse.
     *
     * @param  Collection<int, int>  $kelasIds
     * @return array<int, array{tingkat: ?string, target: ?Kelas}>
     */
    private function kelasPlan(Collection $kelasIds): array
    {
        if ($kelasIds->isEmpty()) {
            return [];
        }

        $plan = [];

        Kelas::query()
            ->whereIn('id', $kelasIds)
            ->with('parent.parent')
            ->get()
            ->each(function (Kelas $kelas) use (&$plan): void {
                $plan[$kelas->id] = [
                    'tingkat' => $kelas->tingkatSekarang(),
                    'target' => $kelas->promoteTarget(),
                ];
            });

        return $plan;
    }
}
