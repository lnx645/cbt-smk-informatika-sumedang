<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['label', 'jam_mulai',"hari", 'jam_selesai', 'is_break', 'urutan'])]
class JamPelajaran extends Model
{
    use HasFactory;

    protected $casts = [
        'is_break' => 'boolean',
    ];

    public function getMulaiAttribute(): string
    {
        return $this->jam_mulai ? Carbon::parse($this->jam_mulai)->format('H:i') : '';
    }

    public function getSelesaiAttribute(): string
    {
        return $this->jam_selesai ? Carbon::parse($this->jam_selesai)->format('H:i') : '';
    }

    public function jadwalPelajarans(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class);
    }
}