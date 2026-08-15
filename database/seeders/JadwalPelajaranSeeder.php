<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
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

        if (empty($guruIds) || empty($matpelIds) || empty($kelasIds)) {
            return;
        }

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $timeSlots = [
            ['07:30', '09:30'],
            ['10:30', '12:00'],
            ['13:00', '14:00'],
            ['14:30', '15:00'],
            ['16:00', '17:00'],
        ];

        $jadwal = [];
        foreach ($hariList as $hari) {
            foreach ($timeSlots as $slot) {
                $jadwal[] = [
                    'guru_id' => fake()->randomElement($guruIds),
                    'matpel_id' => fake()->randomElement($matpelIds),
                    'kelas_id' => fake()->randomElement($kelasIds),
                    'hari' => $hari,
                    'jam_mulai' => $slot[0],
                    'jam_selesai' => $slot[1],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        JadwalPelajaran::insert($jadwal);
    }
}
