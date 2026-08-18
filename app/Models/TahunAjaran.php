<?php

namespace App\Models;

use Database\Factories\TahunAjaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Table('tahun_ajaran')]
#[Fillable(['name', 'active'])]
class TahunAjaran extends Model
{
    /** @use HasFactory<TahunAjaranFactory> */
    use HasFactory;

    protected $casts = [
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('tahun-ajaran-aktif'));
        static::deleted(fn () => Cache::forget('tahun-ajaran-aktif'));
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function guruKelas(): HasMany
    {
        return $this->hasMany(GuruKelas::class);
    }
}
