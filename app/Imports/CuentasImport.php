<?php

namespace App\Imports;

use App\Models\Cuenta;
use App\Models\Maestro;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class CuentasImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public function model(array $row)
    {
        $maestro = Maestro::where('dni', $row['dni'])->first();

        return new Cuenta([
            'dni'            => $row['dni'],
            'nombre'         => $row['nombre'],
            'cuenta'         => $row['cuenta'],
            'concepto'       => $row['concepto'],
            'valor_concepto' => $row['valor_concepto'],
            'maestro_id'     => $maestro ? $maestro->id : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'dni'            => ['required', 'exists:maestros,dni'],
            'nombre'         => ['required', 'string'],
            'cuenta'         => ['required'],
            'concepto'       => ['required', 'string'],
            'valor_concepto' => ['required', 'numeric'],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'dni.exists' => 'El DNI ingresado no existe en el Directorio de Maestros.',
            'dni.required' => 'El campo DNI es obligatorio.',
            'valor_concepto.numeric' => 'El valor del concepto debe ser un número válido.',
        ];
    }
}