<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use Illuminate\Database\Seeder;

class JurusanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => 'Rekayasa Perangkat Lunak', 'kode' => 'RPL'],
            ['name' => 'Teknik Komputer & Jaringan', 'kode' => 'TKJ'],
            ['name' => 'Teknik Kendaraan Ringan', 'kode' => 'TKR'],
            ['name' => 'Bisnis Daring & Pemasaran', 'kode' => 'BDP'],
            ['name' => 'Akuntansi', 'kode' => 'AKT'],
        ];

        foreach ($data as $item) {
            Jurusan::firstOrCreate(['kode' => $item['kode']], $item);
        }
    }
}
