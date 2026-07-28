<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrioridadCuenta extends Model
{
    use HasFactory;

    protected $table = 'prioridades_cuentas';

    protected $fillable = [
        'tipo_cuenta_id',
        'prioridad',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'prioridad' => 'integer',
    ];

    // Relación pertenencia a TipoCuenta
    public function tipoCuenta()
    {
        // ✅ CORRECCIÓN: Se cambió $table por $this
        return $this->belongsTo(TipoCuenta::class, 'tipo_cuenta_id');
    }
}