<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Maestro;


class MaestroController extends Controller
{
public function index(Request $request)
{
    $buscar = $request->get('buscar');
    $criterio = $request->get('criterio', 'todos');

    // Validar columna y dirección para seguridad (evitar SQL Injection)
    $columnasPermitidas = ['id', 'nombre', 'dni', 'no_colegiado'];
    $sort = in_array($request->get('sort'), $columnasPermitidas) ? $request->get('sort') : 'id';
    $direction = in_array(strtolower($request->get('direction')), ['asc', 'desc']) ? $request->get('direction') : 'asc';

    $maestros = Maestro::query();

    if (!empty($buscar)) {
        $maestros->where(function($query) use ($buscar, $criterio) {
            switch ($criterio) {
                case 'nombre':
                    $query->where('nombre', 'LIKE', "%{$buscar}%");
                    break;

                case 'dni':
                    $query->where('dni', 'LIKE', "%{$buscar}%");
                    break;

                case 'no_colegiado':
                    $query->where('no_colegiado', 'LIKE', "%{$buscar}%");
                    break;

                default: // 'todos'
                    $query->where('nombre', 'LIKE', "%{$buscar}%")
                          ->orWhere('dni', 'LIKE', "%{$buscar}%")
                          ->orWhere('no_colegiado', 'LIKE', "%{$buscar}%");
                    break;
            }
        });
    }

    // Aplicar la ordenación por la columna y dirección requerida
    $maestros = $maestros->orderBy($sort, $direction)
                         ->paginate(25)
                         ->appends($request->all());

    return view('maestros.index', compact('maestros'));
}

public function update(Request $request, $id)
    {
        // Validación de datos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'dni' => 'required|string|max:20',
            'no_colegiado' => 'nullable|string|max:50',
        ]);

        // Buscar y actualizar el registro
        $maestro = Maestro::findOrFail($id);
        $maestro->update([
            'nombre' => $request->nombre,
            'dni' => $request->dni,
            'no_colegiado' => $request->no_colegiado,
        ]);

        return redirect()->back()->with('success', 'Registro actualizado correctamente.');
    }
}
