<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Maestro extends Model
{
    use HasFactory;

    protected $table = 'maestros';

    protected $fillable = [
        'nombre',
        'dni',
        'no_colegiado'
    ];
}
