<?php

namespace Database\Seeders;

use App\Models\JamPelajaran;
use Illuminate\Database\Seeder;

class JamPelajaranSeeder extends Seeder
{
    public function run(): void
    {
        $slots = [
            ['label' => 'JP 1', 'jam_mulai' => '07:30', 'jam_selesai' => '08:15', 'is_break' => false, 'urutan' => 1],
            ['label' => 'JP 2', 'jam_mulai' => '08:15', 'jam_selesai' => '09:00', 'is_break' => false, 'urutan' => 2],
            ['label' => 'JP 3', 'jam_mulai' => '09:00', 'jam_selesai' => '09:45', 'is_break' => false, 'urutan' => 3],
            ['label' => 'JP 4', 'jam_mulai' => '09:45', 'jam_selesai' => '10:15', 'is_break' => false, 'urutan' => 4],
            ['label' => 'Istirahat 1', 'jam_mulai' => '10:15', 'jam_selesai' => '10:30', 'is_break' => true, 'urutan' => 5],
            ['label' => 'JP 5', 'jam_mulai' => '10:45', 'jam_selesai' => '11:30', 'is_break' => false, 'urutan' => 6],
            ['label' => 'JP 6', 'jam_mulai' => '11:30', 'jam_selesai' => '12:15', 'is_break' => false, 'urutan' => 7],
            ['label' => 'Istirahat 2', 'jam_mulai' => '12:15', 'jam_selesai' => '12:45', 'is_break' => true, 'urutan' => 8],
            ['label' => 'JP 7', 'jam_mulai' => '12:45', 'jam_selesai' => '13:30', 'is_break' => false, 'urutan' => 9],
            ['label' => 'JP 8', 'jam_mulai' => '13:30', 'jam_selesai' => '14:15', 'is_break' => false, 'urutan' => 10],
            ['label' => 'JP 9', 'jam_mulai' => '14:15', 'jam_selesai' => '14:45', 'is_break' => false, 'urutan' => 11],
            ['label' => 'JP 10', 'jam_mulai' => '14:45', 'jam_selesai' => '15:25', 'is_break' => false, 'urutan' => 12],
        ];

        foreach ($slots as $slot) {
            JamPelajaran::create($slot);
        }
    }
}