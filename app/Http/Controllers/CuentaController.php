<?php 



namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CuentaController extends Controller
{
    /**
     * Muestra la vista principal.
     */
    public function index()
    {
        return view('maestros.cuentas');
    }

    /**
     * Procesa el archivo Excel cargado y realiza las validaciones contra la tabla 'maestros'.
     */
    public function cargarExcel(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls'
        ]);

        $file = $request->file('archivo');

        // Leer archivo Excel usando PhpOffice\PhpSpreadsheet\IOFactory
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet   = $spreadsheet->getActiveSheet();
        $filas       = $worksheet->toArray();

        $todosLosDatos = [];
        $erroresExcel  = [];

        foreach ($filas as $index => $fila) {
            $numLinea = $index + 1; // Fila real dentro del Excel

            // Descartar encabezado o filas totalmente vacías
            if ($index === 0 && strtolower(trim($fila[0] ?? '')) === 'dni') {
                continue;
            }
            if (empty($fila[0]) && empty($fila[1])) {
                continue;
            }

            // Lectura de columnas desde el archivo Excel
            $dni           = trim($fila[0] ?? '');
            $nombre        = trim($fila[1] ?? '');
            $cuenta        = trim($fila[2] ?? '');
            $concepto      = trim($fila[3] ?? '');
            $valorConcepto = trim($fila[4] ?? '');

            // Mantenemos una lista con todos los registros procesados para mostrarlos en la vista siempre
            $registroActual = [
                'linea'          => $numLinea,
                'dni'            => $dni,
                'nombre'         => $nombre,
                'cuenta'         => $cuenta,
                'concepto'       => $concepto,
                'valor_concepto' => $valorConcepto,
                'tiene_error'    => false,
                'detalle_error'  => ''
            ];

            $mensajesError = [];
            $camposError   = [];

            // -------------------------------------------------------------
            // VALIDACIÓN ÚNICA: Verificar Identidad y Nombre en 'maestros'
            // -------------------------------------------------------------
            $maestro = DB::table('maestros')->where('dni', $dni)->first();

            if (!$maestro) {
                // Error 1: La Identidad / DNI no existe en los Datos Maestros
                $camposError[]   = 'Identidad';
                $mensajesError[] = "La identidad/DNI '{$dni}' no se encuentra registrada en Maestros.";
            } else {
                // Error 2: La Identidad existe, pero el nombre no coincide
                $nombreMaestro = trim($maestro->nombre ?? '');

                if (strtolower($nombreMaestro) !== strtolower($nombre)) {
                    $camposError[]   = 'Nombre';
                    $mensajesError[] = "El nombre '{$nombre}' no coincide con Maestros ('{$nombreMaestro}').";
                }
            }

            // Si hay errores en esta fila
            if (count($mensajesError) > 0) {
                $registroActual['tiene_error']   = true;
                $registroActual['detalle_error'] = implode(' | ', $mensajesError);

                $erroresExcel[] = [
                    'linea'    => $numLinea,
                    'campos'   => implode(', ', array_unique($camposError)),
                    'valores'  => "DNI: {$dni} | Nombre: {$nombre}",
                    'mensajes' => $mensajesError,
                ];
            }

            $todosLosDatos[] = $registroActual;
        }

        // Si existen errores, enviamos la lista completa de datos + el listado de errores
        if (count($erroresExcel) > 0) {
            return back()
                ->with('datos', $todosLosDatos) // Muestra la tabla completa en pantalla
                ->with('errores_excel', $erroresExcel)
                ->with('error', 'Se encontraron inconsistencias en ' . count($erroresExcel) . ' fila(s). Revisa el detalle en la tabla o en el resumen de errores.');
        }

        return back()
            ->with('datos', $todosLosDatos)
            ->with('success', 'Archivo procesado y validado correctamente.');
    }

    /**
     * Guarda masivamente en la BD los datos validados.
     */
    public function guardar(Request $request)
    {
        $cuentas = $request->input('cuentas', []);

        if (empty($cuentas)) {
            return back()->with('error', 'No hay registros para guardar.');
        }

        // Re-validación final antes de insertar
        $erroresFinales = [];

        foreach ($cuentas as $index => $item) {
            $numFila = $index + 1;
            $dni     = trim($item['dni'] ?? '');
            $nombre  = trim($item['nombre'] ?? '');

            $maestro = DB::table('maestros')->where('dni', $dni)->first();

            if (!$maestro) {
                $erroresFinales[] = [
                    'linea'    => $numFila,
                    'campos'   => 'Identidad',
                    'valores'  => "DNI: {$dni}",
                    'mensajes' => ["La identidad '{$dni}' no existe en Maestros."]
                ];
                continue;
            }

            if (strtolower(trim($maestro->nombre ?? '')) !== strtolower($nombre)) {
                $erroresFinales[] = [
                    'linea'    => $numFila,
                    'campos'   => 'Nombre',
                    'valores'  => "DNI: {$dni} | Nombre: {$nombre}",
                    'mensajes' => ["El nombre '{$nombre}' no coincide con el registrado en Maestros."]
                ];
            }
        }

        if (count($erroresFinales) > 0) {
            return back()
                ->with('datos', $cuentas) // Reenvía los datos para mantener la tabla visible
                ->with('errores_excel', $erroresFinales)
                ->with('error', 'No se pudo guardar. Hay registros que no coinciden con la tabla Maestros.');
        }

        DB::beginTransaction();
        try {
            foreach ($cuentas as $item) {
                DB::table('cuentas_por_cobrar')->insert([
                    'dni'            => trim($item['dni']),
                    'nombre'         => trim($item['nombre']),
                    'cuenta'         => trim($item['cuenta']),
                    'concepto'       => trim($item['concepto']),
                    'valor_concepto' => $item['valor_concepto'],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('cuentas.index')->with('success', count($cuentas) . ' cuentas por cobrar guardadas exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('datos', $cuentas) // Reenvía los datos para mantener la tabla visible si falla la BD
                ->with('error', 'Error al insertar los registros en la base de datos: ' . $e->getMessage());
        }
    }
}