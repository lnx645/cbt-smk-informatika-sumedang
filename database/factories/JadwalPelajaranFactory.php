<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\Matpel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JadwalPelajaran>
 */
class JadwalPelajaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jp = JamPelajaran::where('is_break', false)->inRandomOrder()->first();
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        return [
            'guru_id' => Guru::inRandomOrder()->first()?->id ?? Guru::factory(),
            'matpel_id' => Matpel::inRandomOrder()->first()?->id ?? Matpel::factory(),
            'kelas_id' => Kelas::inRandomOrder()->first()?->id ?? Kelas::factory(),
            'jam_pelajaran_id' => $jp->id,
            'hari' => fake()->randomElement($hariList),
            'jam_mulai' => $jp->jam_mulai,
            'jam_selesai' => $jp->jam_selesai,
        ];
    }
}
