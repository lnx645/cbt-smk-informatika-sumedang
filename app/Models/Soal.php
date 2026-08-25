<?php

namespace App\Models;

use Database\Factories\SoalFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('soals')]
class Soal extends Model
{
    /** @use HasFactory<SoalFactory> */
    use HasFactory;

    protected $fillable = [
        'ujian_id',
        'bank_soal_id',
        'tipe',
        'pertanyaan',
        'poin',
        'urutan',
        'kunci_isian',
        'isian_case_sensitive',
    ];

    protected $casts = [
        'poin' => 'integer',
        'urutan' => 'integer',
        'kunci_isian' => 'array',
        'isian_case_sensitive' => 'boolean',
    ];

    /**
     * Tipe soal yang dinilai otomatis (objektif).
     *
     * @var array<int, string>
     */
    public const TIPE_OBJEKTIF = ['pg', 'multi', 'benar_salah', 'isian'];

    public const TIPE = ['pg', 'multi', 'benar_salah', 'isian', 'esai'];

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class);
    }

    public function opsi(): HasMany
    {
        return $this->hasMany(OpsiSoal::class)->orderBy('urutan')->orderBy('id');
    }

    public function objektif(): bool
    {
        return in_array($this->tipe, self::TIPE_OBJEKTIF, true);
    }

    public function butuhOpsi(): bool
    {
        return in_array($this->tipe, ['pg', 'multi', 'benar_salah'], true);
    }
}
