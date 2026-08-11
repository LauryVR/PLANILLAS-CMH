<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotorRetencion extends Model
{
    use HasFactory;

    // Define explícitamente el nombre de la tabla en tu base de datos
    protected $table = 'motores_retencion';

    protected $fillable = [
        'ente_retencion_id',
        'nombre_motor',
        'activo'
    ];

    // Relación opcional con el Ente de Retención
    public function enteRetencion()
    {
        return $this->belongsTo(EnteRetencion::class, 'ente_retencion_id');
    }
}