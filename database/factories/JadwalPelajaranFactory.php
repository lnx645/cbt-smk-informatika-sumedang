<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
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
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Minggu'];
        $startHour = fake()->numberBetween(7, 16);
        $startMinute = fake()->randomElement(['00', '30']);

        return [
            'guru_id' => Guru::inRandomOrder()->first()?->id ?? Guru::factory(),
            'matpel_id' => Matpel::inRandomOrder()->first()?->id ?? Matpel::factory(),
            'kelas_id' => Kelas::inRandomOrder()->first()?->id ?? Kelas::factory(),
            'hari' => fake()->randomElement($hariList),
            'jam_mulai' => sprintf('%02d:%s', $startHour, $startMinute),
            'jam_selesai' => sprintf('%02d:%s', $startHour + fake()->numberBetween(1, 2), $startMinute),
        ];
    }
}
