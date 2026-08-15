<?php

namespace Database\Seeders;

use App\Models\Guru;
use Illuminate\Database\Seeder;

class GuruSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gurus = Guru::factory()->count(6)->create();

        foreach ($gurus as $index => $guru) {
            $guru->user()->create([
                'name' => $guru->nama_lengkap,
                'email' => uniqid('guru_').'@smkifsu.sch.id',
                'password' => 'password',
                'role' => 'guru',
            ]);
        }
    }
}
