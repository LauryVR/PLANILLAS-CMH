<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\MaestroController;
use App\Http\Controllers\CuentaController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('inicio');
});



Route::get('/maestros', [MaestroController::class, 'index'])
    ->name('maestros.index');

Route::put('/maestros/{id}', [MaestroController::class, 'update'])
    ->name('maestros.update');


    

Route::get('/excel', [ExcelController::class, 'index'])
    ->name('excel.index');
Route::get('/excel/cargar', [ExcelController::class, 'index'])->name('excel.index');
Route::post('/excel/cargar', [ExcelController::class, 'cargar'])->name('excel.cargar');
Route::post('/excel/guardar', [ExcelController::class, 'guardarBD'])->name('excel.guardar');
Route::post('/excel/guardar', [ExcelController::class, 'guardarBD'])->name('excel.guardar');
// Ruta para ver la vista principal / subir Excel
Route::get('/cuentas', [CuentaController::class, 'index'])->name('cuentas.index');

// Ruta POST para procesar el Excel cargado (NOMBRES OBLIGATORIOS)
Route::post('/cuentas/cargar', [CuentaController::class, 'cargarExcel'])->name('cuentas.cargar');

// Ruta POST para guardar los datos finales en la base de datos
Route::post('/cuentas/guardar', [CuentaController::class, 'guardar'])->name('cuentas.guardar');

Route::post('/cuentas/exportar', [CuentaController::class, 'exportarExcel'])->name('cuentas.exportar');