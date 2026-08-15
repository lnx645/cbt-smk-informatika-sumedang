<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JamPelajaran;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class JamPelajaranController extends Controller
{
    public function index(Request $request): Response
    {

        $queryJp = JamPelajaran::query();

        $filter = $request->query('is_break');

        if ($filter != '') {
            $queryJp->whereIsBreak($filter == '1' ? true : false);
        }
        $search = $request->query('search');
        if ($search) {
            $queryJp->whereLabel($search);
        }
        $jpList = $queryJp->orderBy('urutan')->paginate(20);

        return Inertia::render('admin/JamPelajaran/Index', [
            'jpList' => [
                'data' => $jpList->map(function (JamPelajaran $jp) {
                    return [
                        'id' => $jp->id,
                        'label' => $jp->label,
                        'jam_mulai' => $jp->mulai,
                        'jam_selesai' => $jp->selesai,
                        'is_break' => $jp->is_break,
                        'urutan' => $jp->urutan,
                    ];
                })->all(),
                'current_page' => $jpList->currentPage(),
                'last_page' => $jpList->lastPage(),
                'total' => $jpList->total(),
                'per_page' => $jpList->perPage(),
                'from' => $jpList->firstItem(),
                'to' => $jpList->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'is_break' => ['boolean'],
            'urutan' => ['required', 'integer', 'min:0'],
        ]);

        JamPelajaran::create($data);

        Toast::success('Jam pelajaran berhasil ditambahkan.');

        return Redirect::back();
    }

    public function update(Request $request, JamPelajaran $jamPelajaran): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'is_break' => ['boolean'],
            'urutan' => ['required', 'integer', 'min:0'],
        ]);

        $jamPelajaran->update($data);

        Toast::success('Jam pelajaran berhasil diperbarui.');

        return Redirect::back();
    }

    public function destroy(JamPelajaran $jamPelajaran): RedirectResponse
    {
        $jamPelajaran->delete();

        Toast::success('Jam pelajaran berhasil dihapus.');

        return Redirect::back();
    }
}
