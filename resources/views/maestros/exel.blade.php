@extends('layouts.template')

@section('content')

<div class="container py-4">

    {{-- Alertas de estado o errores --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Card Formulario --}}
    <div class="card shadow-sm border-0">

        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-file-excel me-2"></i>
                Cargar Archivo Excel
            </h5>
        </div>

        <div class="card-body">

            <form action="{{ route('excel.cargar') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row align-items-end">

                    <div class="col-md-10 mb-3 mb-md-0">
                        <label for="archivo" class="form-label fw-bold">
                            Seleccione el archivo Excel
                        </label>

                        <input type="file"
                               id="archivo"
                               name="archivo"
                               class="form-control"
                               accept=".xlsx,.xls"
                               required>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-upload me-1"></i>
                            Subir
                        </button>
                    </div>

                </div>

            </form>

        </div>

    </div>

    {{-- Tabla de Resultados --}}
    @if(isset($datos) && count($datos) > 0)

        <div class="card mt-4 shadow-sm border-0">

            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    Datos encontrados en Excel
                </h5>
            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover table-striped mb-0">

                        <thead class="table-success">
                            <tr>
                                <th>Columna 1</th>
                                <th>Columna 2</th>
                                <th>Columna 3</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($datos as $fila)
                                <tr>
                                    <td>{{ $fila[0] ?? '' }}</td>
                                    <td>{{ $fila[1] ?? '' }}</td>
                                    <td>{{ $fila[2] ?? '' }}</td>
                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    @endif

</div>

@endsection