<?php

namespace App\Models;

use Database\Factories\TugasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Table('tugases')]
#[Fillable(['guru_id', 'guru_kelas_id', 'judul', 'deskripsi', 'tanggal_terbit', 'deadline', 'jenis_pengumpulan', 'file_path', 'file_name', 'file_size', 'mime_type', 'poin', 'penilaian_id'])]
class Tugas extends Model
{
    /** @use HasFactory<TugasFactory> */
    use HasFactory;

    protected $casts = [
        'tanggal_terbit' => 'datetime',
        'deadline' => 'datetime',
        'poin' => 'integer',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function guruKelas(): BelongsTo
    {
        return $this->belongsTo(GuruKelas::class);
    }

    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(Penilaian::class);
    }

    public function pengumpulans(): HasMany
    {
        return $this->hasMany(TugasPengumpulan::class);
    }

    /**
     * Tugas sudah bisa dilihat siswa (tanggal terbit tiba atau tidak diatur).
     */
    public function sudahTerbit(): bool
    {
        return $this->tanggal_terbit === null || $this->tanggal_terbit->lte(Carbon::now());
    }

    /**
     * Batas waktu pengumpulan sudah lewat.
     */
    public function sudahLewatDeadline(): bool
    {
        return $this->deadline !== null && $this->deadline->lt(Carbon::now());
    }
}
