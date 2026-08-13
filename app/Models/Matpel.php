<?php

namespace App\Models;

use Database\Factories\MatpelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description'])]
class Matpel extends Model
{
    /** @use HasFactory<MatpelFactory> */
    use HasFactory;
}
