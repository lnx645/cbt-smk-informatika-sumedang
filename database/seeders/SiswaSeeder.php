<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jurusans = Jurusan::all();
        $kodeMap = $jurusans->keyBy('kode');

        $kelases = Kelas::all();

        foreach ($kelases as $kelas) {
            $kode = trim(Str::after($kelas->nama, ' '));
            $jurusan = $kodeMap->get($kode);

            $jumlah = rand(4, 8);

            for ($i = 0; $i < $jumlah; $i++) {
                $siswa = Siswa::factory()->create([
                    'kelas_id' => $kelas->id,
                    'guru_id' => $kelas->guru_id,
                    'jurusan_id' => $jurusan?->id,
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
