<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPenilaian extends Model
{
    use HasFactory;

    protected $table = 'detail_penilaian';

    protected $fillable = [
        'penilaian_id',
        'siswa_nisn',
        'guru_id',
        'nilai',
        'sumber',
        'keterangan',
    ];

    /**
     * Penilaian (assessment) this detail belongs to.
     */
    public function penilaian()
    {
        return $this->belongsTo(Penilaian::class);
    }

    /**
     * Siswa (student) this score belongs to.
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_nisn', 'nisn');
    }

    /**
     * Guru (teacher) who entered the score, if any.
     */
    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }
}
