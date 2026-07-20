<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Maestro;


class MaestroController extends Controller
{
    public function index()
    {
        $maestros = Maestro::orderBy('id')
            ->paginate(25);

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
