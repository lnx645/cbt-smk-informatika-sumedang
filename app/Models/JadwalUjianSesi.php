<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalUjianSesi extends Model
{
    protected $table = 'jadwal_ujian_sesis';

    protected $fillable = [
        'jadwal_ujian_id',
        'nama',
        'urutan',
        'jam_mulai',
        'jam_selesai',
        'ruang_ujian_id',
        'kapasitas',
        'terisi',
        'token',
        'token_released_at',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'kapasitas' => 'integer',
        'terisi' => 'integer',
        'token_released_at' => 'datetime',
    ];

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalUjian::class, 'jadwal_ujian_id');
    }

    public function ruang(): BelongsTo
    {
        return $this->belongsTo(RuangUjian::class, 'ruang_ujian_id');
    }
}
