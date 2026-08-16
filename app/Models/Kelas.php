<?php

namespace App\Models;

use Database\Factories\KelasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['nama', 'deskripsi', 'guru_id', 'active', 'parent_id', 'jurusan_id', 'ruangan'])]
class Kelas extends Model
{
    /** @use HasFactory<KelasFactory> */
    use HasFactory;

    public function walikelas(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function siswas(): HasManyThrough
    {
        return $this->hasManyThrough(Siswa::class, SiswaKelas::class, 'kelas_id', 'nisn', 'id', 'siswa_nisn');
    }

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function guruKelas(): HasMany
    {
        return $this->hasMany(GuruKelas::class);
    }
}
