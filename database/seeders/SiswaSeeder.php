<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use Illuminate\Database\Seeder;

class SiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kelases = Kelas::all();

        foreach ($kelases as $kelas) {
            $jumlah = rand(4, 8);

            for ($i = 0; $i < $jumlah; $i++) {
                $siswa = Siswa::factory()->create([
                    'guru_id' => $kelas->guru_id,
                ]);

                SiswaKelas::create([
                    'siswa_nisn' => $siswa->nisn,
                    'kelas_id' => $kelas->id,
                    'active' => true,
                ]);

                $siswa->user()->create([
                    'name' => $siswa->nama_lengkap,
                    'email' => $siswa->nisn.'@smkifsu.sch.id',
                    'password' => 'password',
                    'role' => 'siswa',
                ]);
            }
        }
    }
}
