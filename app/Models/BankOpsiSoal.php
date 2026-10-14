<?php

namespace App\Models;

use Database\Factories\BankOpsiSoalFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('bank_opsi_soals')]
class BankOpsiSoal extends Model
{
    /** @use HasFactory<BankOpsiSoalFactory> */
    use HasFactory;

    protected $fillable = [
        'bank_soal_id',
        'teks',
        'benar',
        'urutan',
    ];

    protected $casts = [
        'benar' => 'boolean',
        'urutan' => 'integer',
    ];

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class);
    }
}
