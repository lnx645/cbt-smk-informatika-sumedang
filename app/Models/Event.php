<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'cbt_events';
    protected $fillable = [
        'nama', 'kategori', 'deskripsi',
        'tanggal_mulai', 'tanggal_selesai', 'status',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public const KATEGORI = ['uts', 'uas', 'usbk'];
    public const STATUS = ['draft', 'aktif', 'selesai'];
}
