<?php

namespace App\Http\Controllers;

use App\Models\EnteRetencion;
use Illuminate\Http\Request;

class EnteRetencionController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $estado = $request->get('estado');

        $entes = EnteRetencion::query()
            ->when($buscar, function ($query, $buscar) {
                return $query->where('nombre', 'LIKE', "%{$buscar}%")
                             ->orWhere('ente_retencion_id', 'LIKE', "%{$buscar}%")
                             ->orWhere('id', 'LIKE', "%{$buscar}%");
            })
            ->when($estado !== null && $estado !== '', function ($query) use ($estado) {
                return $query->where('activo', $estado);
            })
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->appends($request->all());

        return view('configuracion.entes-retencion.index', compact('entes', 'buscar', 'estado'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ente_retencion_id' => 'required|string|max:50|unique:entes_retencion,ente_retencion_id',
            'nombre'            => 'required|string|max:150|unique:entes_retencion,nombre',
        ], [
            'ente_retencion_id.unique' => 'El Código del Ente de Retención ya existe.',
            'nombre.unique'            => 'El Nombre del Ente de Retención ya existe.',
        ]);

        EnteRetencion::create([
            'ente_retencion_id' => $request->ente_retencion_id,
            'nombre'            => $request->nombre,
            'activo'            => $request->has('activo') ? 1 : 0,
        ]);

        return redirect()->route('configuracion.entes-retencion.index')
                         ->with('success', 'Ente de Retención creado exitosamente.');
    }

    public function update(Request $request, $id)
    {
        $ente = EnteRetencion::findOrFail($id);

        $request->validate([
            'ente_retencion_id' => 'required|string|max:50|unique:entes_retencion,ente_retencion_id,' . $id,
            'nombre'            => 'required|string|max:150|unique:entes_retencion,nombre,' . $id,
        ]);

        $ente->update([
            'ente_retencion_id' => $request->ente_retencion_id,
            'nombre'            => $request->nombre,
            'activo'            => $request->has('activo') ? 1 : 0,
        ]);

        return redirect()->route('configuracion.entes-retencion.index')
                         ->with('success', 'Ente de Retención actualizado exitosamente.');
    }

    public function toggleState($id)
    {
        $ente = EnteRetencion::findOrFail($id);
        $ente->activo = !$ente->activo;
        $ente->save();

        $estadoTexto = $ente->activo ? 'activado' : 'inactivado';

        return redirect()->route('configuracion.entes-retencion.index')
                         ->with('success', "El Ente de Retención fue {$estadoTexto} correctamente.");
    }
}