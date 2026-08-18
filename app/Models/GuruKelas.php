<?php

namespace App\Models;

use Database\Factories\GuruKelasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuruKelas extends Model
{
    /** @use HasFactory<GuruKelasFactory> */
    use HasFactory;

    protected $table = 'guru_kelas';

    protected $fillable = [
        'guru_id',
        'kelas_id',
        'matpel_id',
        'tahun_ajaran_id',
        'aktif',
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

    public function matpel(): BelongsTo
    {
        return $this->belongsTo(Matpel::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }
}
