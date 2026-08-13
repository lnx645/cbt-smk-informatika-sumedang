<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'totalStudents' => 482,
                'activeExams' => 3,
                'avgScore' => 78.4,
                'completedExams' => 12,
            ],
            'examProgress' => [
                ['subject' => 'Matematika Wajib', 'schedule' => '12 Agu 2026, 08:00', 'progress' => 68, 'participants' => 320],
                ['subject' => 'Bahasa Indonesia', 'schedule' => '13 Agu 2026, 08:00', 'progress' => 45, 'participants' => 482],
                ['subject' => 'Bahasa Inggris', 'schedule' => '14 Agu 2026, 10:00', 'progress' => 12, 'participants' => 418],
            ],
            'recentResults' => [
                ['student' => 'Aisyah Putri Ramadhani', 'subject' => 'Matematika Wajib', 'score' => 92, 'status' => 'Lulus'],
                ['student' => 'Budi Santoso', 'subject' => 'Matematika Wajib', 'score' => 85, 'status' => 'Lulus'],
                ['student' => 'Citra Ayu Lestari', 'subject' => 'Bahasa Indonesia', 'score' => 76, 'status' => 'Lulus'],
                ['student' => 'Dimas Prasetyo', 'subject' => 'Matematika Wajib', 'score' => 54, 'status' => 'Belum Tuntas'],
                ['student' => 'Eka Fitriani', 'subject' => 'Bahasa Indonesia', 'score' => 88, 'status' => 'Lulus'],
            ],
        ]);
    }
}
