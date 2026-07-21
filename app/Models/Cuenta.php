<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaPorCobrar extends Model
{
    use HasFactory;

    // Especifica el nombre real de tu tabla si es distinto al predeterminado
    protected $table = 'nombre_de_tu_tabla_real'; 

    protected $fillable = [
        'dni',
        'nombre',
        'cuenta',
        'concepto',
        'valor_concepto',
    ];
}