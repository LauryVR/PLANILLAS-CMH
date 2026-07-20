@extends('layouts.template')

@section('content')

<div class="container py-4">

    {{-- Alertas de estado --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
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

    {{-- Card Cargar Excel --}}
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
                            Previsualizar
                        </button>
                    </div>

                </div>

            </form>

        </div>

    </div>

    {{-- Tabla de Resultados Editables --}}
    @if(isset($datos) && count($datos) > 0)

        <form action="{{ route('excel.guardar') }}" method="POST">
            @csrf

            <div class="card mt-4 shadow-sm border-0">

                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Revisar y Modificar Datos Antes de Guardar
                    </h5>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Guardar en Base de Datos
                    </button>
                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle mb-0">

                            <thead class="table-success">
                                <tr>
                                    <th width="50" class="text-center">#</th>
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                    <th>No. Colegiado</th>
                                </tr>
                            </thead>

                            <tbody>

                                @php $contador = 0; @endphp

                                @foreach($datos as $index => $fila)

                                    {{-- Ignorar la primera fila si son los encabezados del Excel --}}
                                    @if($index == 0 && strtolower(trim($fila[0] ?? '')) == 'nombre')
                                        @continue
                                    @endif

                                    {{-- Si la fila está vacía completamente la saltamos --}}
                                    @if(empty($fila[0]) && empty($fila[1]))
                                        @continue
                                    @endif

                                    <tr>
                                        <td class="text-center fw-bold text-muted">
                                            {{ $contador + 1 }}
                                        </td>

                                        <td>
                                            <input type="text"
                                                   name="maestros[{{ $contador }}][nombre]"
                                                   value="{{ $fila[0] ?? '' }}"
                                                   class="form-control form-control-sm"
                                                   required>
                                        </td>

                                        <td>
                                            <input type="text"
                                                   name="maestros[{{ $contador }}][dni]"
                                                   value="{{ $fila[1] ?? '' }}"
                                                   class="form-control form-control-sm"
                                                   required>
                                        </td>

                                        <td>
                                            <input type="text"
                                                   name="maestros[{{ $contador }}][no_colegiado]"
                                                   value="{{ $fila[2] ?? '' }}"
                                                   class="form-control form-control-sm">
                                        </td>
                                    </tr>

                                    @php $contador++; @endphp

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </form>

    @endif

</div>

@endsection