<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MaestroController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\Admin\UserController;

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
    Route::patch('/admin/users/{user}/role', [UserController::class, 'updateRole'])->name('admin.users.role');

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

});