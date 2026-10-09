<?php

namespace App\Models;

use Database\Factories\JawabanSiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('jawaban_siswas')]
class JawabanSiswa extends Model
{
    /** @use HasFactory<JawabanSiswaFactory> */
    use HasFactory;

    protected $fillable = [
        'ujian_pengerjaan_id',
        'soal_id',
        'opsi_dipilih',
        'jawaban_teks',
        'benar',
        'skor',
    ];

    protected $casts = [
        'opsi_dipilih' => 'array',
        'benar' => 'boolean',
        'skor' => 'float',
    ];

    public function pengerjaan(): BelongsTo
    {
        return $this->belongsTo(UjianPengerjaan::class, 'ujian_pengerjaan_id');
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
