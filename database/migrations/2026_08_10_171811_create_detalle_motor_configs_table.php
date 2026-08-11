<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetalleMotorConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::create('detalle_motor_configs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('motor_retencion_id');
        $table->string('dni');
        $table->string('colegiado_nombre');
        $table->tinyInteger('cuota_colegial')->default(0);
        $table->tinyInteger('automaticos')->default(0);
        $table->tinyInteger('estudio')->default(0);
        $table->tinyInteger('refinanciamiento')->default(0);
        $table->tinyInteger('readecuacion')->default(0);
        $table->tinyInteger('personal')->default(0);
        $table->tinyInteger('compra_deuda')->default(0);
        $table->tinyInteger('hipotecario')->default(0);
        $table->tinyInteger('vehiculo')->default(0);
        $table->timestamps();

        $table->foreign('motor_retencion_id')->references('id')->on('motores_retencion')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detalle_motor_configs');
    }
}
