<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Maestro;
use Illuminate\Support\Facades\Log;


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

        // 2. Si no empieza por '0', probar agregándole el '0' inicial
        if (!str_starts_with($dniClean, '0')) {
            $maestro = DB::table('maestros')->where('dni', '0' . $dniClean)->first();
            if ($maestro) {
                return $maestro;
            }
        }

        // 3. Búsqueda quitando guiones/espacios
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
                'linea'        => $numLinea,
                'no_colegiado' => 'N/A', // Campo por defecto si no se halla
                'dni'          => $dni,
                'nombre'       => $nombre,
                'cuenta'       => $cuenta,
                'concepto'     => $concepto,
                'valor_concepto' => $valorConcepto,
                'tiene_error'  => false,
                'detalle_error' => ''
            ];

            $mensajesError = [];
            $camposError   = [];

            $maestro = $this->buscarMaestroFlexible($dni);

            if (!$maestro) {
                $camposError[]   = 'Identidad';
                $mensajesError[] = "La identidad/DNI '{$dni}' no se encuentra registrada en Maestros.";
            } else {
                $registroActual['dni']          = $maestro->dni;
                $registroActual['no_colegiado'] = $maestro->no_colegiado ?? 'N/A'; // <--- Se asigna no_colegiado
                $dni                            = $maestro->dni;

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
        return back()->with('error', 'No hay registros para guardar/procesar.');
    }

    $erroresFinales = [];
    $todosLosDatos  = [];

    foreach ($cuentasRaw as $index => $item) {
        $numFila = isset($item['linea']) ? (int)$item['linea'] : ($index + 2);
        $dni     = trim($item['dni'] ?? '');
        $nombre  = trim($item['nombre'] ?? '');

        $registroActual = [
            'no_colegiado'   => trim($item['no_colegiado'] ?? 'N/A'),
            'dni'            => $dni,
            'nombre'         => $nombre,
            'cuenta'         => trim($item['cuenta'] ?? ''),
            'concepto'       => trim($item['concepto'] ?? ''),
            'tipo_cuenta'    => $item['tipo_cuenta'] ?? 10,
            'valor_concepto' => $item['valor_concepto'] ?? 0,
            'tiene_error'    => false,
            'detalle_error'  => ''
        ];

        $mensajesError = [];
        $camposError   = [];

        // Validar contra Maestros (sin sobrescribir ningún valor)
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

    // Si existen inconsistencias, regresamos con los mensajes de error
    if (count($erroresFinales) > 0) {
        return back()
            ->with('datos', $todosLosDatos)
            ->with('errores_excel', $erroresFinales)
            ->with('error', 'No se pudo procesar. Hay registros que no coinciden con la tabla Maestros.');
    }

    // --- GENERAR ARCHIVO CON FECHA Y HORA AL GUARDAR ---
    $nombreArchivo = 'Cuentas_Guardadas_' . now()->format('Y-m-d_H-i-s') . '.csv';

    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename={$nombreArchivo}",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $callback = function() use ($todosLosDatos) {
        $file = fopen('php://output', 'w');
        
        // Soporte para caracteres especiales (tildes, Ñ) en Excel
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Columnas exactas sin la columna # Fila/Línea
        fputcsv($file, [
            'N° Colegiado',
            'DNI',
            'Nombre',
            'N° Ref. Cuenta',
            'Cuenta Concepto',
            'Tipo Cuenta',
            'Valor Concepto'
        ]);

        foreach ($todosLosDatos as $row) {
            fputcsv($file, [
                $row['no_colegiado'],
                $row['dni'],
                $row['nombre'],
                $row['cuenta'],
                $row['concepto'],
                $row['tipo_cuenta'],
                $row['valor_concepto'],
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    /**
     * Exporta los datos validados actualmente a un archivo Excel.
     */
 public function exportarExcel(Request $request)
{
    $cuentasRaw = $request->input('cuentas', []);

    if (empty($cuentasRaw)) {
        return back()->with('error', 'No hay datos cargados para exportar.');
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Encabezados sin 'Línea Excel' y con las nuevas columnas
    $headers = [
        'N° Colegiado', 
        'DNI', 
        'Nombre', 
        'N° Ref. Cuenta', 
        'Cuenta Concepto', 
        'Tipo Cuenta', 
        'Valor Concepto'
    ];
    $sheet->fromArray($headers, NULL, 'A1');

    // Estilo de encabezado (ahora va de A1 a G1)
    $sheet->getStyle('A1:G1')->getFont()->setBold(true);

    // Generar filas arrancando desde la columna A
    $fila = 2;
    foreach ($cuentasRaw as $item) {
        $sheet->setCellValue("A{$fila}", $item['no_colegiado'] ?? 'N/A');
        $sheet->setCellValue("B{$fila}", $item['dni'] ?? '');
        $sheet->setCellValue("C{$fila}", $item['nombre'] ?? '');
        $sheet->setCellValue("D{$fila}", $item['cuenta'] ?? '');
        $sheet->setCellValue("E{$fila}", $item['concepto'] ?? '');
        $sheet->setCellValue("F{$fila}", $item['tipo_cuenta'] ?? '');
        $sheet->setCellValue("G{$fila}", $item['valor_concepto'] ?? 0);
        $fila++;
    }

    $writer = new Xlsx($spreadsheet);
    
    // Nombre del archivo con la fecha y hora dinámica (Ejemplo: Cuentas_Previsualizacion_20260723_103000.xlsx)
    $fileName = 'Cuentas_Previsualizacion_' . date('Ymd_His') . '.xlsx';

    return response()->streamDownload(function() use ($writer) {
        $writer->save('php://output');
    }, $fileName, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Cache-Control' => 'max-age=0',
    ]);
}
/**
     * Ruta AJAX para verificar un DNI individual en tiempo real.
     */
public function verificarDniAjax(Request $request)
{
    $dniOriginal = $request->input('dni');

    if (empty($dniOriginal)) {
        return response()->json([
            'valido' => false, 
            'mensaje' => 'El DNI enviado está vacío.'
        ]);
    }

    // Método flexible de búsqueda que tienes en el controlador
    $maestro = $this->buscarMaestroFlexible($dniOriginal);

    if ($maestro) {
        return response()->json([
            'valido'       => true,
            'success'      => true,
            'dni_real'     => $maestro->dni,
            'nombre_real'  => $maestro->nombre ?? $maestro->nombre_completo ?? '',
            'no_colegiado' => $maestro->no_colegiado ?? 'N/A',
            'mensaje'      => 'DNI verificado correctamente'
        ]);
    }

    return response()->json([
        'valido'  => false,
        'success' => false,
        'mensaje' => "El DNI '{$dniOriginal}' no se encontró en la tabla Maestros."
    ]);
}}