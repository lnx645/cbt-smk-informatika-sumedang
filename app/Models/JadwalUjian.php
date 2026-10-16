<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalUjian extends Model
{
    protected $table = 'jadwal_ujians';

    protected $fillable = [
        'event_id',
        'kelompok_bank_soal_id',
        'nama',
        'tanggal',
        'durasi_menit',
        'mode',
        'status',
        'acak_soal',
        'acak_opsi',
        'aktifkan_token',
        'tampilkan_nilai',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'durasi_menit' => 'integer',
        'acak_soal' => 'boolean',
        'acak_opsi' => 'boolean',
        'aktifkan_token' => 'boolean',
        'tampilkan_nilai' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function banksoal(): BelongsTo
    {
        return $this->belongsTo(KelompokBankSoal::class, 'kelompok_bank_soal_id');
    }

    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'jadwal_ujian_kelas', 'jadwal_ujian_id', 'kelas_id');
    }

    public function sesi(): HasMany
    {
        return $this->hasMany(JadwalUjianSesi::class, 'jadwal_ujian_id')->orderBy('urutan');
    }
}
