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

        foreach (['X', 'XI', 'XII'] as $tingkat) {
            $root = Kelas::factory()->create([
                'nama' => $tingkat,
                'tingkat' => $tingkat,
                'guru_id' => null,
                'jurusan_id' => null,
                'parent_id' => null,
                'active' => true,
            ]);

            foreach ($jurusans as $jurusan) {
                $parent = Kelas::factory()->create([
                    'nama' => $tingkat.'-'.$jurusan->kode,
                    'guru_id' => $gurus->random()->id,
                    'jurusan_id' => $jurusan->id,
                    'parent_id' => $root->id,
                    'active' => true,
                ]);

                foreach ([1, 2] as $nomor) {
                    Kelas::factory()->create([
                        'nama' => $parent->nama.'-'.$nomor,
                        'guru_id' => $gurus->random()->id,
                        'jurusan_id' => $jurusan->id,
                        'parent_id' => $parent->id,
                        'active' => true,
                    ]);
                }
            }
        }
    }
}
