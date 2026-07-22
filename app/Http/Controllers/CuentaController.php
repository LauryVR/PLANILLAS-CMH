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
     * Busca un maestro en la BD de forma flexible para mitigar omisión de ceros por parte de Excel.
     */
    private function buscarMaestroFlexible(string $dni)
    {
        $dniClean = trim($dni);
        if (empty($dniClean)) {
            return null;
        }

        // 1. Búsqueda directa exacta
        $maestro = DB::table('maestros')->where('dni', $dniClean)->first();
        if ($maestro) {
            return $maestro;
        }

        // 2. Si no empieza por '0', probar agregándole el '0' inicial (por si Excel lo eliminó)
        if (!str_starts_with($dniClean, '0')) {
            $maestro = DB::table('maestros')->where('dni', '0' . $dniClean)->first();
            if ($maestro) {
                return $maestro;
            }
        }

        // 3. Búsqueda quitando guiones/espacios por si en BD están sin formato
        $soloNumeros = preg_replace('/[^0-9]/', '', $dniClean);
        if (!empty($soloNumeros) && $soloNumeros !== $dniClean) {
            $maestro = DB::table('maestros')->where('dni', $soloNumeros)->first();
            if ($maestro) {
                return $maestro;
            }
            if (!str_starts_with($soloNumeros, '0')) {
                $maestro = DB::table('maestros')->where('dni', '0' . $soloNumeros)->first();
                if ($maestro) {
                    return $maestro;
                }
            }
        }

        return null;
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

            $dni           = trim($fila[0] ?? '');
            $nombre        = trim($fila[1] ?? '');
            $cuenta        = trim($fila[2] ?? '');
            $concepto      = trim($fila[3] ?? '');
            $valorConcepto = trim($fila[4] ?? '');

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

            $maestro = $this->buscarMaestroFlexible($dni);

            if (!$maestro) {
                $camposError[]   = 'Identidad';
                $mensajesError[] = "La identidad/DNI '{$dni}' no se encuentra registrada en Maestros.";
            } else {
                $registroActual['dni'] = $maestro->dni;
                $dni = $maestro->dni;

                $nombreMaestro = trim($maestro->nombre ?? '');

                if (strtolower($nombreMaestro) !== strtolower($nombre)) {
                    $camposError[]   = 'Nombre';
                    $mensajesError[] = "El nombre '{$nombre}' no coincide con Maestros ('{$nombreMaestro}').";
                }
            }

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

        if (count($erroresExcel) > 0) {
            return back()
                ->with('datos', $todosLosDatos)
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
        $cuentasRaw = $request->input('cuentas', []);

        if (empty($cuentasRaw)) {
            return back()->with('error', 'No hay registros para guardar.');
        }

        $erroresFinales = [];
        $todosLosDatos  = [];

        foreach ($cuentasRaw as $index => $item) {
            // Se usa la línea enviada desde el formulario; si no existe, se calcula basándose en el índice
            $numFila = isset($item['linea']) ? (int)$item['linea'] : ($index + 2);
            $dni     = trim($item['dni'] ?? '');
            $nombre  = trim($item['nombre'] ?? '');

            $registroActual = [
                'linea'          => $numFila,
                'dni'            => $dni,
                'nombre'         => $nombre,
                'cuenta'         => trim($item['cuenta'] ?? ''),
                'concepto'       => trim($item['concepto'] ?? ''),
                'valor_concepto' => $item['valor_concepto'] ?? 0,
                'tiene_error'    => false,
                'detalle_error'  => ''
            ];

            $mensajesError = [];
            $camposError   = [];

            $maestro = $this->buscarMaestroFlexible($dni);

            if (!$maestro) {
                $camposError[]   = 'Identidad';
                $mensajesError[] = "La identidad '{$dni}' no existe en Maestros.";
            } else {
                $nombreMaestro = trim($maestro->nombre ?? '');
                if (strtolower($nombreMaestro) !== strtolower($nombre)) {
                    $camposError[]   = 'Nombre';
                    $mensajesError[] = "El nombre '{$nombre}' no coincide con el registrado en Maestros ('{$nombreMaestro}').";
                }
            }

            if (count($mensajesError) > 0) {
                $registroActual['tiene_error']   = true;
                $registroActual['detalle_error'] = implode(' | ', $mensajesError);

                $erroresFinales[] = [
                    'linea'    => $numFila,
                    'campos'   => implode(', ', array_unique($camposError)),
                    'valores'  => "DNI: {$dni} | Nombre: {$nombre}",
                    'mensajes' => $mensajesError,
                ];
            }

            $todosLosDatos[] = $registroActual;
        }

        if (count($erroresFinales) > 0) {
            return back()
                ->with('datos', $todosLosDatos) // Mantiene la estructura uniforme con 'linea', 'tiene_error', etc.
                ->with('errores_excel', $erroresFinales)
                ->with('error', 'No se pudo guardar. Hay registros que no coinciden con la tabla Maestros.');
        }

        DB::beginTransaction();
        try {
            foreach ($todosLosDatos as $item) {
                DB::table('cuentas_por_cobrar')->insert([
                    'dni'            => $item['dni'],
                    'nombre'         => $item['nombre'],
                    'cuenta'         => $item['cuenta'],
                    'concepto'       => $item['concepto'],
                    'valor_concepto' => $item['valor_concepto'],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('cuentas.index')->with('success', count($todosLosDatos) . ' cuentas por cobrar guardadas exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('datos', $todosLosDatos)
                ->with('error', 'Error al insertar los registros en la base de datos: ' . $e->getMessage());
        }
    }
}