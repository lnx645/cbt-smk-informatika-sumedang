<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jurusans = Jurusan::all();
        $gurus = Guru::all();

        foreach ($jurusans as $jurusan) {
            foreach (['X', 'XI', 'XII'] as $tingkat) {
                Kelas::factory()->create([
                    'nama' => $tingkat.' '.$jurusan->kode,
                    'guru_id' => $gurus->random()->id,
                    'jurusan_id' => $jurusan->id,
                    'active' => true,
                ]);
            }
        }
    }
}
