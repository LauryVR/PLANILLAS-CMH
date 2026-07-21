<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

use App\Models\Maestro; 
class ExcelController extends Controller
{
    public function index()
    {
        return view('maestros.exel');
    }

    public function cargar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls'
        ]);

        $archivo = $request->file('archivo');

        $spreadsheet = IOFactory::load($archivo->getPathname());

        $sheet = $spreadsheet->getActiveSheet();

        $datos = [];

        foreach ($sheet->getRowIterator(2) as $row)
        {
            $fila = $row->getRowIndex();

            $datos[] = [
                $sheet->getCell('A'.$fila)->getValue(),
                $sheet->getCell('B'.$fila)->getValue(),
                $sheet->getCell('C'.$fila)->getValue(),
            ];
        }

        return view('maestros.exel', compact('datos'));
    }

public function guardarBD(Request $request)
{
    $request->validate([
        'maestros'               => 'required|array',
        'maestros.*.nombre'      => 'required|string|max:255',
        'maestros.*.dni'         => 'required|string|max:20',
        'maestros.*.no_colegiado'=> 'nullable|string|max:50',
    ]);

    $registros = $request->input('maestros');
    $erroresAgrupados = [];

    // Rastrear duplicados dentro del mismo Excel cargado
    $dnisProcesados = [];
    $colegiadosProcesados = [];

    foreach ($registros as $index => $datos) {
        $numeroLinea = $index + 1;
        $dni = trim($datos['dni']);
        $noColegiado = !empty($datos['no_colegiado']) ? trim($datos['no_colegiado']) : null;

        $mensajesFila = [];
        $camposAfectados = [];
        $valoresIngresados = [];

        // 1. Validaciones DNI
        if (in_array($dni, $dnisProcesados)) {
            $camposAfectados[] = 'DNI';
            $valoresIngresados[] = "DNI: {$dni}";
            $mensajesFila[] = 'El DNI está repetido en la misma lista cargada.';
        } else {
            $dnisProcesados[] = $dni;
        }

        $maestroExistenteDni = Maestro::where('dni', $dni)->first();
        if ($maestroExistenteDni) {
            $camposAfectados[] = 'DNI';
            $valoresIngresados[] = "DNI: {$dni}";
            $mensajesFila[] = 'El DNI ya existe en la Base de Datos (ID BD: ' . $maestroExistenteDni->id . ').';
        }

        // 2. Validaciones No. Colegiado
        if ($noColegiado) {
            if (in_array($noColegiado, $colegiadosProcesados)) {
                $camposAfectados[] = 'No. Colegiado';
                $valoresIngresados[] = "Colegiado: {$noColegiado}";
                $mensajesFila[] = 'El No. de Colegiado está repetido en la misma lista cargada.';
            } else {
                $colegiadosProcesados[] = $noColegiado;
            }

            $maestroExistenteCol = Maestro::where('no_colegiado', $noColegiado)->first();
            if ($maestroExistenteCol) {
                $camposAfectados[] = 'No. Colegiado';
                $valoresIngresados[] = "Colegiado: {$noColegiado}";
                $mensajesFila[] = 'El No. de Colegiado ya existe en la Base de Datos (ID BD: ' . $maestroExistenteCol->id . ').';
            }
        }

        // Si la fila acumuló uno o más errores, los agrupamos
        if (!empty($mensajesFila)) {
            $erroresAgrupados[] = [
                'linea'    => $numeroLinea,
                'campos'   => implode(', ', array_unique($camposAfectados)),
                'valores'  => implode(' | ', array_unique($valoresIngresados)),
                'mensajes' => $mensajesFila, // Arreglo con todos los fallos de esta fila
            ];
        }
    }

 // Si hay conflictos, cortamos el proceso e impedimos llegar al create()
if (count($erroresAgrupados) > 0) {
    // Reconstruimos los datos corregidos para no perder lo que el usuario editó en pantalla
    $datosParaVista = array_map(function ($m) {
        return [$m['nombre'], $m['dni'], $m['no_colegiado'] ?? ''];
    }, $registros);

    return back()
        ->withInput()
        ->with('errores_excel', $erroresAgrupados)
        ->with('datos', $datosParaVista)
        ->with('error', 'No se puede guardar: Existen conflictos de duplicado o duplicación interna.');
}

// ÚNICAMENTE si la línea anterior no se ejecutó (0 errores), se guardan los datos
foreach ($registros as $datos) {
    Maestro::create([
        'nombre'       => $datos['nombre'],
        'dni'          => $datos['dni'],
        'no_colegiado' => $datos['no_colegiado'] ?? null,
    ]);
}

    return redirect()->route('maestros.index')
        ->with('success', '¡Se han guardado ' . count($registros) . ' registros exitosamente!');
}

    public function guardar(Request $request)
    {
        // Tu lógica para iterar y guardar en la BD:
        foreach ($request->maestros as $datos) {
            Maestro::create([
                'nombre'       => $datos['nombre'],
                'dni'          => $datos['dni'],
                'no_colegiado' => $datos['no_colegiado'] ?? null,
            ]);
        }

        return redirect()->back()->with('success', 'Datos guardados correctamente.');
    }
}
