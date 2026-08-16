<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class SiswaController extends Controller
{
    /**
     * Display a listing of the peserta didik.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $jenisKelamin = $request->query('jenis_kelamin');
        $isAktif = $request->query('is_aktif');
        $punyaKelas = $request->query('punya_kelas');
        $punyaAkun = $request->query('punya_akun');

        $query = Siswa::query()->with('user', 'kelas');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }
        if (in_array($jenisKelamin, ['L', 'P'], true)) {
            $query->where('jenis_kelamin', $jenisKelamin);
        }
        if ($isAktif === '1') {
            $query->where('is_aktif', true);
        } elseif ($isAktif === '0') {
            $query->where('is_aktif', false);
        }
        if ($punyaKelas === '1') {
            $query->has('siswaKelas');
        } elseif ($punyaKelas === '0') {
            $query->doesntHave('siswaKelas');
        }
        if ($punyaAkun === '1') {
            $query->has('user');
        } elseif ($punyaAkun === '0') {
            $query->doesntHave('user');
        }

        $siswa = $query->orderBy('nama_lengkap')
            ->paginate(10)
            ->through(fn (Siswa $item) => [
                'id' => $item->nisn,
                'nisn' => $item->nisn,
                'nis' => $item->nis,
                'nama_lengkap' => $item->nama_lengkap,
                'tempat_lahir' => $item->tempat_lahir,
                'tanggal_lahir' => $item->tanggal_lahir,
                'jenis_kelamin' => $item->jenis_kelamin,
                'alamat' => $item->alamat,
                'foto_profil' => $item->foto_profil,
                'is_aktif' => $item->is_aktif,
                'kelas' => $item->kelas ? [
                    'id' => $item->kelas->id,
                    'nama' => $item->kelas->nama,
                ] : null,
                'punya_akun' => $item->user !== null,
            ]);

        $jenisKelaminOptions = [
            ['value' => 'L', 'label' => 'Laki-laki'],
            ['value' => 'P', 'label' => 'Perempuan'],
        ];

        return Inertia::render('admin/Siswa/Index', [
            'siswa' => Inertia::merge($siswa),
            'filters' => [
                'search' => $search ?? '',
                'jenis_kelamin' => $jenisKelamin ?? '',
                'is_aktif' => $isAktif ?? '',
                'punya_kelas' => $punyaKelas ?? '',
                'punya_akun' => $punyaAkun ?? '',
            ],
            'jenisKelaminOptions' => $jenisKelaminOptions,
        ]);
    }

    /**
     * Store a newly created peserta didik in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateSiswa($request);

        if ($request->hasFile('foto_profil')) {
            $data['foto_profil'] = $request->file('foto_profil')->store('siswa', 'public');
        }

        Siswa::create($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Peserta didik berhasil ditambahkan.',
        ]);

        return Redirect::route('admin.siswa.index');
    }

    /**
     * Update the specified peserta didik in storage.
     */
    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $data = $this->validateSiswa($request, $siswa);

        if ($request->hasFile('foto_profil')) {
            $data['foto_profil'] = $request->file('foto_profil')->store('siswa', 'public');
        }

        $siswa->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Peserta didik berhasil diperbarui.',
        ]);

        return Redirect::route('admin.siswa.index');
    }

    /**
     * Remove the specified peserta didik from storage.
     */
    public function destroy(Siswa $siswa): RedirectResponse
    {
        if ($siswa->user || $siswa->siswaKelas()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Peserta didik tidak dapat dihapus karena masih memiliki akun atau kelas terkait.',
            ]);

            return Redirect::route('admin.siswa.index');
        }

        $siswa->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Peserta didik berhasil dihapus.',
        ]);

        return Redirect::route('admin.siswa.index');
    }

    /**
     * Validate the peserta didik request for store/update.
     */
    private function validateSiswa(Request $request, ?Siswa $current = null): array
    {
        return $request->validate([
            'nisn' => ['required', 'string', 'max:10', 'unique:siswa,nisn'.($current ? ','.$current->nisn.',nisn' : '')],
            'nis' => ['required', 'string', 'max:10', 'unique:siswa,nis'.($current ? ','.$current->nis.',nis' : '')],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'alamat' => ['nullable', 'string'],
            'is_aktif' => ['boolean'],
        ], [], [
            'nisn' => 'NISN',
            'nis' => 'NIS',
            'nama_lengkap' => 'Nama Lengkap',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'alamat' => 'Alamat',
            'is_aktif' => 'Aktif',
        ]);
    }
}
