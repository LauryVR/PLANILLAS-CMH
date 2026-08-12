<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUpdatedByToDetalleMotorConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::table('detalle_motor_configs', function (Blueprint $table) {
        $table->unsignedBigInteger('updated_by')->nullable()->after('updated_at');
        // Opcional: si quieres una clave foránea real
        // $table->foreign('updated_by')->references('id')->on('users');
    });
}

public function down()
{
    Schema::table('detalle_motor_configs', function (Blueprint $table) {
        $table->dropColumn('updated_by');
    });
}
}
