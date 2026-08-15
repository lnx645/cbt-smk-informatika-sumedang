<?php

namespace Database\Factories;

use App\Models\JamPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JamPelajaran>
 */
class JamPelajaranFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => 'JP '.fake()->numberBetween(1, 9),
            'jam_mulai' => fake()->time('H:i'),
            'jam_selesai' => fake()->time('H:i'),
            'is_break' => fake()->boolean(10),
            'urutan' => fake()->numberBetween(1, 20),
        ];
    }
}
