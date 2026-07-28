<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_cuenta', function (Blueprint $table) {
            $table->id(); // ID autoincremental del registro en la BD
            $table->unsignedBigInteger('tipo_cuenta_id')->unique(); // ID / Código numérico del Tipo de Cuenta
            $table->string('nombre')->unique();                     // Ej: CUOTA COLEGIAL
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_cuenta');
    }
};