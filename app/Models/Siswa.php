<?php

namespace App\Models;

use Database\Factories\SiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
#[Table("siswa")]
class Siswa extends Model
{
    /** @use HasFactory<SiswaFactory> */
    use HasFactory;

    protected $primaryKey = 'nisn';

    public $incrementing = false;

    protected $keyType = 'string';

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'nisn', 'nisn');
    }
}
