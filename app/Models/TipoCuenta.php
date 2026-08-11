<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCuenta extends Model
{
    use HasFactory;

    protected $table = 'tipos_cuenta';

    protected $fillable = [
        'tipo_cuenta_id',
        'nombre',
        'cuenta_sap',
        'activo',
    ];
}