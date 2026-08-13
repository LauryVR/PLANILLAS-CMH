<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Maestro;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\Auth;
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

    // Obtenemos los IDs únicos de motores que sí existen en tu tabla actual
    $idsMotores = DB::table('detalle_motor_configs')
                    ->select('motor_retencion_id')
                    ->distinct()
                    ->pluck('motor_retencion_id');

    // Diccionario para asociar cada ID con su respectivo nombre de institución
    $nombresEntes = [
        1 => 'Secretaría de Salud (SESAL)',
        2 => 'Instituto Hondureño de Seguridad Social (IHSS)',
        3 => 'Hospital Escuela',
        4 => 'Hospital María',
        5 => 'Ministerio Público',
        6 => 'Universidad Autónoma de Honduras (UNAH)',
        // Puedes agregar más números de ID según los que tengas registrados
    ];

    // Construimos la lista final con el ID y su nombre correspondiente
    $motoresRetencion = [];
    foreach ($idsMotores as $id) {
        $motoresRetencion[] = (object)[
            'motor_retencion_id' => $id,
            'nombre' => $nombresEntes[$id] ?? "Ente / Motor General (ID: {$id})"
        ];
    }

    $datos = session('datos', []); 
    $retenciones = session('retenciones_cargadas', []);
    $entesRetenedores = session('entes_retenedores', []);

    return view('maestros.cuentas', compact(
        'tiposCuenta', 
        'motoresRetencion', 
        'datos', 
        'retenciones', 
        'entesRetenedores'
    ));
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
    // Validar entrada del motor
    $request->validate([
        'motor_retencion_id' => 'required'
    ]);

    // Comprueba si tienes datos previos cargados
    if (empty(session('retenciones_cargadas')) || empty(session('datos'))) {
        return back()->with('error', 'Faltan datos previos de retenciones o cuentas por cobrar en la sesión.');
    }

    $motorRetencionId = $request->input('motor_retencion_id');

    try {
        // 1. Obtener configuraciones del motor directamente de BD
        $detallesMotor = \DB::table('detalle_motor_configs')
            ->where('motor_retencion_id', $motorRetencionId)
            ->get();

        if ($detallesMotor->isEmpty()) {
            return back()->with('error', 'No se encontraron registros configurados para el motor seleccionado.');
        }

        $entesRetenedores = [];
        $erroresEntes = [];
        $mapaDniEntesNuevos = [];
        $conteoDnisEnMotor = [];

        // Pre-procesar duplicados en motor con manejo seguro de ceros iniciales
        foreach ($detallesMotor as $row) {
            $dniBruto = trim($row->dni ?? '');
            if (!empty($dniBruto)) {
                $dniLimpioTemp = is_numeric($dniBruto) ? number_format((float)$dniBruto, 0, '', '') : $dniBruto;
                if (strlen($dniLimpioTemp) === 12) { $dniLimpioTemp = '0' . $dniLimpioTemp; }
                
                $conteoDnisEnMotor[$dniLimpioTemp] = ($conteoDnisEnMotor[$dniLimpioTemp] ?? 0) + 1;
            }
        }

        // 2. Procesar registros de configuración
        foreach ($detallesMotor as $index => $row) {
            $numLinea = $index + 1;
            $dniBruto = trim($row->dni ?? '');
            
            if (empty($dniBruto)) continue;

            $dni = is_numeric($dniBruto) ? number_format((float)$dniBruto, 0, '', '') : $dniBruto;
            if (strlen($dni) === 12) { $dni = '0' . $dni; }

            $tieneError = false;
            $mensajeError = '';
            $dniValido = $dni;

            // Validaciones
            if (($conteoDnisEnMotor[$dni] ?? 0) > 1) {
                $tieneError = true;
                $mensajeError = "El DNI/Identidad '{$dni}' está duplicado en la configuración de este motor.";
                $erroresEntes[] = "Registro {$numLinea}: {$mensajeError}";
            }

            $maestro = $this->buscarMaestroFlexible($dni);
            if (!$maestro) {
                $tieneError = true;
                $mensajeError = empty($mensajeError) ? "El DNI/Identidad '{$dni}' no existe en Maestros." : $mensajeError;
                $erroresEntes[] = "Registro {$numLinea}: El DNI/Identidad '{$dni}' no existe en Maestros.";
            } else {
                $dniValido = trim($maestro->dni);
                if (strlen($dniValido) === 12) {
                    $dniValido = '0' . $dniValido;
                }
            }

            $mapaDniEntesNuevos[$dniValido] = true;

            $enteData = [
                'linea'         => $numLinea,
                'dni'           => $dniValido,
                'cuota_cole'    => trim($row->cuota_colegial ?? 0),
                'automatico'    => trim($row->automaticos ?? 0),
                'estudio'       => trim($row->estudio ?? 0),
                'refinancia'    => trim($row->refinanciamiento ?? 0),
                'readecuaci'    => trim($row->readecuacion ?? 0),
                'personal'      => trim($row->personal ?? 0),
                'compra_deu'    => trim($row->compra_deuda ?? 0),
                'hipotecario'   => trim($row->hipotecario ?? 0),
                'vehiculo'      => trim($row->vehiculo ?? 0),
                'empleado' => trim($row->empleado ?? 0),
                'tiene_error'   => $tieneError,
                'detalle_error' => $mensajeError
            ];

            $entesRetenedores[] = $enteData;
        }

        // Cruzar y marcar errores en retenciones cargadas
        $retencionesActuales = $request->session()->get('retenciones_cargadas', []);
        $retencionesModificadas = [];
        $huboErrorRetencionPorEnte = false;

        foreach ($retencionesActuales as $retencion) {
            $dniRetencionBruto = trim($retencion['dni'] ?? '');
            $dniRetencion = is_numeric($dniRetencionBruto) ? number_format((float)$dniRetencionBruto, 0, '', '') : $dniRetencionBruto;
            if (strlen($dniRetencion) === 12) { $dniRetencion = '0' . $dniRetencion; }

            if (!empty($dniRetencion) && !isset($mapaDniEntesNuevos[$dniRetencion])) {
                $retencion['tiene_error'] = true;
                $retencion['detalle_error'] = "El DNI '{$dniRetencion}' no se encuentra inmerso en el motor de retención seleccionado.";
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
        $notificacionesDescartes = []; 
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

                $misCuentas = array_filter($misCuentas, function($cuentaItem) use ($enteRecord, $mapaCamposEnte, $dniRet, $nombrePersona, &$notificacionesDescartes) {
                    $valorConcepto = (float)($cuentaItem['valor_concepto'] ?? 0);
                    if ($valorConcepto <= 0) return false;

                    if ($enteRecord) {
                        $tipoCuentaId = $cuentaItem['tipo_cuenta_id'] ?? ($cuentaItem['tipo_cuenta'] ?? 0);
                        $nombreConcepto = strtolower($cuentaItem['cuenta_concepto'] ?? ($cuentaItem['concepto'] ?? ''));

                        $campoEnte = $mapaCamposEnte[$tipoCuentaId] ?? null;

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
                                $productoTexto = strtoupper($cuentaItem['cuenta_concepto'] ?? ($cuentaItem['concepto'] ?? $campoEnte));
                                $notificacionesDescartes[] = "Aviso: Al afiliado **{$nombrePersona}** (DNI: {$dniRet}) se le omitió el producto **{$productoTexto}** porque figura en 0.00 en la configuración del motor.";
                                return false; 
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

                usort($misCuentas, fn($a, $b) => $a['_prioridad'] <=> $b['_prioridad']);

                $maestro = $this->buscarMaestroFlexible($dniRet);
                $codigoColegial = $maestro->no_colegiado ?? ($misCuentas[0]['no_colegiado'] ?? '');
                $montoDisponible = $mapaRetencionesMonto[$dniRet] ?? 0;

                foreach ($misCuentas as $cxcMatch) {
                    if ($montoDisponible <= 0) break;

                    $valorConcepto = (float)($cxcMatch['valor_concepto'] ?? 0);
                    $valorAPagarAsignado = min($montoDisponible, $valorConcepto);

                    if ($valorAPagarAsignado > 0) {
                        $sifcoInsumos[] = [
                            'ente_retenedor'    => '', 
                            'codigo_colegial'   => $codigoColegial, 
                            'codigo_sifco'      => '', 
                            'cuenta_numero'     => $cxcMatch['cuenta'] ?? '', 
                            'cuenta_referencia' => '', 
                            'cuenta_nombre'     => $nombrePersona, 
                            'no_identificacion' => $dniRet, 
                            'producto'          => $cxcMatch['cuenta_concepto'] ?? ($cxcMatch['concepto'] ?? ''), 
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
        
        // Obtenemos todas las cuentas de la base de datos para pasarlas a la vista y al select
        $cuentasContables = \DB::table('tipos_cuenta')->get();
        $tiposCuentaMap = $cuentasContables->keyBy('tipo_cuenta_id');
        
        // Generación de un número de documento referencial (puedes ajustar esta lógica según tu necesidad)
        $numeroDocumento = 'plan' . date('mY') . $motorRetencionId;

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
                    'total_pagado'      => 0,
                    // Guardamos el tipo_cuenta_id para buscar la cuenta contable después
                    'tipo_cuenta_id'    => $item['tipo_cuenta_id'] ?? 1 
                ];
            }
            
            $insumosSapAgrupados[$dni]['total_a_pagar'] += (float)$item['valor_a_pagar'];
            $insumosSapAgrupados[$dni]['total_pagado'] += (float)$item['valor_real_pago'];
        }

        $insumosSap = [];
        foreach ($insumosSapAgrupados as $d) {
            $remanenteDinero = $d['total_retenido'] - $d['total_pagado']; 
            $saldoPendienteDeuda = $d['total_a_pagar'] - $d['total_pagado']; 

            $codigoLimpio = ltrim($d['codigo_colegial'] ?? '', 'C');
            $codigoConC = 'C' . $codigoLimpio;

            // Buscamos configuración contable inicial basada en el tipo de cuenta
            $configCuenta = $tiposCuentaMap->get($d['tipo_cuenta_id']);

            $insumosSap[] = [
                'numero_documento'  => $numeroDocumento,
                'comentario'        => 'Procesamiento de retención ' . date('d/m/Y'),
                'cuenta_contable'   => $configCuenta->cuenta_sap ?? '',
                'nombre_cuenta'     => $configCuenta->nombre ?? '',
                'socio_negocio'     => $codigoConC,
                'nombre_socio'      => $d['nombre'],
                'codigo_colegial'   => $codigoConC,
                'nombre'            => $d['nombre'],
                'no_identificacion' => $d['no_identificacion'],
                'total_retenido'    => $d['total_retenido'],
                'total_pagado'      => $d['total_pagado'],
                'remanente'         => $remanenteDinero,    
                'saldo_pendiente'   => $saldoPendienteDeuda  
            ];
        }

        // Guardamos tanto los insumos como el listado completo de cuentas contables en sesión
        $request->session()->put('insumos_sap', $insumosSap);
        $request->session()->put('cuentas_contables', $cuentasContables);

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
                ->with('error', 'Motor procesado con observaciones y filtros aplicados.');
        }

        return $responseRedirect->with('success', 'Motor de retención procesado correctamente desde la base de datos.');

    } catch (\Exception $e) {
        return back()->with('error', 'Error al procesar el motor de retención: ' . $e->getMessage());
    }
}

private function limpiarDni($dni) {
    $dni = trim($dni);
    if (is_numeric($dni)) {
        $dni = number_format((float)$dni, 0, '', '');
    }
    return (strlen($dni) === 12) ? '0' . $dni : $dni;
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

public function exportarSifcoTodos(Request $request)
{
    $sifcoInsumos = session('sifco_insumos', []);
    
    // Filtrar para OMITIR los conceptos que contengan "COLEGIAL"
    $filtrados = array_filter($sifcoInsumos, function($item) {
        $producto = strtoupper($item['producto'] ?? '');
        return !str_contains($producto, 'COLEGIAL');
    });

    return $this->generarExcelSifco(array_values($filtrados), 'Sifco_Insumos_Sin_Cuota_Colegial.xlsx');
}

public function exportarSifcoColegial(Request $request)
{
    $sifcoInsumos = session('sifco_insumos', []);
    
    // Filtrar estrictamente solo los registros de Cuota Colegial
    $filtrados = array_filter($sifcoInsumos, function($item) {
        $producto = strtoupper($item['producto'] ?? '');
        return str_contains($producto, 'COLEGIAL');
    });

    return $this->generarExcelSifco(array_values($filtrados), 'Sifco_Insumos_Cuota_Colegial.xlsx');
}

private function generarExcelSifco($datos, $nombreArchivo)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Cabeceras exactas según tu requerimiento
    $cabeceras = [
        'Ente Retenedor', 
        'Código Colegial', 
        'Codigo SIFCO', 
        'Cuenta Número', 
        'Cuenta Referencia', 
        'Cuenta Nombre', 
        'No. Identificación', 
        'Producto', 
        'Valor a Pagar', 
        'Valor Real Pago', 
        'Boleta'
    ];

    $sheet->fromArray($cabeceras, NULL, 'A1');

    // Mapear y escribir los datos manteniendo el orden estricto de las columnas
    $filaInicio = 2;
    foreach ($datos as $item) {
        $rowdata = [
            $item['ente_retenedor'] ?? '',
            $item['codigo_colegial'] ?? '',
            $item['codigo_sifco'] ?? '',
            $item['cuenta_numero'] ?? '',
            $item['cuenta_referencia'] ?? '',
            $item['cuenta_nombre'] ?? '',
            $item['no_identificacion'] ?? '',
            $item['producto'] ?? '',
            (float)($item['valor_a_pagar'] ?? 0),
            (float)($item['valor_real_pago'] ?? 0),
            $item['boleta'] ?? ''
        ];
        
        $sheet->fromArray($rowdata, NULL, 'A' . $filaInicio);
        
        // Dar formato numérico a las columnas de valores económicos
        $sheet->getStyle('I' . $filaInicio)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('J' . $filaInicio)->getNumberFormat()->setFormatCode('#,##0.00');
        
        $filaInicio++;
    }

    // Autoajustar el ancho de las columnas para mayor prolijidad
    foreach (range('A', 'K') as $columna) {
        $sheet->getColumnDimension($columna)->setAutoSize(true);
    }

    // Descargar el archivo Excel directamente al navegador
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment-filename="' . $nombreArchivo . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}


public function exportarSifcoPdf(Request $request)
{
    $sifcoInsumos = session('sifco_insumos', []);

    // Puedes pasarle datos adicionales como la fecha actual
    $data = [
        'insumos' => $sifcoInsumos,
        'fecha' => date('d/m/Y H:i:s')
    ];

    // Cargar la vista 'pdf.sifco_insumos' y configurar el tamaño en orientación horizontal (landscape)
    $pdf = Pdf::loadView('pdf.sifco_insumos', $data)
              ->setPaper('letter', 'landscape');

    // Descargar o mostrar directamente en el navegador
    return $pdf->download('Reporte_Sifco_Insumos_' . date('Y_m_d') . '.pdf');
}


public function exportarPdfColegial(Request $request)
{
    $sifcoInsumos = session('sifco_insumos', []);
    
    // Filtrar estrictamente solo Cuota Colegial
    $filtrados = array_filter($sifcoInsumos, function($item) {
        $producto = strtoupper($item['producto'] ?? '');
        return str_contains($producto, 'COLEGIAL');
    });

    return $this->generarPdfConEstructura(array_values($filtrados), 'REPORTE SIFCO INSUMOS - CUOTA COLEGIAL');
}

public function exportarPdfPrestamos(Request $request)
{
    $sifcoInsumos = session('sifco_insumos', []);
    
    // Filtrar para omitir Cuota Colegial (dejando solo préstamos)
    $filtrados = array_filter($sifcoInsumos, function($item) {
        $producto = strtoupper($item['producto'] ?? '');
        return !str_contains($producto, 'COLEGIAL');
    });

    return $this->generarPdfConEstructura(array_values($filtrados), 'REPORTE SIFCO INSUMOS - PRÉSTAMOS');
}

private function generarPdfConEstructura($insumos, $tituloReporte)
{
    $usuarioActual = Auth::user();
    
    $data = [
        'insumos' => $insumos,
        'tituloReporte' => $tituloReporte,
        'usuario' => $usuarioActual ? $usuarioActual->name . ' (' . $usuarioActual->email . ')' : 'Sistema / Invitado',
        'fecha' => date('d/m/Y H:i:s')
    ];

    $pdf = Pdf::loadView('pdf.sifco_insumos', $data)
              ->setPaper('letter', 'landscape');

    $nombreArchivo = str_replace(' ', '_', strtoupper($tituloReporte)) . '_' . date('Y_m_d') . '.pdf';

    return $pdf->download($nombreArchivo);
}

public function exportarSapRemanente(Request $request)
{
    $insumosSap = session('insumos_sap', []);

    // Solo conservar registros con remanente mayor a 0
    $insumosSap = array_filter($insumosSap, function ($item) {

        $remanente = (float)($item['remanente'] ?? 0);

        return $remanente > 0;
    });

    if (empty($insumosSap)) {
        return back()->with(
            'error',
            'No existen registros con remanente mayor a 0 para exportar.'
        );
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Cabeceras
    $cabeceras = [
        'Fecha',
        'Número Documento',
        'Débito',
        'Crédito',
        'Comentario',
        'Cuenta Contable',
        'Nombre Cuenta',
        'Socio Negocio',
        'Nombre Socio'
    ];

    $sheet->fromArray($cabeceras, null, 'A1');

    // Encabezados en negrita
    $sheet->getStyle('A1:I1')->getFont()->setBold(true);

    $fila = 2;

    foreach ($insumosSap as $item) {

        $fecha = !empty($item['fecha'])
            ? $item['fecha']
            : date('Y-m-d');

        $remanente = (float)($item['remanente'] ?? 0);

        $sheet->setCellValue('A' . $fila, $fecha);
        $sheet->setCellValue('B' . $fila, $item['numero_documento'] ?? '');

        // Débito = Remanente
        $sheet->setCellValue('C' . $fila, $remanente);

        $sheet->setCellValue('D' . $fila, (float)($item['credito'] ?? 0));
        $sheet->setCellValue('E' . $fila, $item['comentario'] ?? '');
        $sheet->setCellValue('F' . $fila, $item['cuenta_contable'] ?? '');
        $sheet->setCellValue('G' . $fila, $item['nombre_cuenta'] ?? '');
        $sheet->setCellValue('H' . $fila, $item['socio_negocio'] ?? '');
        $sheet->setCellValue(
            'I' . $fila,
            $item['nombre_socio'] ?? ($item['nombre'] ?? '')
        );

        // Formato numérico
        $sheet->getStyle('C' . $fila)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle('D' . $fila)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $fila++;
    }

    // Autoajustar columnas
    foreach (range('A', 'I') as $columna) {
        $sheet->getColumnDimension($columna)
            ->setAutoSize(true);
    }

    $nombreArchivo = 'Insumos_SAP_' . date('Y_m_d_His') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

    exit;
}
public function exportarSapPdfRemanente(Request $request)
{
    $insumosSap = session('insumos_sap', []);

    if (empty($insumosSap)) {
        return back()->with(
            'error',
            'No existen registros de Insumos SAP para generar el PDF.'
        );
    }

    // Asegurar que cada registro tenga fecha
    $insumosSap = array_map(function ($item) {

        $item['fecha'] = !empty($item['fecha'])
            ? $item['fecha']
            : date('Y-m-d');

        return $item;

    }, $insumosSap);

    $usuarioActual = Auth::user();

    $data = [
        'insumos' => array_values($insumosSap),
        'tituloReporte' => 'REPORTE DE INSUMOS SAP',
        'usuario' => $usuarioActual
            ? $usuarioActual->name . ' (' . $usuarioActual->email . ')'
            : 'Sistema / Invitado',
        'fecha' => date('d/m/Y H:i:s')
    ];

    $pdf = Pdf::loadView(
                'pdf.sap_remanentes',
                $data
            )
            ->setPaper('letter', 'landscape');

    return $pdf->download(
        'Reporte_Insumos_SAP_' . date('Y_m_d_His') . '.pdf'
    );
}
public function exportarReporteGeneral(Request $request)
{
    $sifcoInsumos = session('sifco_insumos', []);
    $insumosSap = session('insumos_sap', []);

    $mapaSap = [];

    foreach ($insumosSap as $sap) {

        $dni = $sap['no_identificacion'] ?? '';

        if (!empty($dni)) {
            $mapaSap[$dni] = $sap;
        }
    }

    $reporteGeneral = [];

    foreach ($sifcoInsumos as $item) {

        $dni = $item['no_identificacion'] ?? '';

        $sapData = $mapaSap[$dni] ?? [];

        $codigoSifcoColegial = $item['codigo_colegial'] ?? '';

        $codigoSapOriginal = $item['codigo_colegial'] ?? '';
        $codigoSapConC = 'C' . ltrim($codigoSapOriginal, 'C');

        $remanenteSap = (float)($sapData['remanente'] ?? 0);

        $reporteGeneral[] = [

            // DATOS SIFCO
            'codigo_sifco_colegial' => $codigoSifcoColegial,
            'codigo_sap'            => $codigoSapConC,
            'cuenta_numero'         => $item['cuenta_numero'] ?? '',
            'no_identificacion'     => $dni,
            'nombre'                => $item['cuenta_nombre'] ?? '',
            'producto'              => $item['producto'] ?? '',
            'valor_a_pagar'         => (float)($item['valor_a_pagar'] ?? 0),
            'valor_real_pago'       => (float)($item['valor_real_pago'] ?? 0),

            // REMANENTE
            'remanente_sap'         => $remanenteSap,

            // DATOS SAP
            'fecha_sap'        => $sapData['fecha'] ?? date('Y-m-d'),
            'numero_documento' => $sapData['numero_documento'] ?? '',
            'cuenta_contable'  => $sapData['cuenta_contable'] ?? '',
            'nombre_cuenta'    => $sapData['nombre_cuenta'] ?? '',
            'socio_negocio'    => $sapData['socio_negocio'] ?? '',
        ];
    }

    // Ordenar por nombre
    usort($reporteGeneral, function ($a, $b) {
        return strcmp($a['nombre'], $b['nombre']);
    });

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $cabeceras = [
        'Código Colegial',
        'Código SAP',
        'Cuenta Número',
        'Identificación',
        'Nombre',
        'Producto',
        'Valor a Pagar',
        'Valor Real Pagado',
        'Remanente SAP',
        'Fecha SAP',
        'Número Documento',
        'Cuenta Contable',
        'Nombre Cuenta',
        'Socio Negocio'
    ];

    $sheet->fromArray($cabeceras, null, 'A1');

    // Encabezados en negrita
    $sheet->getStyle('A1:N1')->getFont()->setBold(true);

    $filaInicio = 2;

    foreach ($reporteGeneral as $row) {

        $rowData = [
            $row['codigo_sifco_colegial'],
            $row['codigo_sap'],
            $row['cuenta_numero'],
            $row['no_identificacion'],
            $row['nombre'],
            $row['producto'],
            $row['valor_a_pagar'],
            $row['valor_real_pago'],
            $row['remanente_sap'],
            $row['fecha_sap'],
            $row['numero_documento'],
            $row['cuenta_contable'],
            $row['nombre_cuenta'],
            $row['socio_negocio']
        ];

        $sheet->fromArray(
            $rowData,
            null,
            'A' . $filaInicio
        );

        // Formato numérico
        foreach (['G', 'H', 'I'] as $col) {

            $sheet->getStyle($col . $filaInicio)
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        $filaInicio++;
    }

    // Autoajustar columnas
    foreach (range('A', 'N') as $columna) {
        $sheet->getColumnDimension($columna)
            ->setAutoSize(true);
    }

    $nombreArchivo =
        'Reporte_General_SIFCO_SAP_' .
        date('Y_m_d_His') .
        '.xlsx';

    header(
        'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );

    header(
        'Content-Disposition: attachment;filename="' .
        $nombreArchivo .
        '"'
    );

    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
}