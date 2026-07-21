@extends('layouts.template')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="container py-4">

    {{-- Alertas de estado --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- 1. Card Cargar Excel --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-file-excel me-2"></i> Cargar Archivo Excel
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
                        <input type="file" id="archivo" name="archivo" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-upload me-1"></i> Previsualizar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @php
        $datos = session('datos') ?? ($datos ?? []);
    @endphp

    {{-- 2. Tabla de Resultados Editables --}}
    @if(!empty($datos))
        <form action="{{ route('excel.guardar') }}" method="POST">
            @csrf
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="fas fa-edit me-2"></i> Previsualización y Edición de Datos
                        </h5>
                        <small class="text-muted">Se van a procesar <strong>{{ count($datos) }}</strong> registros.</small>
                    </div>

                    @if(session('errores_excel'))
                        <button type="button" class="btn btn-secondary" disabled title="Corrija los errores reportados abajo para poder guardar">
                            <i class="fas fa-lock me-1"></i> Correcciones requeridas
                        </button>
                    @else
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar en Base de Datos
                        </button>
                    @endif
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaExcel" class="table table-bordered table-hover align-middle mb-0 w-100">
                            <thead class="table-success">
                                <tr>
                                    <th width="50" class="text-center">#</th>
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                    <th>No. Colegiado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($datos as $index => $fila)
                                    {{-- Omitir encabezado si viene en la primera fila --}}
                                    @if($loop->first && strtolower(trim($fila[0] ?? '')) === 'nombre')
                                        @continue
                                    @endif

                                    {{-- Omitir filas vacías --}}
                                    @if(empty($fila[0]) && empty($fila[1]))
                                        @continue
                                    @endif

                                    <tr>
                                        <td class="text-center fw-bold text-muted">
                                            {{ $loop->iteration }}
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="maestros[{{ $index }}][nombre]" 
                                                   value="{{ $fila[0] ?? '' }}" 
                                                   class="form-control form-control-sm" 
                                                   required>
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="maestros[{{ $index }}][dni]" 
                                                   value="{{ $fila[1] ?? '' }}" 
                                                   class="form-control form-control-sm" 
                                                   required>
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="maestros[{{ $index }}][no_colegiado]" 
                                                   value="{{ $fila[2] ?? '' }}" 
                                                   class="form-control form-control-sm">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    @endif

    {{-- 3. Reporte de Errores y Duplicados Agrupados por Fila --}}
    @if(session('errores_excel'))
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i> Reporte de Errores Detectados
                    </h5>
                    <small>Conflicto(s) encontrados durante la validación</small>
                </div>
                <span class="badge bg-white text-danger fw-bold fs-6">
                    {{ count(session('errores_excel')) }} Fila(s) Afectada(s)
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaErrores" class="table table-striped table-hover mb-0 align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 100px;">Línea #</th>
                                <th style="width: 160px;">Campo(s)</th>
                                <th>Valor(es) Ingresado(s)</th>
                                <th>Errores Detectados</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('errores_excel') as $log)
                                <tr>
                                    <td class="text-center fw-bold">
                                        <span class="badge bg-danger fs-6">Fila {{ $log['linea'] }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-secondary">{{ $log['campos'] ?? $log['campo'] ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <code class="text-danger fw-bold fs-6">{{ $log['valores'] ?? $log['valor'] ?? 'N/A' }}</code>
                                    </td>
                                    <td>
                                        <ul class="mb-0 text-danger ps-3">
                                            @php $mensajes = (array) ($log['mensajes'] ?? $log['mensaje'] ?? []); @endphp
                                            @foreach($mensajes as $msj)
                                                <li><i class="fas fa-times-circle me-1"></i> {{ $msj }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
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

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            const dtLanguage = {
                "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            };

            if ($('#tablaExcel').length) {
                $('#tablaExcel').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [10, 25, 50, 100],
                    "language": dtLanguage
                });
            }

            if ($('#tablaErrores').length) {
                $('#tablaErrores').DataTable({
                    "pageLength": 5,
                    "lengthMenu": [5, 10, 25, 50],
                    "language": dtLanguage
                });
            }
        });
    </script>
@endpush