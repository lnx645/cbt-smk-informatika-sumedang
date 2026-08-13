<?php

namespace App\Models;

use Database\Factories\GuruFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guru extends Model
{
    /** @use HasFactory<GuruFactory> */
    use HasFactory;

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'guru_id');
    }
}
