<?php

namespace App\Models;

use Database\Factories\GuruFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['nip', 'nama_lengkap', 'pendidikan_terakhir', 'jenis_kelamin', 'alamat', 'foto_profil', 'is_aktif'])]
class Guru extends Model
{
    /** @use HasFactory<GuruFactory> */
    use HasFactory;

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'guru_id');
    }

    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class, 'guru_id');
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    public function walikelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'guru_id');
    }

    public function guruKelas(): HasMany
    {
        return $this->hasMany(GuruKelas::class);
    }
}
