<?php

namespace App\Models;

use Database\Factories\JurusanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'kode'])]
class Jurusan extends Model
{
    /** @use HasFactory<JurusanFactory> */
    use HasFactory;

    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }
    public function kelas(): HasMany
    {
        return $this->hasMany(Jurusan::class);
    }
}
