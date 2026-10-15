<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CBT v2: Kelompok Bank Soal — wadah bernama berisi kumpulan soal.
 *
 * Alur: guru buat bank (ketik nama di modal) -> isi soal ke dalamnya ->
 * soal dipakai untuk generate paket ujian.
 */
class KelompokBankSoal extends Model
{
    protected $fillable = ['nama', 'deskripsi', 'matpel_id', 'guru_id'];

    public function matpel(): BelongsTo
    {
        return $this->belongsTo(Matpel::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function soals(): HasMany
    {
        return $this->hasMany(BankSoal::class)->orderBy('id');
    }
}
