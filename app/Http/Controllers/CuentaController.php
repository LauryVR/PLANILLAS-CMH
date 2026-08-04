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
    $tiposCuenta = DB::table('tipos_cuenta')
                 ->where('activo', 1)
                 ->orderBy('nombre', 'asc')
                 ->get();

    // Recuperamos ambas sesiones de forma independiente
    $datos = session('datos', []); 
    $retenciones = session('retenciones_cargadas', []);

    return view('maestros.cuentas', compact('tiposCuenta', 'datos', 'retenciones'));
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

        $numLinea = $index + 1;

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
            'no_colegiado'   => 'N/A',
            'dni'            => $dni,
            'nombre'         => $nombre,
            'cuenta'         => $cuenta,
            'concepto'       => $concepto,
            'valor_concepto' => $valorConcepto,
            'tipo_cuenta'    => 10,
            'tiene_error'    => false
        ];

        $mensajesError = [];
        $camposError   = [];

        $maestro = $this->buscarMaestroFlexible($dni);

        if (!$maestro) {

            $camposError[]   = 'Identidad';
            $mensajesError[] = "La identidad/DNI '{$dni}' no existe en Maestros.";

        } else {

            $registroActual['dni']          = $maestro->dni;
            $registroActual['no_colegiado'] = $maestro->no_colegiado ?? 'N/A';

            $nombreMaestro = trim($maestro->nombre ?? '');

            if (strtolower($nombreMaestro) !== strtolower($nombre)) {

                $camposError[]   = 'Nombre';
                $mensajesError[] = "El nombre '{$nombre}' no coincide con '{$nombreMaestro}'";
            }
        }

        if (!empty($mensajesError)) {

            $erroresExcel[] = [
                'linea'    => $numLinea,
                'campos'   => implode(', ', $camposError),
                'valores'  => "DNI: {$dni} | Nombre: {$nombre}",
                'mensajes' => $mensajesError
            ];
        }

        $todosLosDatos[] = $registroActual;
    }

    // Mantener retenciones existentes
    $retencionesActuales = session()->get('retenciones_cargadas', []);

    // Guardar cuentas
    session()->put('datos', $todosLosDatos);

    // Mantener retenciones
    session()->put('retenciones_cargadas', $retencionesActuales);

    if (count($erroresExcel) > 0) {

        session()->put('errores_excel', $erroresExcel);

        return redirect()
            ->route('cuentas.index')
            ->with(
                'error',
                'Se encontraron inconsistencias en '
                . count($erroresExcel)
                . ' fila(s).'
            );
    }

    return redirect()
        ->route('cuentas.index')
        ->with(
            'success',
            'Archivo de cuentas procesado correctamente.'
        );
}

public function cargarRetenciones(Request $request)
    {
        $request->validate([
            'archivo_retencion' => 'required|mimes:xlsx,xls,csv'
        ]);

        $archivo = $request->file('archivo_retencion');

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $filas = $sheet->toArray();

            // 1. Recuperamos las cuentas por cobrar actuales de la sesión persistente
            $datosActuales = $request->session()->get('datos', []);

            // Creamos un mapa rápido de DNI => Nombre basado en las Cuentas por Cobrar cargadas
            $mapaCuentasPorCobrar = [];
            foreach ($datosActuales as $cuentaItem) {
                $dniCuenta = trim($cuentaItem['dni'] ?? '');
                $nombreCuenta = trim($cuentaItem['nombre'] ?? '');
                if (!empty($dniCuenta)) {
                    $mapaCuentasPorCobrar[$dniCuenta] = $nombreCuenta;
                }
            }

            $retenciones = [];
            $erroresRetencion = []; // Para la alerta superior

            for ($i = 1; $i < count($filas); $i++) {
                $fila = $filas[$i];
                $numLinea = $i + 1; // Fila real en el Excel
                
                $dni = trim($fila[0] ?? '');
                $nombreArchivoRetencion = trim($fila[1] ?? '');
                $monto = trim($fila[2] ?? 0);

                if (empty($dni) && empty($nombreArchivoRetencion)) {
                    continue;
                }

                $nombreFinal = $nombreArchivoRetencion;
                $dniValido = $dni;
                $tieneError = false;
                $mensajeError = '';

                // 2. Validar DNI en la base de datos (usando tu método flexible)
                $maestro = $this->buscarMaestroFlexible($dni);

                if (!$maestro) {
                    $tieneError = true;
                    $mensajeError = "El DNI '{$dni}' no se encuentra registrado en el sistema.";
                    $erroresRetencion[] = "Fila {$numLinea}: {$mensajeError}";
                } else {
                    $dniValido = $maestro->dni;
                    $nombreBd = trim($maestro->nombre ?? '');

                    // Cruzar con Cuentas por Cobrar o Base de Datos
                    if (isset($mapaCuentasPorCobrar[$dniValido])) {
                        $nombreCxC = $mapaCuentasPorCobrar[$dniValido];
                        if (empty($nombreFinal) || strtolower($nombreFinal) !== strtolower($nombreCxC)) {
                            $nombreFinal = $nombreCxC;
                        }
                    } else {
                        if (empty($nombreFinal) || strtolower($nombreFinal) !== strtolower($nombreBd)) {
                            $nombreFinal = $nombreBd;
                        }
                    }
                }

                $retenciones[] = [
                    'linea' => $numLinea,
                    'dni' => $dniValido,
                    'nombre' => $nombreFinal,
                    'monto' => $monto,
                    'tiene_error' => $tieneError,
                    'detalle_error' => $mensajeError
                ];
            }

            // 3. Guardamos de forma PERSISTENTE usando put()
            $request->session()->put('datos', $datosActuales);
            $request->session()->put('retenciones_cargadas', $retenciones);

            $redirect = back()
                ->with('datos', $datosActuales)
                ->with('retenciones_cargadas', $retenciones);

            if (count($erroresRetencion) > 0) {
                // Guardamos el detalle de errores específicos para mostrarlos en el Alert
                return $redirect->with('errores_retencion_detalle', $erroresRetencion)
                                ->with('error', 'Se encontraron errores en la planilla de retención. Por favor, revise las filas marcadas en rojo, corrija su archivo y vuelva a cargar la planilla de retención.');
            }

            return $redirect->with('success', 'Archivo de retenciones leído, validado y cruzado correctamente.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al leer el archivo de retenciones: ' . $e->getMessage());
        }
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
}

public function procesarExcel(Request $request)
{
    // ... tu lógica para leer el archivo Excel y obtener $filas ...

    $datosValidos = [];
    $filasConError = [];

    foreach ($filas as $index => $fila) {
        // Suponiendo que la columna del préstamo se llama 'prestamo' o índice numérico
        $prestamo = $fila['prestamo'] ?? $fila[2] ?? null; 

        if (empty($prestamo)) {
            // Guardamos la fila y un mensaje descriptivo del error
            $filasConError[] = [
                'fila' => $index + 1, // Número de fila en el Excel (ajustable según tus cabeceras)
                'datos' => $fila,
                'mensaje' => 'El campo préstamo está en blanco.'
            ];
        } else {
            $datosValidos[] = $fila;
        }
    }

    $tiposCuenta = DB::table('tipos_cuenta')
                 ->where('activo', 1)
                 ->orderBy('nombre', 'asc')
                 ->get();

    // Enviamos tanto los datos limpios como las alertas/errores a la vista
    return view('maestros.cuentas', compact('tiposCuenta', 'datosValidos', 'filasConError'));
}

public function exportarExcelPorConcepto(Request $request)
{
    $tipoFiltro = $request->input('tipo_filtro');
    $registros = json_decode($request->input('registros'), true);

    // Filtrar los datos en memoria según el concepto seleccionado
    $datosFiltrados = array_filter($registros, function($fila) use ($tipoFiltro) {
        $concepto = strtoupper($fila['cuenta_concepto'] ?? '');

        if ($tipoFiltro === 'cuota') {
            return str_contains($concepto, 'COLEGIA');
        } else {
            return str_contains($concepto, 'PERSONAL') || str_contains($concepto, 'PRESTAMO');
        }
    });

    if (empty($datosFiltrados)) {
        return back()->with('error', 'No se encontraron registros con ese concepto para exportar.');
    }

    $nombreArchivo = ($tipoFiltro === 'cuota') ? 'cuota_colegial.csv' : 'prestamos.csv';

    $callback = function() use ($datosFiltrados) {
        $file = fopen('php://output', 'w');
        
        // BOM para asegurar compatibilidad de tildes y eñes (UTF-8) en Excel
        fwrite($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeceras separadas por punto y coma
        $cabeceras = ['Nº Colegiado', 'DNI', 'Nombre', 'Nº Ref. Cuenta', 'Cuenta Concepto', 'Tipo Cuenta', 'Valor Concepto'];
        fwrite($file, implode(';', $cabeceras) . "\r\n");

      foreach ($datosFiltrados as $row) {
            // Forzamos el DNI para que Excel no le borre el cero inicial usando comillas y el tab/igual o un truco de formato de texto CSV
            $dniLimpio = trim(str_replace([';', "\n", "\r", '"'], '', $row['dni'] ?? ''));
            // Si el DNI tiene números, le anteponemos un apóstrofe interno o formato de texto para CSV
            $dniFormateado = $dniLimpio !== '' ? '="' . $dniLimpio . '"' : '';

            $linea = [
                trim(str_replace([';', "\n", "\r"], '', $row['no_colegiado'] ?? '')),
                $dniFormateado, // <--- DNI protegido para que mantenga el cero inicial
                trim(str_replace([';', "\n", "\r"], '', $row['nombre'] ?? '')),
                trim(str_replace([';', "\n", "\r"], '', $row['num_ref'] ?? '')),
                trim(str_replace([';', "\n", "\r"], '', $row['cuenta_concepto'] ?? '')),
                trim(str_replace([';', "\n", "\r"], '', $row['tipo_cuenta'] ?? '')),
                trim(str_replace([';', "\n", "\r"], '', $row['valor_concepto'] ?? ''))
            ];

            fwrite($file, implode(';', $linea) . "\r\n");
        }
        fclose($file);
    };

    return response()->stream($callback, 200, [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=$nombreArchivo",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ]);
}


}