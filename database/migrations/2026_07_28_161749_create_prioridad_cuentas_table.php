<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrioridadCuentasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('prioridades_cuentas', function (Blueprint $table) {
            $table->id();
            
            // Relación con la tabla tipos_cuenta (Un solo tipo de cuenta por cada registro)
            $table->foreignId('tipo_cuenta_id')
                  ->constrained('tipos_cuenta')
                  ->onDelete('cascade')
                  ->unique(); // <-- Ahora el tipo_cuenta_id es único de forma directa

            $table->integer('prioridad')->default(1); // 1 = Máxima prioridad
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('prioridades_cuentas');
    }
}