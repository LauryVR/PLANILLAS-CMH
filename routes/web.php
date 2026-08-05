<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MaestroController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\TipoCuentaController;
use App\Http\Controllers\EnteRetencionController;
use App\Http\Controllers\PrioridadCuentaController;

// Redireccionar la raíz al login o a tu vista de inicio
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas generadas por Breeze (Login, Register, Logout, etc.)
require __DIR__.'/auth.php';

// =========================================================================
// RUTAS PROTEGEDAS (Solo accesibles si el usuario inició sesión)
// =========================================================================
Route::middleware(['auth'])->group(function () {

    // --- Módulo Gestión de Usuarios ---
    Route::get('/admin/usuarios', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/usuarios/crear', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/usuarios/crear', [UserController::class, 'store'])->name('admin.users.store');
    
    // Edición, Contraseña, Rol, Estado y Borrado
    Route::put('/admin/usuarios/{id}', [UserController::class, 'update'])->name('admin.users.update');
    Route::put('/admin/usuarios/{id}/password', [UserController::class, 'updatePassword'])->name('admin.users.password');
    Route::patch('/admin/usuarios/{id}/role', [UserController::class, 'updateRole'])->name('admin.users.role');
    Route::patch('/admin/usuarios/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
    Route::delete('/admin/usuarios/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');

    // --- Perfil de Usuario ---
    Route::get('/perfil/cambiar-password', [UserController::class, 'editPassword'])->name('password.change.edit');
    Route::put('/perfil/cambiar-password', [UserController::class, 'updateOwnPassword'])->name('password.change.update');

    // --- Inicio ---
    Route::get('/inicio', function () {
        return view('inicio');
    })->name('inicio');

    // --- Módulo Maestros ---
    Route::get('/maestros', [MaestroController::class, 'index'])->name('maestros.index');
    Route::put('/maestros/{id}', [MaestroController::class, 'update'])->name('maestros.update');

    // --- Módulo Excel ---
    Route::get('/excel', [ExcelController::class, 'index'])->name('excel.index');
    Route::post('/excel/cargar', [ExcelController::class, 'cargar'])->name('excel.cargar');
    Route::post('/excel/guardar', [ExcelController::class, 'guardarBD'])->name('excel.guardar');

    // --- Módulo Cuentas ---
    Route::get('/cuentas', [CuentaController::class, 'index'])->name('cuentas.index');
    Route::post('/cuentas/cargar', [CuentaController::class, 'cargarExcel'])->name('cuentas.cargar');
    Route::post('/cuentas/guardar', [CuentaController::class, 'guardar'])->name('cuentas.guardar');
    Route::post('/cuentas/exportar', [CuentaController::class, 'exportarExcel'])->name('cuentas.exportar');
    
    // ✅ CORREGIDO (CuentaController en singular):
    Route::post('/cuentas/verificar-dni', [CuentaController::class, 'verificarDniAjax'])->name('cuentas.verificar-dni');


 // --- Módulo Configuracion---
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    // Rutas secundarias para las tarjetas
Route::prefix('configuracion/tipos-cuenta')->name('configuracion.tipos-cuenta.')->group(function () {
    Route::get('/', [TipoCuentaController::class, 'index'])->name('index');
    Route::post('/', [TipoCuentaController::class, 'store'])->name('store');
    Route::put('/{id}', [TipoCuentaController::class, 'update'])->name('update');
    Route::patch('/{id}/toggle', [TipoCuentaController::class, 'toggleState'])->name('toggle');
});

Route::prefix('configuracion/entes-retencion')->name('configuracion.entes-retencion.')->group(function () {
    Route::get('/', [EnteRetencionController::class, 'index'])->name('index');
    Route::post('/', [EnteRetencionController::class, 'store'])->name('store');
    Route::put('/{id}', [EnteRetencionController::class, 'update'])->name('update');
    Route::patch('/{id}/toggle', [EnteRetencionController::class, 'toggleState'])->name('toggle');
});

// Cambia esto:
Route::prefix('configuracion/prioridades-cuentas')->name('configuracion.prioridades-cuentas.')->group(function () {
    Route::get('/', [PrioridadCuentaController::class, 'index'])->name('index');
    Route::post('/', [PrioridadCuentaController::class, 'store'])->name('store');
    
    // Ruta para guardar el nuevo orden
    Route::put('/reordenar', [PrioridadCuentaController::class, 'reordenar'])->name('reordenar');

    Route::put('/{id}', [PrioridadCuentaController::class, 'update'])->name('update');
    Route::patch('/{id}/toggle', [PrioridadCuentaController::class, 'toggleState'])->name('toggle');


});

Route::post('/cuentas/exportar-concepto', [CuentaController::class, 'exportarExcelPorConcepto'])->name('cuentas.exportar.concepto');


// 🆕 Nueva ruta para cargar Retenciones
Route::post('/retenciones/cargar', [CuentaController::class, 'cargarRetenciones'])->name('retenciones.cargar');


Route::post('/cargar-entes-retenedores', [CuentaController::class, 'cargarEntesRetenedores'])->name('cargar.entes.retenedores');


Route::get('/cuentas/reiniciar', [CuentaController::class, 'reiniciarCarga'])->name('cuentas.reiniciar');
});