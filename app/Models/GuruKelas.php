<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuruKelas extends Model
{
    /** @use HasFactory<\Database\Factories\GuruKelasFactory> */
    use HasFactory;

    protected $table = 'guru_kelas';

    protected $fillable = [
        'guru_id',
        'kelas_id',
        'tahun_ajaran_id',
        'aktif',
        'kode_undangan',
        'active_forum',
        'lihat_anggota_kelas',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'active_forum' => 'boolean',
        'lihat_anggota_kelas' => 'boolean',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class)->where('active', true);
    }
}
