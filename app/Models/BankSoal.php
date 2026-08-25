<?php

namespace App\Models;

use Database\Factories\BankSoalFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('bank_soals')]
class BankSoal extends Model
{
    /** @use HasFactory<BankSoalFactory> */
    use HasFactory;

    protected $fillable = [
        'matpel_id',
        'guru_id',
        'tipe',
        'pertanyaan',
        'poin',
        'topik',
        'kesulitan',
        'kunci_isian',
        'isian_case_sensitive',
    ];

    protected $casts = [
        'poin' => 'integer',
        'kunci_isian' => 'array',
        'isian_case_sensitive' => 'boolean',
    ];

    public const KESULITAN = ['mudah', 'sedang', 'sulit'];

    public function matpel(): BelongsTo
    {
        return $this->belongsTo(Matpel::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function opsi(): HasMany
    {
        return $this->hasMany(BankOpsiSoal::class)->orderBy('urutan')->orderBy('id');
    }

    public function butuhOpsi(): bool
    {
        return in_array($this->tipe, ['pg', 'multi', 'benar_salah'], true);
    }

    /**
     * @param  array<int, int>  $matpelIds
     */
    public function scopeUntukMatpel(Builder $query, array $matpelIds): Builder
    {
        return $query->whereIn('matpel_id', $matpelIds);
    }
}
