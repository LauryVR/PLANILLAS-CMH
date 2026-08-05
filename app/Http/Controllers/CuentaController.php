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

        // Recuperamos las tres sesiones de forma independiente
        $datos = session('datos', []); 
        $retenciones = session('retenciones_cargadas', []);
        $entesRetenedores = session('entes_retenedores', []);

        return view('maestros.cuentas', compact('tiposCuenta', 'datos', 'retenciones', 'entesRetenedores'));
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

        // 1. Primer paso opcional: Detectar qué números de cuenta se repiten en todo el archivo de forma global
        $conteoCuentas = [];
        for ($i = 1; $i < count($filas); $i++) {
            $ctaVal = trim($filas[$i][2] ?? '');
            if (!empty($ctaVal)) {
                $conteoCuentas[$ctaVal] = ($conteoCuentas[$ctaVal] ?? 0) + 1;
            }
        }

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

            // Bandera general de error para la fila
            $tieneErrorRegistro = false;

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

            // 2. Validación de Cuenta duplicada en el archivo
            if (!empty($cuenta) && isset($conteoCuentas[$cuenta]) && $conteoCuentas[$cuenta] > 1) {
                $camposError[]   = 'Cuenta';
                $mensajesError[] = "El número de cuenta '{$cuenta}' está repetido en el archivo.";
                $tieneErrorRegistro = true;
            }

            // Validación de DNI en Maestros
            $maestro = $this->buscarMaestroFlexible($dni);

            if (!$maestro) {
                $camposError[]   = 'Identidad';
                $mensajesError[] = "La identidad/DNI '{$dni}' no existe en Maestros.";
                $tieneErrorRegistro = true;
            } else {
                $registroActual['dni']          = $maestro->dni;
                $registroActual['no_colegiado'] = $maestro->no_colegiado ?? 'N/A';

                $nombreMaestro = trim($maestro->nombre ?? '');

                if (strtolower($nombreMaestro) !== strtolower($nombre)) {
                    $camposError[]   = 'Nombre';
                    $mensajesError[] = "El nombre '{$nombre}' no coincide con '{$nombreMaestro}'";
                    $tieneErrorRegistro = true;
                }
            }

            // Actualizamos la propiedad 'tiene_error' en el arreglo del registro
            $registroActual['tiene_error'] = $tieneErrorRegistro;

            if (!empty($mensajesError)) {
                $erroresExcel[] = [
                    'linea'    => $numLinea,
                    'campos'   => implode(', ', $camposError),
                    'valores'  => "DNI: {$dni} | Cuenta: {$cuenta} | Nombre: {$nombre}",
                    'mensajes' => $mensajesError
                ];
            }

            $todosLosDatos[] = $registroActual;
        }

        // Guardar cuentas de forma independiente (Laravel mantiene las demás en sesión)
        session()->put('datos', $todosLosDatos);

        if (count($erroresExcel) > 0) {
            session()->put('errores_excel', $erroresExcel);

            return redirect()
                ->route('cuentas.index')
                ->with('error', 'Se encontraron inconsistencias en ' . count($erroresExcel) . ' fila(s).');
        }

        return redirect()
            ->route('cuentas.index')
            ->with('success', 'Archivo de cuentas procesado correctamente.');
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

        // Recuperamos las cuentas por cobrar actuales de la sesión para el cruce de nombres
        $datosActuales = $request->session()->get('datos', []);

        $mapaCuentasPorCobrar = [];
        foreach ($datosActuales as $cuentaItem) {
            $dniCuenta = trim($cuentaItem['dni'] ?? '');
            $nombreCuenta = trim($cuentaItem['nombre'] ?? '');
            if (!empty($dniCuenta)) {
                $mapaCuentasPorCobrar[$dniCuenta] = $nombreCuenta;
            }
        }

        $retenciones = [];
        $erroresRetencion = [];
        
        // --- PASO EXTRA: Detectar DNIs duplicados en el archivo Excel antes de procesarlos ---
        $conteoDnisEnArchivo = [];
        for ($i = 1; $i < count($filas); $i++) {
            $dniCrudo = trim($filas[$i][0] ?? '');
            if (!empty($dniCrudo)) {
                // Limpieza básica temporal para contar duplicados exactos
                $dniLimpioTemp = is_numeric($dniCrudo) ? number_format((float)$dniCrudo, 0, '', '') : $dniCrudo;
                if (strlen($dniLimpioTemp) === 12) { $dniLimpioTemp = '0' . $dniLimpioTemp; }
                
                $conteoDnisEnArchivo[$dniLimpioTemp] = ($conteoDnisEnArchivo[$dniLimpioTemp] ?? 0) + 1;
            }
        }

        for ($i = 1; $i < count($filas); $i++) {
            $fila = $filas[$i];
            $numLinea = $i + 1;
            
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

            // Limpieza y estandarización del DNI actual para validaciones
            $dniLimpioCheck = is_numeric($dni) ? number_format((float)$dni, 0, '', '') : $dni;
            if (strlen($dniLimpioCheck) === 12) { $dniLimpioCheck = '0' . $dniLimpioCheck; }

            // 1. Validar si el DNI está repetido en el archivo
            if (isset($conteoDnisEnArchivo[$dniLimpioCheck]) && $conteoDnisEnArchivo[$dniLimpioCheck] > 1) {
                $tieneError = true;
                $mensajeError = "El DNI '{$dniLimpioCheck}' está duplicado en el archivo de retención.";
                $erroresRetencion[] = "Fila {$numLinea}: {$mensajeError}";
            }

            // 2. Buscar en la Base de Datos (Maestro)
            $maestro = $this->buscarMaestroFlexible($dni);

            if (!$maestro) {
                $tieneError = true;
                $mensajeError = empty($mensajeError) 
                    ? "El DNI '{$dni}' no se encuentra registrado en el sistema." 
                    : $mensajeError . " Además, no se encuentra registrado en el sistema.";
                if (!in_array("Fila {$numLinea}: El DNI '{$dni}' no se encuentra registrado en el sistema.", $erroresRetencion)) {
                    $erroresRetencion[] = "Fila {$numLinea}: El DNI '{$dni}' no se encuentra registrado en el sistema.";
                }
            } else {
                $dniValido = $maestro->dni;
                $nombreBd = trim($maestro->nombre ?? '');

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

        // Guardar las retenciones en la sesión
        $request->session()->put('retenciones_cargadas', $retenciones);

        if (count($erroresRetencion) > 0) {
            return back()
                ->with('errores_retencion_detalle', $erroresRetencion)
                ->with('error', 'Se encontraron errores (DNIS duplicados o no registrados) en la planilla. Revise las filas marcadas en rojo.');
            }

        return back()->with('success', 'Archivo de retenciones leído, validado y cruzado correctamente.');

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
public function cargarEntesRetenedores(Request $request)
{
    // Comprueba si tienes datos previos cargados
    if (empty(session('retenciones_cargadas')) || empty(session('datos'))) {
        return back()->with('error', 'Faltan datos previos de retenciones o cuentas por cobrar en la sesión.');
    }

    $request->validate([
        'archivo_entes' => 'required|mimes:xlsx,xls,csv'
    ]);

    $archivo = $request->file('archivo_entes');

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $filas = $sheet->toArray();
        
        $entesRetenedores = [];
        $erroresEntes = [];
        $mapaDniEntesNuevos = [];
        $conteoDnisEnArchivo = [];

        // --- PASO 0: Detectar DNIs duplicados en el Excel antes de procesar ---
        for ($i = 1; $i < count($filas); $i++) {
            $dniBruto = trim($filas[$i][0] ?? '');
            if (!empty($dniBruto)) {
                $dniLimpioTemp = is_numeric($dniBruto) ? number_format((float)$dniBruto, 0, '', '') : $dniBruto;
                if (strlen($dniLimpioTemp) === 12) { $dniLimpioTemp = '0' . $dniLimpioTemp; }
                
                $conteoDnisEnArchivo[$dniLimpioTemp] = ($conteoDnisEnArchivo[$dniLimpioTemp] ?? 0) + 1;
            }
        }

        for ($i = 1; $i < count($filas); $i++) {
            $fila = $filas[$i];
            $numLinea = $i + 1;
            
            $dniBruto = trim($fila[0] ?? '');
            if (empty($dniBruto)) {
                continue;
            }

            if (is_numeric($dniBruto)) {
                $dni = number_format((float)$dniBruto, 0, '', '');
            } else {
                $dni = $dniBruto;
            }

            if (strlen($dni) === 12) {
                $dni = '0' . $dni;
            }

            $tieneError = false;
            $mensajeError = '';
            $dniValido = $dni;

            if (isset($conteoDnisEnArchivo[$dni]) && $conteoDnisEnArchivo[$dni] > 1) {
                $tieneError = true;
                $mensajeError = "El DNI/Identidad '{$dni}' está duplicado en el archivo de Entes Retenedores.";
                $erroresEntes[] = "Fila {$numLinea}: {$mensajeError}";
            }

            $maestro = $this->buscarMaestroFlexible($dni);

            if (!$maestro) {
                $tieneError = true;
                $mensajeError = empty($mensajeError) ? "El DNI/Identidad '{$dni}' no existe en Maestros." : $mensajeError;
                $erroresEntes[] = "Fila {$numLinea}: El DNI/Identidad '{$dni}' no existe en Maestros.";
            } else {
                $dniValido = trim($maestro->dni);
                if (strlen($dniValido) === 12) {
                    $dniValido = '0' . $dniValido;
                }
            }

            $mapaDniEntesNuevos[$dniValido] = true;

            $registroEnte = [
                'linea'         => $numLinea,
                'dni'           => $dniValido,
                'cuota_cole'    => trim($fila[1] ?? 0),
                'automatico'    => trim($fila[2] ?? 0),
                'estudio'       => trim($fila[3] ?? 0),
                'refinancia'    => trim($fila[4] ?? 0),
                'readecuaci'    => trim($fila[5] ?? 0),
                'personal'      => trim($fila[6] ?? 0),
                'compra_deu'    => trim($fila[7] ?? 0),
                'hipotecario'   => trim($fila[8] ?? 0),
                'vehiculo'      => trim($fila[9] ?? 0),
                'tiene_error'   => $tieneError,
                'detalle_error' => $mensajeError
            ];

            $entesRetenedores[] = $registroEnte;
        }

        $retencionesActuales = $request->session()->get('retenciones_cargadas', []);
        $retencionesModificadas = [];
        $huboErrorRetencionPorEnte = false;

        foreach ($retencionesActuales as $retencion) {
            $dniRetencionBruto = trim($retencion['dni'] ?? '');
            $dniRetencion = is_numeric($dniRetencionBruto) ? number_format((float)$dniRetencionBruto, 0, '', '') : $dniRetencionBruto;
            if (strlen($dniRetencion) === 12) { $dniRetencion = '0' . $dniRetencion; }

            if (!empty($dniRetencion) && !isset($mapaDniEntesNuevos[$dniRetencion])) {
                $retencion['tiene_error'] = true;
                $retencion['detalle_error'] = "El DNI '{$dniRetencion}' no se encuentra inmerso en el archivo de Entes Retenedores cargado.";
                $huboErrorRetencionPorEnte = true;
            } else {
                $retencion['tiene_error'] = false;
                $retencion['detalle_error'] = '';
            }

            $retencionesModificadas[] = $retencion;
        }

        if (!empty($retencionesModificadas)) {
            $request->session()->put('retenciones_cargadas', $retencionesModificadas);
        }

        $request->session()->put('entes_retenedores', $entesRetenedores);

        // =========================================================================
        // 3. GENERACIÓN DE SIFCO INSUMOS CON FILTRADO ESTRICTO Y NOTIFICACIONES
        // =========================================================================
        $cuentasCobrar = session()->get('datos', []);
        $retencionesFinales = session()->get('retenciones_cargadas', []);

        $mapaRetencionesMonto = [];
        foreach ($retencionesFinales as $ret) {
            $dniRet = trim($ret['dni'] ?? '');
            if (is_numeric($dniRet)) { $dniRet = number_format((float)$dniRet, 0, '', ''); }
            if (strlen($dniRet) === 12) { $dniRet = '0' . $dniRet; }
            $mapaRetencionesMonto[$dniRet] = (float)($ret['monto'] ?? 0);
        }

        $prioridadesDb = \DB::table('prioridades_cuentas')
            ->where('activo', 1)
            ->orderBy('prioridad', 'asc')
            ->get()
            ->keyBy('tipo_cuenta_id');

        $cuentasPorDni = [];
        foreach ($cuentasCobrar as $cxc) {
            $dniCxC = trim($cxc['dni'] ?? '');
            if (is_numeric($dniCxC)) { $dniCxC = number_format((float)$dniCxC, 0, '', ''); }
            if (strlen($dniCxC) === 12) { $dniCxC = '0' . $dniCxC; }
            $cuentasPorDni[$dniCxC][] = $cxc;
        }

        $mapaEntesPorDni = [];
        foreach ($entesRetenedores as $ente) {
            $mapaEntesPorDni[$ente['dni']] = $ente;
        }

        $mapaCamposEnte = [
            1 => 'cuota_cole',
            2 => 'automatico',
            3 => 'estudio',
            4 => 'refinancia',
            5 => 'readecuaci',
            6 => 'personal',
            7 => 'compra_deu',
            8 => 'hipotecario',
            9 => 'vehiculo',
        ];

        $sifcoInsumos = [];
        $notificacionesDescartes = []; // Arreglo para acumular las notificaciones de productos no activos
        $boletaAutomatica = date('n') . date('Y');

        foreach ($retencionesFinales as $ret) {
            $dniRet = trim($ret['dni'] ?? '');
            if (is_numeric($dniRet)) { $dniRet = number_format((float)$dniRet, 0, '', ''); }
            if (strlen($dniRet) === 12) { $dniRet = '0' . $dniRet; }

            if (isset($cuentasPorDni[$dniRet])) {
                $misCuentas = $cuentasPorDni[$dniRet];
                $enteRecord = $mapaEntesPorDni[$dniRet] ?? null;
                
                $maestroTemp = $this->buscarMaestroFlexible($dniRet);
                $nombrePersona = $maestroTemp->nombre ?? ($misCuentas[0]['nombre'] ?? 'Afiliado');

                // FILTRADO ESTRICTO + NOTIFICACIÓN
                $misCuentas = array_filter($misCuentas, function($cuentaItem) use ($enteRecord, $mapaCamposEnte, $dniRet, $nombrePersona, &$notificacionesDescartes) {
                    $valorConcepto = (float)($cuentaItem['valor_concepto'] ?? 0);
                    if ($valorConcepto <= 0) {
                        return false;
                    }

                    if ($enteRecord) {
                        $tipoCuentaId = $cuentaItem['tipo_cuenta_id'] ?? ($cuentaItem['tipo_cuenta'] ?? 0);
                        $nombreConcepto = strtolower($cuentaItem['cuenta_concepto'] ?? ($cuentaItem['concepto'] ?? ''));

                        $campoEnte = $mapaCamposEnte[$tipoCuentaId] ?? null;

                        // Respaldo por texto por si el ID numérico no viene informado en cuentas por cobrar
                        if (!$campoEnte) {
                            if (str_contains($nombreConcepto, 'colegial')) $campoEnte = 'cuota_cole';
                            elseif (str_contains($nombreConcepto, 'automatic')) $campoEnte = 'automatico';
                            elseif (str_contains($nombreConcepto, 'estudio')) $campoEnte = 'estudio';
                            elseif (str_contains($nombreConcepto, 'refinancia')) $campoEnte = 'refinancia';
                            elseif (str_contains($nombreConcepto, 'readecuaci')) $campoEnte = 'readecuaci';
                            elseif (str_contains($nombreConcepto, 'personal')) $campoEnte = 'personal';
                            elseif (str_contains($nombreConcepto, 'compra')) $campoEnte = 'compra_deu';
                            elseif (str_contains($nombreConcepto, 'hipotecario')) $campoEnte = 'hipotecario';
                            elseif (str_contains($nombreConcepto, 'vehiculo')) $campoEnte = 'vehiculo';
                        }

                        if ($campoEnte && isset($enteRecord[$campoEnte])) {
                            $valorEnte = (float)$enteRecord[$campoEnte];
                            if ($valorEnte <= 0) {
                                // Registrar notificación clara para el usuario
                                $productoTexto = strtoupper($cuentaItem['cuenta_concepto'] ?? ($cuentaItem['concepto'] ?? $campoEnte));
                                $notificacionesDescartes[] = "Aviso: Al afiliado **{$nombrePersona}** (DNI: {$dniRet}) se le omitió el producto **{$productoTexto}** porque figura en 0.00 en el archivo de Entes Retenedores.";
                                
                                return false; // Se descarta
                            }
                        }
                    }

                    return true;
                });

                $misCuentas = array_values($misCuentas);

                foreach ($misCuentas as &$cuentaItem) {
                    $tipoCuentaId = $cuentaItem['tipo_cuenta_id'] ?? ($cuentaItem['tipo_cuenta'] ?? 0);
                    $prioridadObj = $prioridadesDb[$tipoCuentaId] ?? null;
                    
                    $cuentaItem['_prioridad'] = $prioridadObj ? (int)$prioridadObj->prioridad : 999;
                }
                unset($cuentaItem);

                usort($misCuentas, function($a, $b) {
                    return $a['_prioridad'] <=> $b['_prioridad'];
                });

                $maestro = $this->buscarMaestroFlexible($dniRet);
                $codigoColegial = $maestro->no_colegiado ?? ($misCuentas[0]['no_colegiado'] ?? '');
                $montoDisponible = $mapaRetencionesMonto[$dniRet] ?? 0;

                foreach ($misCuentas as $cxcMatch) {
                    if ($montoDisponible <= 0) {
                        break;
                    }

                    $valorConcepto = (float)($cxcMatch['valor_concepto'] ?? 0);
                    $valorAPagarAsignado = min($montoDisponible, $valorConcepto);

                    if ($valorAPagarAsignado > 0) {
                        $conceptoCuenta = $cxcMatch['cuenta_concepto'] ?? ($cxcMatch['concepto'] ?? '');

                        $sifcoInsumos[] = [
                            'ente_retenedor'    => '', 
                            'codigo_colegial'   => $codigoColegial, 
                            'codigo_sifco'      => '', 
                            'cuenta_numero'     => $cxcMatch['cuenta'] ?? '', 
                            'cuenta_referencia' => '', 
                            'cuenta_nombre'     => $nombrePersona, 
                            'no_identificacion' => $dniRet, 
                            'producto'          => $conceptoCuenta, 
                            'valor_a_pagar'     => $valorConcepto, 
                            'valor_real_pago'   => $valorAPagarAsignado, 
                            'boleta'            => $boletaAutomatica 
                        ];

                        $montoDisponible -= $valorAPagarAsignado;
                    }
                }
            }
        }

        $request->session()->put('sifco_insumos', $sifcoInsumos);

       // =========================================================================
        // 4. GENERACIÓN DE INSUMOS SAP (Control de Remanentes y Saldos Pendientes)
        // =========================================================================
        $insumosSapAgrupados = [];

        foreach ($sifcoInsumos as $item) {
            $dni = $item['no_identificacion'];
            
            if (!isset($insumosSapAgrupados[$dni])) {
                $insumosSapAgrupados[$dni] = [
                    'codigo_colegial'   => $item['codigo_colegial'],
                    'nombre'            => $item['cuenta_nombre'],
                    'no_identificacion' => $dni,
                    'total_retenido'    => $mapaRetencionesMonto[$dni] ?? 0,
                    'total_a_pagar'     => 0,
                    'total_pagado'      => 0
                ];
            }
            
            $insumosSapAgrupados[$dni]['total_a_pagar'] += (float)$item['valor_a_pagar'];
            $insumosSapAgrupados[$dni]['total_pagado'] += (float)$item['valor_real_pago'];
        }

        $insumosSap = [];
        foreach ($insumosSapAgrupados as $d) {
            $remanenteDinero = $d['total_retenido'] - $d['total_pagado']; // Lo que sobró de la retención (si es que sobró)
            $saldoPendienteDeuda = $d['total_a_pagar'] - $d['total_pagado']; // Lo que el afiliado aún debe de sus préstamos ($3,564.00 en este caso)

            $insumosSap[] = [
                'codigo_colegial'   => $d['codigo_colegial'],
                'nombre'            => $d['nombre'],
                'no_identificacion' => $d['no_identificacion'],
                'total_retenido'    => $d['total_retenido'],
                'total_pagado'      => $d['total_pagado'],
                'remanente'         => $remanenteDinero,     // Dinero sobrante de la retención
                'saldo_pendiente'   => $saldoPendienteDeuda  // Deuda insatisfecha de los préstamos
            ];
        }

        $request->session()->put('insumos_sap', $insumosSap);
        // =========================================================================
        // 5. RESPUESTA Y NOTIFICACIONES
        // =========================================================================
        $responseRedirect = back();

        if (count($notificacionesDescartes) > 0) {
            $responseRedirect->with('notificaciones_descartes', $notificacionesDescartes);
        }

        if (count($erroresEntes) > 0 || $huboErrorRetencionPorEnte) {
            return $responseRedirect
                ->with('errores_entes_detalle', $erroresEntes)
                ->with('error', 'Archivo procesado con observaciones y filtros aplicados.');
        }

        return $responseRedirect->with('success', 'Entes retenedores procesados. Se filtraron los productos en 0.00 y se generaron las alertas correspondientes.');

    } catch (\Exception $e) {
        return back()->with('error', 'Error al leer el archivo de entes retenedores: ' . $e->getMessage());
    }
}

public function reiniciarCarga(Request $request)
{
    $request->session()->forget([
        'datos',
        'retenciones_cargadas',
        'entes_retenedores',
        'sifco_insumos',
        'insumos_sap', // <-- Limpia también Insumos SAP
        'errores_excel',
        'errores_retencion_detalle',
        'errores_entes_detalle'
    ]);

    return redirect()
        ->route('cuentas.index')
        ->with('success', 'Se ha reiniciado la carga. Puede subir nuevos archivos.');
}



}