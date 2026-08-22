<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MotorRetencion;
use App\Models\DetalleMotorConfig;
use App\Models\EnteRetencion;
use App\Models\Maestro; // Asegúrate que este sea el nombre correcto de tu modelo
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Exception;

class MotorRetencionController extends Controller
{
  public function index()
{
    // Cambia ->get() por ->paginate(15)
    $motores = MotorRetencion::with('enteRetencion')->get();
    $detalles = DetalleMotorConfig::paginate(15); 
    $entes = EnteRetencion::all();
    
    return view('motores.index', compact('motores', 'detalles', 'entes'));
}

    public function store(Request $request)
    {
        $request->validate([
            'ente_retencion_id' => 'required|exists:entes_retencion,id|unique:motores_retencion,ente_retencion_id',
            'nombre_motor' => 'required|string|max:255'
        ]);

        MotorRetencion::create([
            'ente_retencion_id' => $request->ente_retencion_id,
            'nombre_motor' => $request->nombre_motor,
            'activo' => true
        ]);

        return back()->with('success', 'Motor de retención creado exitosamente.');
    }
public function importar(Request $request)
{
    set_time_limit(0);
    ini_set('memory_limit', '1024M');

    $request->validate([
        'motor_retencion_id' => 'required|exists:motores_retencion,id',
        'archivo' => 'required|mimes:xlsx,xls,csv'
    ]);

    $motorId = $request->motor_retencion_id;
    $file = $request->file('archivo');

    try {

        $data = Excel::toArray([], $file);

        if (empty($data) || !isset($data[0])) {
            return back()->with(
                'error',
                'El archivo está vacío o no tiene un formato válido.'
            );
        }

        $filas = $data[0];

        /*
         * Obtener todos los DNIs del Excel
         */
        $dnisExcel = [];

        foreach ($filas as $index => $row) {

            if ($index === 0 && strtolower(trim($row[0] ?? '')) === 'dni') {
                continue;
            }

            $dni = trim($row[0] ?? '');

            if (empty($dni)) {
                continue;
            }

            if (strlen($dni) === 12) {
                $dni = '0' . $dni;
            }

            $dnisExcel[] = $dni;
        }

        $dnisExcel = array_unique($dnisExcel);

        /*
         * Consultar TODOS los maestros en una sola consulta
         */
        $maestros = Maestro::whereIn('dni', $dnisExcel)
            ->get()
            ->keyBy('dni');

        /*
         * Validar DNIs inexistentes
         */
        $dnisInvalidos = [];

        foreach ($dnisExcel as $dni) {

            if (!isset($maestros[$dni])) {
                $dnisInvalidos[] = $dni;
            }
        }

        if (!empty($dnisInvalidos)) {

            return back()->with(
                'error',
                'Carga cancelada. Los siguientes DNIs no están registrados: ' .
                implode(', ', $dnisInvalidos)
            );
        }

        DB::transaction(function () use (
            $filas,
            $motorId,
            $dnisExcel,
            $maestros
        ) {

            /*
             * Eliminar registros que ya no están en el Excel
             */
            DetalleMotorConfig::where('motor_retencion_id', $motorId)
                ->whereNotIn('dni', $dnisExcel)
                ->delete();

            $registros = [];

            foreach ($filas as $index => $row) {

                if ($index === 0 && strtolower(trim($row[0] ?? '')) === 'dni') {
                    continue;
                }

                $dni = trim($row[0] ?? '');

                if (empty($dni)) {
                    continue;
                }

                if (strlen($dni) === 12) {
                    $dni = '0' . $dni;
                }

                $maestro = $maestros[$dni] ?? null;

                if (!$maestro) {
                    continue;
                }

                $registros[] = [

                    'motor_retencion_id' => $motorId,
                    'dni' => $dni,

                    'colegiado_nombre' => $maestro->nombre ?? 'N/A',
                    'numero_colegiado' => $maestro->no_colegiado ?? 'N/A',

                    'cuota_colegial'   => $row[1] ?? 0,
                    'automaticos'      => $row[2] ?? 0,
                    'estudio'          => $row[3] ?? 0,
                    'refinanciamiento' => $row[4] ?? 0,
                    'readecuacion'     => $row[5] ?? 0,
                    'personal'         => $row[6] ?? 0,
                    'compra_deuda'     => $row[7] ?? 0,
                    'hipotecario'      => $row[8] ?? 0,
                    'vehiculo'         => $row[9] ?? 0,
                    'empleado'         => $row[10] ?? 0,

                    'updated_by' => Auth::id(),

                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            /*
             * Procesar en bloques de 500
             */
            foreach (array_chunk($registros, 500) as $bloque) {

                DetalleMotorConfig::upsert(
                    $bloque,
                    ['motor_retencion_id', 'dni'],
                    [
                        'colegiado_nombre',
                        'numero_colegiado',
                        'cuota_colegial',
                        'automaticos',
                        'estudio',
                        'refinanciamiento',
                        'readecuacion',
                        'personal',
                        'compra_deuda',
                        'hipotecario',
                        'vehiculo',
                        'empleado',
                        'updated_by',
                        'updated_at'
                    ]
                );
            }
        });

        return back()->with(
            'success',
            'Carga masiva procesada con éxito.'
        );

    } catch (\Exception $e) {

        return back()->with(
            'error',
            'Error al procesar: ' . $e->getMessage()
        );
    }
}



public function previsualizar(Request $request)
{
    return response()->json([
        'data' => [],
        'total_registros' => 0,
        'tiene_errores' => false
    ]);
}

    /**
     * Nuevo método para cargar datos mediante AJAX para la tabla
     */
public function getDetallesJson($motorId)
{
    $detalles = DetalleMotorConfig::where('motor_retencion_id', $motorId)->get();

    $resultados = $detalles->map(function ($item) {

        $nombreUsuario = 'N/D';

        if (!empty($item->updated_by)) {

            $usuario = \App\Models\User::find($item->updated_by);

            if ($usuario) {
                $nombreUsuario = $usuario->name ?? $usuario->nombre ?? 'N/D';
            }
        }

        return [
            'id'                => $item->id,
            'dni'               => $item->dni,
            'nombre'            => $item->colegiado_nombre,
            'numero_colegiado'  => $item->numero_colegiado,

            'cuota_colegial'    => $item->cuota_colegial,
            'automaticos'       => $item->automaticos,
            'estudio'           => $item->estudio,
            'refinanciamiento'  => $item->refinanciamiento,
            'readecuacion'      => $item->readecuacion,
            'personal'          => $item->personal,
            'compra_deuda'      => $item->compra_deuda,
            'hipotecario'       => $item->hipotecario,
            'vehiculo'          => $item->vehiculo,

            // NUEVO CAMPO
            'empleado'          => $item->empleado,

            'updated_at'        => $item->updated_at,
            'updated_by'        => $nombreUsuario,
        ];
    });

    return response()->json($resultados);
}

public function showImportar()
{
    $motores = MotorRetencion::with('enteRetencion')->get();

    return view(
        'motores.importar',
        compact('motores')
    );
}
public function actualizarMasivo(Request $request)
{
    $datosNuevos = $request->input('detalles', []);

    // Definimos las columnas que permitimos editar
    $camposPermitidos = [
        'cuota_colegial',
        'automaticos',
        'estudio',
        'refinanciamiento',
        'readecuacion',
        'personal',
        'compra_deuda',
        'hipotecario',
        'vehiculo',
        'empleado'
    ];

    // Validar que los valores sean únicamente 0 o 1
    foreach ($datosNuevos as $id => $valores) {

        foreach ($valores as $columna => $valor) {

            if (in_array($columna, $camposPermitidos)) {

                if (!in_array($valor, [0, '0', 1, '1'], true)) {

                    return back()
                        ->withErrors([
                            'error' => "El campo '{$columna}' para el registro ID {$id} solo acepta valores 0 o 1."
                        ])
                        ->withInput();
                }
            }
        }
    }

    // Actualización masiva
    foreach ($datosNuevos as $id => $valores) {

        $detalle = DetalleMotorConfig::find($id);

        if ($detalle) {

            foreach ($valores as $columna => $valor) {

                if (in_array($columna, $camposPermitidos)) {

                    $detalle->{$columna} = (int) $valor;
                }
            }

            // Guardar solo si hubo cambios
            if ($detalle->isDirty()) {

                $detalle->updated_by = \Auth::id();

                $detalle->save();
            }
        }
    }

    return back()->with(
        'success',
        'Los cambios se han guardado correctamente.'
    );
}

public function descargarExcel($id)
{
    $motor = MotorRetencion::with('enteRetencion')->findOrFail($id);

    $nombreMotor = Str::slug($motor->nombre_motor, '_');
    $nombreEnte = Str::slug($motor->enteRetencion->nombre ?? 'ente', '_');
    $fileName = "motor_{$nombreMotor}_ente_{$nombreEnte}.xlsx";

    $detalles = DetalleMotorConfig::where('motor_retencion_id', $id)->get();

    return Excel::download(
        new class($detalles) implements FromCollection, WithHeadings, WithMapping {

            protected $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function collection()
            {
                return $this->data;
            }

            public function map($detalle): array
            {
                $format = function ($value) {
                    return (empty($value) && $value !== 0 && $value !== '0')
                        ? "0"
                        : (string) $value;
                };

                return [
                    (string) $detalle->dni,
                    $format($detalle->cuota_colegial),
                    $format($detalle->automaticos),
                    $format($detalle->estudio),
                    $format($detalle->refinanciamiento),
                    $format($detalle->readecuacion),
                    $format($detalle->personal),
                    $format($detalle->compra_deuda),
                    $format($detalle->hipotecario),
                    $format($detalle->vehiculo),

                    // NUEVA COLUMNA
                    $format($detalle->empleado),
                ];
            }

            public function headings(): array
            {
                return [
                    'DNI',
                    'Cuota Colegial',
                    'Automáticos',
                    'Estudio',
                    'Refinanciamiento',
                    'Readecuación',
                    'Personal',
                    'Compra Deuda',
                    'Hipotecario',
                    'Vehículo',

                    // NUEVA COLUMNA
                    'Empleado'
                ];
            }
        },
        $fileName
    );
}

public function edit($id)
{
    $motor = MotorRetencion::findOrFail($id);

    return response()->json($motor);
}
public function update(Request $request, $id)
{
    $motor = MotorRetencion::findOrFail($id);

    $motor->update([
        'ente_retencion_id' => $request->ente_retencion_id,
        'nombre_motor'      => $request->nombre_motor,
    ]);

    return redirect()
        ->route('motores.index')
        ->with('success', 'Motor actualizado correctamente.');
}

}
