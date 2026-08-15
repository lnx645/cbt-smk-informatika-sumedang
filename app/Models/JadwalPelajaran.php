<?php

namespace App\Models;

use Database\Factories\JadwalPelajaranFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalPelajaran extends Model
{
    /** @use HasFactory<JadwalPelajaranFactory> */
    use HasFactory;

    protected $fillable = [
        'guru_id',
        'matpel_id',
        'kelas_id',
        'jam_pelajaran_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function matpel(): BelongsTo
    {
        return $this->belongsTo(Matpel::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function jamPelajaran(): BelongsTo
    {
        return $this->belongsTo(JamPelajaran::class);
    }
}
