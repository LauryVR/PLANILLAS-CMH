<?php

namespace App\Http\Controllers;

use App\Models\PrioridadCuenta;
use App\Models\TipoCuenta;
use Illuminate\Http\Request;

class PrioridadCuentaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');

        // Solo obtenemos los tipos de cuenta que aún NO se han asignado a la lista de prioridades
        $cuentasAsignadasIds = PrioridadCuenta::pluck('tipo_cuenta_id');

        $tiposCuenta = TipoCuenta::where('activo', true)
            ->whereNotIn('id', $cuentasAsignadasIds)
            ->orderBy('nombre')
            ->get();

        // Se obtienen TODAS las prioridades ordenadas de menor a mayor
        $prioridades = PrioridadCuenta::with('tipoCuenta')
            ->when($buscar, function ($q) use ($buscar) {
                return $q->whereHas('tipoCuenta', function ($sub) use ($buscar) {
                    $sub->where('nombre', 'LIKE', "%{$buscar}%")
                        ->orWhere('tipo_cuenta_id', 'LIKE', "%{$buscar}%");
                });
            })
            ->orderBy('prioridad', 'asc') // Siempre 1, 2, 3...
            ->get(); // Cambiamos paginate() por get() para permitir reordenamiento global

        return view('configuracion.prioridades-cuentas.index', compact('prioridades', 'tiposCuenta', 'buscar'));
    }

    /**
     * Guarda el nuevo orden masivo enviado desde la vista (Drag & Drop o Flechas)
     */
    public function reordenar(Request $request)
    {
        // $request->prioridades es un array [ id_prioridad => nuevo_numero ]
        $prioridades = $request->input('prioridades', []);

        foreach ($prioridades as $id => $nuevoOrden) {
            PrioridadCuenta::where('id', $id)->update([
                'prioridad' => $nuevoOrden
            ]);
        }

        return redirect()->route('configuracion.prioridades-cuentas.index')
            ->with('success', 'Secuencia de prioridades guardada correctamente.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_cuenta_id' => 'required|exists:tipos_cuenta,id',
        ]);

        $existe = PrioridadCuenta::where('tipo_cuenta_id', $request->tipo_cuenta_id)->exists();

        if ($existe) {
            return redirect()->back()
                ->withErrors(['tipo_cuenta_id' => 'Este tipo de cuenta ya tiene un orden de prioridad asignado.'])
                ->withInput();
        }

        // Calculamos la última prioridad para colocar la nueva cuenta al final automáticamente
        $ultimaPrioridad = PrioridadCuenta::max('prioridad') ?? 0;

        PrioridadCuenta::create([
            'tipo_cuenta_id' => $request->tipo_cuenta_id,
            'prioridad'      => $ultimaPrioridad + 1, // Se coloca automáticamente como la siguiente
            'activo'         => $request->has('activo') ? 1 : 0,
        ]);

        return redirect()->route('configuracion.prioridades-cuentas.index')
            ->with('success', 'Regla de prioridad registrada al final de la secuencia.');
    }

    public function toggleState($id)
    {
        $regla = PrioridadCuenta::findOrFail($id);
        $regla->activo = !$regla->activo;
        $regla->save();

        $estado = $regla->activo ? 'activada' : 'inactivada';

        return redirect()->route('configuracion.prioridades-cuentas.index')
            ->with('success', "La regla de prioridad fue {$estado} correctamente.");
    }
}