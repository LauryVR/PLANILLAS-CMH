<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnteRetencion extends Model
{
    use HasFactory;

    protected $table = 'entes_retencion';

    protected $fillable = [
        'ente_retencion_id',
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}