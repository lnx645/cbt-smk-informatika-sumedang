<?php

namespace App\Models;

use Database\Factories\SiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

#[Table('siswa')]
#[Fillable(['nisn', 'nis', 'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'foto_profil', 'is_aktif', 'status'])]
class Siswa extends Model
{
    /** @use HasFactory<SiswaFactory> */
    use HasFactory;

    protected $primaryKey = 'nisn';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'status' => 'string',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'nisn', 'nisn');
    }

    public function siswaKelas(): HasMany
    {
        return $this->hasMany(SiswaKelas::class, 'siswa_nisn', 'nisn');
    }

    public function kelas(): HasOneThrough
    {
        return $this->hasOneThrough(Kelas::class, SiswaKelas::class, 'siswa_nisn', 'id', 'nisn', 'kelas_id')
            ->where('siswa_kelas.active', true);
    }
}
