<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penilaian extends Model
{
    use HasFactory;

    protected $table = 'penilaian';

    protected $casts = [
        'nilai_maks' => 'float',
        'bobot' => 'float',
        'aktif' => 'boolean',
    ];

    protected $fillable = [
        'nama',
        'deskripsi',
        'tipe',
        'nilai_maks',
        'bobot',
        'aktif',
        'sumber',
    ];

    /**
     * Kelas yang di‑assign ke penilaian ini (many‑to‑many).
     */
    public function kelas()
    {
        return $this->belongsToMany(Kelas::class, 'penilaian_kelas');
    }

    /**
     * Daftar detail nilai (per siswa).
     */
    public function detailPenilaian()
    {
        return $this->hasMany(DetailPenilaian::class);
    }
}
