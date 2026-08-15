<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\Matpel;
use Illuminate\Database\Seeder;

class JadwalPelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guruIds = Guru::inRandomOrder()->take(5)->pluck('id')->all();
        $matpelIds = Matpel::pluck('id')->all();
        $kelasIds = Kelas::pluck('id')->all();
        $jpIds = JamPelajaran::where('is_break', false)->pluck('id')->all();
        $jpMap = JamPelajaran::where('is_break', false)->get()->keyBy('id');

        if (empty($guruIds) || empty($matpelIds) || empty($kelasIds) || empty($jpIds)) {
            return;
        }

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $jadwal = [];
        foreach ($hariList as $hari) {
            foreach ($jpIds as $jpId) {
                $jp = $jpMap[$jpId] ?? null;
                $jadwal[] = [
                    'guru_id' => fake()->randomElement($guruIds),
                    'matpel_id' => fake()->randomElement($matpelIds),
                    'kelas_id' => fake()->randomElement($kelasIds),
                    'jam_pelajaran_id' => $jpId,
                    'hari' => $hari,
                    'jam_mulai' => $jp?->jam_mulai ?? '07:00',
                    'jam_selesai' => $jp?->jam_selesai ?? '07:45',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        JadwalPelajaran::insert($jadwal);
    }
}
