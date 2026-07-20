<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;


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


    // Nuevo método para guardar los datos editados en la BD
    public function guardarBD(Request $request)
    {
        $request->validate([
            'maestros' => 'required|array',
            'maestros.*.nombre' => 'required|string|max:255',
            'maestros.*.dni' => 'required|string|max:20',
            'maestros.*.no_colegiado' => 'nullable|string|max:50',
        ]);

        $registros = $request->input('maestros');

        foreach ($registros as $datosMaestro) {
            // updateOrCreate evita duplicar registros si el DNI ya existe
            Maestro::updateOrCreate(
                ['dni' => $datosMaestro['dni']], // Condición de búsqueda
                [
                    'nombre' => $datosMaestro['nombre'],
                    'no_colegiado' => $datosMaestro['no_colegiado'],
                ]
            );
        }

        return redirect()->route('maestros.index')->with('success', '¡Se han guardado ' . count($registros) . ' registros en la base de datos!');
    }

}
