<?php

namespace Database\Seeders;

use App\Models\JamPelajaran;
use Illuminate\Database\Seeder;

class JamPelajaranSeeder extends Seeder
{
    public function run(): void
    {
        // Kosongkan data lama jika seeder dijalankan ulang (opsional)
        JamPelajaran::truncate();

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        foreach ($hariList as $hari) {
            $slots = [];

            // 1. Pola Khusus Hari SENIN (Ada Upacara Bendera, JP 1 mulai lebih siang)
            if ($hari === 'Senin') {
                $slots = [
                    ['label' => 'Upacara Bendera', 'jam_mulai' => '07:00', 'jam_selesai' => '07:45', 'is_break' => true, 'urutan' => 1],
                    ['label' => 'JP 1', 'jam_mulai' => '07:45', 'jam_selesai' => '08:30', 'is_break' => false, 'urutan' => 2],
                    ['label' => 'JP 2', 'jam_mulai' => '08:30', 'jam_selesai' => '09:15', 'is_break' => false, 'urutan' => 3],
                    ['label' => 'JP 3', 'jam_mulai' => '09:15', 'jam_selesai' => '10:00', 'is_break' => false, 'urutan' => 4],
                    ['label' => 'Istirahat 1', 'jam_mulai' => '10:00', 'jam_selesai' => '10:15', 'is_break' => true, 'urutan' => 5],
                    ['label' => 'JP 4', 'jam_mulai' => '10:15', 'jam_selesai' => '11:00', 'is_break' => false, 'urutan' => 6],
                    ['label' => 'JP 5', 'jam_mulai' => '11:00', 'jam_selesai' => '11:45', 'is_break' => false, 'urutan' => 7],
                    ['label' => 'Istirahat 2 / Ishoma', 'jam_mulai' => '11:45', 'jam_selesai' => '12:30', 'is_break' => true, 'urutan' => 8],
                    ['label' => 'JP 6', 'jam_mulai' => '12:30', 'jam_selesai' => '13:15', 'is_break' => false, 'urutan' => 9],
                    ['label' => 'JP 7', 'jam_mulai' => '13:15', 'jam_selesai' => '14:00', 'is_break' => false, 'urutan' => 10],
                    ['label' => 'JP 8', 'jam_mulai' => '14:00', 'jam_selesai' => '14:45', 'is_break' => false, 'urutan' => 11],
                ];
            } 
            // 2. Pola Standar Hari SELASA, RABU, KAMIS
            elseif (in_array($hari, ['Selasa', 'Rabu', 'Kamis'])) {
                $slots = [
                    ['label' => 'JP 1', 'jam_mulai' => '07:00', 'jam_selesai' => '07:45', 'is_break' => false, 'urutan' => 1],
                    ['label' => 'JP 2', 'jam_mulai' => '07:45', 'jam_selesai' => '08:30', 'is_break' => false, 'urutan' => 2],
                    ['label' => 'JP 3', 'jam_mulai' => '08:30', 'jam_selesai' => '09:15', 'is_break' => false, 'urutan' => 3],
                    ['label' => 'JP 4', 'jam_mulai' => '09:15', 'jam_selesai' => '10:00', 'is_break' => false, 'urutan' => 4],
                    ['label' => 'Istirahat 1', 'jam_mulai' => '10:00', 'jam_selesai' => '10:15', 'is_break' => true, 'urutan' => 5],
                    ['label' => 'JP 5', 'jam_mulai' => '10:15', 'jam_selesai' => '11:00', 'is_break' => false, 'urutan' => 6],
                    ['label' => 'JP 6', 'jam_mulai' => '11:00', 'jam_selesai' => '11:45', 'is_break' => false, 'urutan' => 7],
                    ['label' => 'Istirahat 2 / Ishoma', 'jam_mulai' => '11:45', 'jam_selesai' => '12:30', 'is_break' => true, 'urutan' => 8],
                    ['label' => 'JP 7', 'jam_mulai' => '12:30', 'jam_selesai' => '13:15', 'is_break' => false, 'urutan' => 9],
                    ['label' => 'JP 8', 'jam_mulai' => '13:15', 'jam_selesai' => '14:00', 'is_break' => false, 'urutan' => 10],
                    ['label' => 'JP 9', 'jam_mulai' => '14:00', 'jam_selesai' => '14:45', 'is_break' => false, 'urutan' => 11],
                ];
            } 
            // 3. Pola Khusus Hari JUMAT (Pulang lebih awal / persiapan Sholat Jumat)
            elseif ($hari === 'Jumat') {
                $slots = [
                    ['label' => 'JP 1', 'jam_mulai' => '07:00', 'jam_selesai' => '07:40', 'is_break' => false, 'urutan' => 1],
                    ['label' => 'JP 2', 'jam_mulai' => '07:40', 'jam_selesai' => '08:20', 'is_break' => false, 'urutan' => 2],
                    ['label' => 'JP 3', 'jam_mulai' => '08:20', 'jam_selesai' => '09:00', 'is_break' => false, 'urutan' => 3],
                    ['label' => 'Istirahat 1', 'jam_mulai' => '09:00', 'jam_selesai' => '09:20', 'is_break' => true, 'urutan' => 4],
                    ['label' => 'JP 4', 'jam_mulai' => '09:20', 'jam_selesai' => '10:00', 'is_break' => false, 'urutan' => 5],
                    ['label' => 'JP 5', 'jam_mulai' => '10:00', 'jam_selesai' => '10:40', 'is_break' => false, 'urutan' => 6],
                    ['label' => 'JP 6', 'jam_mulai' => '10:40', 'jam_selesai' => '11:20', 'is_break' => false, 'urutan' => 7],
                    ['label' => 'Istirahat / Persiapan Jumat', 'jam_mulai' => '11:20', 'jam_selesai' => '13:00', 'is_break' => true, 'urutan' => 8],
                ];
            } 
            // 4. Pola Khusus Hari SABTU (Biasanya setengah hari / Kegiatan Ekstrakurikuler)
            elseif ($hari === 'Sabtu') {
                $slots = [
                    ['label' => 'JP 1', 'jam_mulai' => '07:00', 'jam_selesai' => '07:45', 'is_break' => false, 'urutan' => 1],
                    ['label' => 'JP 2', 'jam_mulai' => '07:45', 'jam_selesai' => '08:30', 'is_break' => false, 'urutan' => 2],
                    ['label' => 'JP 3', 'jam_mulai' => '08:30', 'jam_selesai' => '09:15', 'is_break' => false, 'urutan' => 3],
                    ['label' => 'Istirahat', 'jam_mulai' => '09:15', 'jam_selesai' => '09:30', 'is_break' => true, 'urutan' => 4],
                    ['label' => 'JP 4', 'jam_mulai' => '09:30', 'jam_selesai' => '10:15', 'is_break' => false, 'urutan' => 5],
                    ['label' => 'JP 5', 'jam_mulai' => '10:15', 'jam_selesai' => '11:00', 'is_break' => false, 'urutan' => 6],
                    ['label' => 'JP 6', 'jam_mulai' => '11:00', 'jam_selesai' => '11:45', 'is_break' => false, 'urutan' => 7],
                ];
            }

            // Masukkan data ke database dengan menyertakan kolom 'hari'
            foreach ($slots as $slot) {
                JamPelajaran::create([
                    'label' => $slot['label'],
                    'hari' => $hari,
                    'jam_mulai' => $slot['jam_mulai'],
                    'jam_selesai' => $slot['jam_selesai'],
                    'is_break' => $slot['is_break'],
                    'urutan' => $slot['urutan'],
                ]);
            }
        }
    }
}