<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleMotorConfig extends Model
{
    use HasFactory;

protected $fillable = [
    'motor_retencion_id',
    'dni',
    'colegiado_nombre', // <--- IMPORTANTE: Debe coincidir con la columna de la tabla detalle
    'numero_colegiado',
    'cuota_colegial',
    'automaticos',
    'estudio',
    'refinanciamiento',
    'readecuacion',
    'personal',
    'compra_deuda',
    'hipotecario',
    'vehiculo',
];
}