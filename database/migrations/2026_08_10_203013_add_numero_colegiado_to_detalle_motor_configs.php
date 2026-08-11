<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNumeroColegiadoToDetalleMotorConfigs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
  public function up()
{
    Schema::table('detalle_motor_configs', function (Blueprint $table) {
        $table->string('numero_colegiado')->nullable()->after('colegiado_nombre');
    });
}

public function down()
{
    Schema::table('detalle_motor_configs', function (Blueprint $table) {
        $table->dropColumn('numero_colegiado');
    });
}}