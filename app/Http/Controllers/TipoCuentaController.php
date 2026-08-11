<?php

namespace App\Http\Controllers;

use App\Models\TipoCuenta;
use Illuminate\Http\Request;

class TipoCuentaController extends Controller
{
    /**
     * Mostrar listado con búsqueda, filtro y paginación (Ordenado por ID ASC).
     */
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $estado = $request->get('estado');

        $tiposCuenta = TipoCuenta::query()
            ->when($buscar, function ($query, $buscar) {
                return $query->where('nombre', 'LIKE', "%{$buscar}%")
                             ->orWhere('tipo_cuenta_id', 'LIKE', "%{$buscar}%")
                             ->orWhere('cuenta_sap', 'LIKE', "%{$buscar}%")
                             ->orWhere('id', 'LIKE', "%{$buscar}%");
            })
            ->when($estado !== null && $estado !== '', function ($query) use ($estado) {
                return $query->where('activo', $estado);
            })
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->appends($request->all());

        return view('configuracion.tipos-cuenta.index', compact('tiposCuenta', 'buscar', 'estado'));
    }

    /**
     * Guardar un nuevo registro.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tipo_cuenta_id' => 'required|string|max:50|unique:tipos_cuenta,tipo_cuenta_id',
            'nombre'         => 'required|string|max:150|unique:tipos_cuenta,nombre',
            'cuenta_sap'     => 'nullable|string|max:100',
        ], [
            'tipo_cuenta_id.unique' => 'El Tipo Cuenta ID ya existe.',
            'nombre.unique'         => 'El Nombre del tipo de cuenta ya existe.',
        ]);

        TipoCuenta::create([
            'tipo_cuenta_id' => $request->tipo_cuenta_id,
            'nombre'         => $request->nombre,
            'cuenta_sap'     => $request->cuenta_sap,
            'activo'         => $request->has('activo') ? 1 : 0,
        ]);

        return redirect()->route('configuracion.tipos-cuenta.index')
                         ->with('success', 'Tipo de cuenta creado exitosamente.');
    }

    /**
     * Actualizar datos del tipo de cuenta.
     */
    public function update(Request $request, $id)
    {
        $tipoCuenta = TipoCuenta::findOrFail($id);

        $request->validate([
            'tipo_cuenta_id' => 'required|string|max:50|unique:tipos_cuenta,tipo_cuenta_id,' . $id,
            'nombre'         => 'required|string|max:150|unique:tipos_cuenta,nombre,' . $id,
            'cuenta_sap'     => 'nullable|string|max:100',
        ]);

        $tipoCuenta->update([
            'tipo_cuenta_id' => $request->tipo_cuenta_id,
            'nombre'         => $request->nombre,
            'cuenta_sap'     => $request->cuenta_sap,
            'activo'         => $request->has('activo') ? 1 : 0,
        ]);

        return redirect()->route('configuracion.tipos-cuenta.index')
                         ->with('success', 'Tipo de cuenta actualizado exitosamente.');
    }

    /**
     * Cambiar de estado (Activar / Inactivar).
     */
    public function toggleState($id)
    {
        $tipoCuenta = TipoCuenta::findOrFail($id);
        $tipoCuenta->activo = !$tipoCuenta->activo;
        $tipoCuenta->save();

        $estadoTexto = $tipoCuenta->activo ? 'activado' : 'inactivado';

        return redirect()->route('configuracion.tipos-cuenta.index')
                         ->with('success', "El tipo de cuenta fue {$estadoTexto} correctamente.");
    }
}