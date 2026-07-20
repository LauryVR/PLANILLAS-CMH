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
}
