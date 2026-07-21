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
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- 1. Card Cargar Excel de CxC --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-file-excel me-2"></i> Cargar Archivo Excel - Cuentas por Cobrar (CxC)
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('cuentas.cargar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-10 mb-3 mb-md-0">
                        <label for="archivo" class="form-label fw-bold">
                            Seleccione el archivo Excel (.xlsx / .xls)
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
        <form id="formGuardarCuentas" action="{{ route('cuentas.guardar') }}" method="POST">
            @csrf
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="fas fa-edit me-2"></i> Previsualización y Edición de Cuentas
                        </h5>
                        <small class="text-muted">Se van a procesar <strong>{{ count($datos) }}</strong> registros de cuentas.</small>
                    </div>

                    @if(session('errores_excel'))
                        <button type="button" class="btn btn-secondary" disabled title="Corrija los errores reportados abajo para poder guardar">
                            <i class="fas fa-lock me-1"></i> Correcciones requeridas
                        </button>
                    @else
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar Cuentas en BD
                        </button>
                    @endif
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaCuentas" class="table table-bordered table-hover align-middle mb-0 w-100">
                            <thead class="table-success">
                                <tr>
                                    <th width="50" class="text-center">#</th>
                                    <th>DNI</th>
                                    <th>Nombre</th>
                                    <th>Cuenta</th>
                                    <th>Concepto</th>
                                    <th>Valor Concepto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($datos as $index => $fila)
                                    @php
                                        // Extraer valores soportando tanto llaves asociativas como numéricas
                                        $valDni      = $fila['dni'] ?? $fila['identidad'] ?? $fila[0] ?? '';
                                        $valNombre   = $fila['nombre'] ?? $fila[1] ?? '';
                                        $valCuenta   = $fila['cuenta'] ?? $fila[2] ?? '';
                                        $valConcepto = $fila['concepto'] ?? $fila[3] ?? '';
                                        $valValor    = $fila['valor_concepto'] ?? $fila['valor'] ?? $fila[4] ?? '';
                                    @endphp

                                    {{-- Omitir si la fila viene totalmente vacía --}}
                                    @if(empty($valDni) && empty($valNombre))
                                        @continue
                                    @endif

                                    <tr>
                                        <td class="text-center fw-bold text-muted">
                                            {{ $loop->iteration }}
                                        </td>
                                        <td data-search="{{ $valDni }}" data-order="{{ $valDni }}">
                                            <input type="text" 
                                                   name="cuentas[{{ $index }}][dni]" 
                                                   value="{{ $valDni }}" 
                                                   class="form-control form-control-sm" 
                                                   required>
                                        </td>
                                        <td data-search="{{ $valNombre }}" data-order="{{ $valNombre }}">
                                            <input type="text" 
                                                   name="cuentas[{{ $index }}][nombre]" 
                                                   value="{{ $valNombre }}" 
                                                   class="form-control form-control-sm" 
                                                   required>
                                        </td>
                                        <td data-search="{{ $valCuenta }}" data-order="{{ $valCuenta }}">
                                            <input type="text" 
                                                   name="cuentas[{{ $index }}][cuenta]" 
                                                   value="{{ $valCuenta }}" 
                                                   class="form-control form-control-sm" 
                                                   required>
                                        </td>
                                        <td data-search="{{ $valConcepto }}" data-order="{{ $valConcepto }}">
                                            <input type="text" 
                                                   name="cuentas[{{ $index }}][concepto]" 
                                                   value="{{ $valConcepto }}" 
                                                   class="form-control form-control-sm" 
                                                   required>
                                        </td>
                                        <td data-search="{{ $valValor }}" data-order="{{ $valValor }}">
                                            <input type="number" 
                                                   step="0.01"
                                                   name="cuentas[{{ $index }}][valor_concepto]" 
                                                   value="{{ $valValor }}" 
                                                   class="form-control form-control-sm text-end" 
                                                   required>
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

    {{-- 3. Reporte de Errores y Validaciones --}}
    @if(session('errores_excel'))
        <div class="card shadow-sm border-danger mb-4">
            <div class="card-header bg-danger text-white d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i> Errores y Validación de DNI
                    </h5>
                    <small>Se encontraron conflictos con los datos del Maestro o formato</small>
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

            if ($('#tablaCuentas').length) {
                var table = $('#tablaCuentas').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [10, 25, 50, 100],
                    "language": dtLanguage,
                    // Permite a DataTables extraer los valores de los inputs para búsquedas y ordenamiento
                    "columnDefs": [
                        {
                            "targets": "_all",
                            "render": function(data, type, row, meta) {
                                return type === 'display' ? data : $(data).val() || data;
                            }
                        }
                    ]
                });

                // Enviar también los inputs que no estén visibles por la paginación
                $('#formGuardarCuentas').on('submit', function(e) {
                    var form = this;
                    table.$('input').each(function() {
                        if (!$.contains(document, this)) {
                            $(form).append(
                                $('<input>')
                                    .attr('type', 'hidden')
                                    .attr('name', this.name)
                                    .val(this.value)
                            );
                        }
                    });
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