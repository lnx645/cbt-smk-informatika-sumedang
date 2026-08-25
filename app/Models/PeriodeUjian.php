<?php

namespace App\Models;

use Database\Factories\PeriodeUjianFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('periode_ujians')]
class PeriodeUjian extends Model
{
    /** @use HasFactory<PeriodeUjianFactory> */
    use HasFactory;

    protected $fillable = [
        'kategori',
        'tahun_ajaran_id',
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    protected $casts = [
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
    ];

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }
}
