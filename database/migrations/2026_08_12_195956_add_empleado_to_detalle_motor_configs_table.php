<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmpleadoToDetalleMotorConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::table('detalle_motor_configs', function (Blueprint $table) {
        $table->string('empleado')->nullable()->after('vehiculo'); 
    });
}

public function down()
{
    Schema::table('detalle_motor_configs', function (Blueprint $table) {
        $table->dropColumn('empleado');
    });
}
}
