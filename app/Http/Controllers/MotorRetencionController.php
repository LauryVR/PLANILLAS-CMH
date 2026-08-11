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
        $request->validate([
            'motor_retencion_id' => 'required|exists:motores_retencion,id',
            'archivo' => 'required|mimes:xlsx,xls,csv'
        ]);

        $motorId = $request->motor_retencion_id;
        $file = $request->file('archivo');

        try {
            $data = Excel::toArray([], $file);

            if (empty($data) || !isset($data[0])) {
                return back()->with('error', 'El archivo está vacío o no tiene un formato válido.');
            }

            $filas = $data[0];
            $dnisInvalidos = [];
            $dnisEnArchivo = []; // Array para almacenar los DNIs válidos leídos en el Excel

            // 1. PRIMERA PASADA: Validar que todos los DNIs existan y recolectarlos
            foreach ($filas as $index => $row) {
                if ($index === 0 && strtolower($row[0] ?? '') == 'dni') continue;

                $dni = trim($row[0] ?? '');
                if (empty($dni)) continue;
                if (strlen($dni) === 12) $dni = '0' . $dni;

                if (!Maestro::where('dni', $dni)->exists()) {
                    $dnisInvalidos[] = $dni;
                } else {
                    $dnisEnArchivo[] = $dni; // Guardamos el DNI válido encontrado en el archivo
                }
            }

            if (!empty($dnisInvalidos)) {
                $listaDnis = implode(', ', array_unique($dnisInvalidos));
                return back()->with('error', 'Carga cancelada. Los siguientes DNIs no están registrados: ' . $listaDnis);
            }

            // Usamos una transacción para garantizar la integridad de la sincronización
            \Illuminate\Support\Facades\DB::transaction(function () use ($filas, $motorId, $dnisEnArchivo) {
                
                // 2. ELIMINAR los registros de este motor que NO están presentes en el Excel subido
                DetalleMotorConfig::where('motor_retencion_id', $motorId)
                    ->whereNotIn('dni', $dnisEnArchivo)
                    ->delete();

                // 3. SEGUNDA PASADA: Guardar o actualizar los registros que sí vienen en el Excel
                foreach ($filas as $index => $row) {
                    if ($index === 0 && strtolower($row[0] ?? '') == 'dni') continue;

                    $dni = trim($row[0] ?? '');
                    if (empty($dni)) continue;
                    if (strlen($dni) === 12) $dni = '0' . $dni;

                    $maestro = Maestro::where('dni', $dni)->first();
                    
                    if (!$maestro) continue;

                    DetalleMotorConfig::updateOrCreate(
                        [
                            'motor_retencion_id' => $motorId,
                            'dni' => $dni
                        ],
                        [
                            'colegiado_nombre'   => $maestro->nombre ?? 'N/A',
                            'numero_colegiado'   => $maestro->no_colegiado ?? 'N/A', 
                            'cuota_colegial'     => $row[1] ?? 0,
                            'automaticos'        => $row[2] ?? 0,
                            'estudio'            => $row[3] ?? 0,
                            'refinanciamiento'   => $row[4] ?? 0,
                            'readecuacion'       => $row[5] ?? 0,
                            'personal'           => $row[6] ?? 0,
                            'compra_deuda'       => $row[7] ?? 0,
                            'hipotecario'        => $row[8] ?? 0,
                            'vehiculo'           => $row[9] ?? 0,
                        ]
                    );
                }
            });

            return back()->with('success', 'Carga masiva procesada con éxito. Se actualizaron los registros y se eliminaron los ausentes en el archivo.');

        } catch (Exception $e) {
            return back()->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }
public function previsualizar(Request $request)
    {
        // 1. Validación de recepción de archivo
        if (!$request->hasFile('archivo')) {
            return response()->json(['error' => 'No se recibió ningún archivo'], 400);
        }

        try {
            // 2. Procesamiento del archivo Excel
            $data = Excel::toArray([], $request->file('archivo'));
            
            if (empty($data) || !isset($data[0])) {
                return response()->json(['error' => 'El archivo está vacío o no tiene formato válido'], 400);
            }

            $filas = $data[0];
            $resultado = [];
            $tieneErrores = false;

            // Arrays para control de DNIs duplicados en el Excel
            $dnisVistos = [];
            $filasDuplicadas = [];

            // 3. PRIMERA PASADA: Detectar DNIs duplicados en el archivo y números de fila exactos
            foreach ($filas as $index => $row) {
                if ($index === 0 && strtolower($row[0] ?? '') == 'dni') continue; 
                
                $dni = trim($row[0] ?? '');
                if (empty($dni)) continue;

                if (strlen($dni) === 12) {
                    $dni = '0' . $dni;
                }

                $numeroFilaExcel = $index + 1; // Fila real en Excel

                if (isset($dnisVistos[$dni])) {
                    $filasDuplicadas[] = $numeroFilaExcel;
                    // Aseguramos incluir también la primera aparición si no estaba en la lista
                    $primeraFila = $dnisVistos[$dni];
                    if (!in_array($primeraFila, $filasDuplicadas)) {
                        $filasDuplicadas[] = $primeraFila;
                    }
                } else {
                    $dnisVistos[$dni] = $numeroFilaExcel;
                }
            }

            // Si hay duplicados, detenemos y retornamos las filas con error
            if (!empty($filasDuplicadas)) {
                sort($filasDuplicadas);
                return response()->json([
                    'error' => 'Se encontraron números de identidad (DNI) repetidos en el archivo. Revise las siguientes filas: ' . implode(', ', $filasDuplicadas),
                    'tiene_errores' => true
                ], 422);
            }

            // 4. SEGUNDA PASADA: Iteración sobre las filas para validación con la base de datos Maestro
            foreach ($filas as $index => $row) {
                // Omitir cabecera
                if ($index === 0 && strtolower($row[0] ?? '') == 'dni') continue; 
                
                $dni = trim($row[0] ?? '');
                if (empty($dni)) continue;

                if (strlen($dni) === 12) {
                    $dni = '0' . $dni;
                }

                $maestro = Maestro::where('dni', $dni)->first();
                $esValido = $maestro !== null;

                if (!$esValido) {
                    $tieneErrores = true;
                }

                // 5. Construcción del arreglo de respuesta
                $resultado[] = [
                    'id' => uniqid(),
                    'dni' => $dni,
                    'numero_colegiado' => $maestro ? $maestro->no_colegiado : 'NO ENCONTRADO',
                    'nombre' => $maestro ? $maestro->nombre : 'NO REGISTRADO',
                    'es_valido' => $esValido,
                    'cuota' => $row[1] ?? 0,
                    'auto' => $row[2] ?? 0,
                    'estudio' => $row[3] ?? 0,
                    'refi' => $row[4] ?? 0,
                    'readecuacion' => $row[5] ?? 0,
                    'personal' => $row[6] ?? 0,
                    'compra_deuda' => $row[7] ?? 0,
                    'hipotecario' => $row[8] ?? 0,
                    'vehiculo' => $row[9] ?? 0,
                ];
            }

            // 6. Retorno de respuesta JSON
            return response()->json([
                'data' => $resultado,
                'tiene_errores' => $tieneErrores
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al procesar el archivo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Nuevo método para cargar datos mediante AJAX para la tabla
     */
    public function getDetallesJson($motorId)
    {
        $detalles = DetalleMotorConfig::where('motor_retencion_id', $motorId)->get();

        $resultados = $detalles->map(function ($item) {
            return [
                'id' => $item->id,
                'dni' => $item->dni,
                'nombre' => $item->colegiado_nombre,
                'numero_colegiado' => $item->numero_colegiado,
                'cuota_colegial' => $item->cuota_colegial,
                'automaticos' => $item->automaticos,
                'estudio' => $item->estudio,
                'refinanciamiento' => $item->refinanciamiento,
                'readecuacion' => $item->readecuacion,
                'personal' => $item->personal,
                'compra_deuda' => $item->compra_deuda,
                'hipotecario' => $item->hipotecario,
                'vehiculo' => $item->vehiculo,
            ];
        });

        return response()->json($resultados);
    }

    public function showImportar()
    {
        $motores = MotorRetencion::with('enteRetencion')->get();
        return view('motores.importar', compact('motores'));
    }

    public function actualizarMasivo(Request $request)
{
    $datosNuevos = $request->input('detalles', []);

    foreach ($datosNuevos as $id => $valores) {
        $detalle = DetalleMotorConfig::find($id);

        if ($detalle) {
            // Solo actualizamos los campos permitidos, protegiendo dni, nombre y número de colegiado
            $detalle->update([
                'cuota_colegial'   => $valores['cuota_colegial'] ?? 0,
                'automaticos'      => $valores['automaticos'] ?? 0,
                'estudio'          => $valores['estudio'] ?? 0,
                'refinanciamiento' => $valores['refinanciamiento'] ?? 0,
                'readecuacion'     => $valores['readecuacion'] ?? 0,
                'personal'         => $valores['personal'] ?? 0,
                'compra_deuda'     => $valores['compra_deuda'] ?? 0,
                'hipotecario'      => $valores['hipotecario'] ?? 0,
                'vehiculo'         => $valores['vehiculo'] ?? 0,
            ]);
        }
    }

    return back()->with('success', 'Los cambios se han guardado correctamente.');
}

public function descargarExcel($id)
{
    $motor = MotorRetencion::with('enteRetencion')->findOrFail($id);
    
    $nombreMotor = Str::slug($motor->nombre_motor, '_');
    $nombreEnte = Str::slug($motor->enteRetencion->nombre ?? 'ente', '_');
    $fileName = "motor_{$nombreMotor}_ente_{$nombreEnte}.xlsx";

    $detalles = DetalleMotorConfig::where('motor_retencion_id', $id)->get();

    return Excel::download(new class($detalles) implements FromCollection, WithHeadings, WithMapping {
        protected $data;

        public function __construct($data) {
            $this->data = $data;
        }

        public function collection() {
            return $this->data;
        }

        public function map($detalle): array {
            // Función auxiliar para forzar el valor como texto "0"
            $format = function($value) {
                // Si el valor es null, vacío o cero, devolvemos el string "0"
                return (empty($value) && $value !== 0 && $value !== '0') ? "0" : (string)$value;
            };

            return [
                (string)$detalle->dni,
                $format($detalle->cuota_colegial),
                $format($detalle->automaticos),
                $format($detalle->estudio),
                $format($detalle->refinanciamiento),
                $format($detalle->readecuacion),
                $format($detalle->personal),
                $format($detalle->compra_deuda),
                $format($detalle->hipotecario),
                $format($detalle->vehiculo),
            ];
        }

        public function headings(): array {
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
                'Vehículo'
            ];
        }
    }, $fileName);
}
}