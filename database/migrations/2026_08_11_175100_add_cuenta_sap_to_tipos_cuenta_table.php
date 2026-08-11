<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCuentaSapToTiposCuentaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tipos_cuenta', function (Blueprint $table) {
            // Agregamos la columna 'cuenta_sap'. 
            $table->string('cuenta_sap')->nullable()->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tipos_cuenta', function (Blueprint $table) {
            $table->dropColumn('cuenta_sap');
        });
    }
}