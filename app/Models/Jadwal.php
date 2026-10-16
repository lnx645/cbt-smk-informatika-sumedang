<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jadwal extends Model
{
    protected $table = 'cbt_jadwals';

    protected $fillable = [
        'event_id',
        'kelompok_bank_soal_id',
        'nama',
        'tanggal',
        'durasi_menit',
        'acak_soal',
        'acak_opsi',
        'butuh_token',
        'tampilkan_hasil',
        'status',
        'mode',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'acak_soal' => 'boolean',
        'acak_opsi' => 'boolean',
        'butuh_token' => 'boolean',
        'tampilkan_hasil' => 'boolean',
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
        return $this->belongsToMany(Kelas::class, 'cbt_jadwal_kelas');
    }

    public function sesi(): HasMany
    {
        return $this->hasMany(JadwalSesi::class);
    }
}
