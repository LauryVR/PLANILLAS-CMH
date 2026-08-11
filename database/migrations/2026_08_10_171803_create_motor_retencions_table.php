<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMotorRetencionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::create('motores_retencion', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ente_retencion_id'); // Relación con tu tabla actual
        $table->string('nombre_motor'); 
        $table->boolean('activo')->default(true);
        $table->timestamps();

        $table->foreign('ente_retencion_id')->references('id')->on('entes_retencion')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('motor_retencions');
    }
}
