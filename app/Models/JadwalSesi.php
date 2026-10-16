<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalSesi extends Model
{
    protected $table = 'cbt_jadwal_sesi';

    protected $fillable = [
        'jadwal_id',
        'nama',
        'jam_mulai',
        'jam_selesai',
        'ruang_ujian_id',
        'kapasitas',
        'terisi',
        'token',
        'token_released_at',
    ];

    protected $casts = [
        'token_released_at' => 'datetime',
    ];

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function ruang(): BelongsTo
    {
        return $this->belongsTo(RuangUjian::class, 'ruang_ujian_id');
    }
}
