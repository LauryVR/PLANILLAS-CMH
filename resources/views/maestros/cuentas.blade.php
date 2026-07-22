@extends('layouts.template')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    {{-- CSS para Mover Columnas (ColReorder) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReorder.bootstrap5.min.css">
    <style>
        /* Estilo para los filtros del encabezado */
        .filter-row input {
            font-size: 0.8rem;
            padding: 0.2rem 0.4rem;
        }
    </style>
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
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="fas fa-edit me-2"></i> Previsualización y Edición de Cuentas
                        </h5>
                        <small class="text-muted">Se van a procesar <strong>{{ count($datos) }}</strong> registros de cuentas. <i class="fas fa-info-circle ms-1"></i> <em>Usa los filtros superiores o arrastra las columnas para reordenar.</em></small>
                    </div>

                    <div class="d-flex gap-2">
                        {{-- Botón Exportar Previsualización --}}
                        <button type="submit" formaction="{{ route('cuentas.exportar') }}" class="btn btn-outline-success">
                            <i class="fas fa-file-download me-1"></i> Exportar Excel
                        </button>

                        @if(session('errores_excel'))
                            <button type="button" class="btn btn-secondary" disabled title="Corrija los errores reportados abajo para poder guardar">
                                <i class="fas fa-lock me-1"></i> Correcciones requeridas
                            </button>
                        @else
                          <button type="submit" class="btn btn-primary d-none">
    <i class="fas fa-save me-1"></i> Guardar Cuentas en BD
</button>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaCuentas" class="table table-bordered table-hover align-middle mb-0 w-100">
                            <thead class="table-success">
                                <tr>
                                    <th width="50" class="text-center"># Fila</th>
                                    <th>N° Colegiado</th>
                                    <th>DNI</th>
                                    <th>Nombre</th>
                                    <th>Cuenta</th>
                                    <th>Concepto</th>
                                    <th>Valor Concepto</th>
                                </tr>
                                {{-- Fila para Filtros de Columna --}}
                                <tr class="filter-row bg-light">
                                    <th></th>
                                    <th><input type="text" class="form-control form-control-sm column-filter" placeholder="Filtrar..."></th>
                                    <th><input type="text" class="form-control form-control-sm column-filter" placeholder="Filtrar DNI..."></th>
                                    <th><input type="text" class="form-control form-control-sm column-filter" placeholder="Filtrar Nombre..."></th>
                                    <th><input type="text" class="form-control form-control-sm column-filter" placeholder="Filtrar Cuenta..."></th>
                                    <th><input type="text" class="form-control form-control-sm column-filter" placeholder="Filtrar Concepto..."></th>
                                    <th><input type="text" class="form-control form-control-sm column-filter" placeholder="Filtrar Valor..."></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($datos as $index => $fila)
                                    @php
                                        // Extraer valores
                                        $valColegiado = $fila['no_colegiado'] ?? $fila['numero_colegiado'] ?? 'N/A';
                                        $valDni       = $fila['dni'] ?? $fila['identidad'] ?? $fila[0] ?? '';
                                        $valNombre    = $fila['nombre'] ?? $fila[1] ?? '';
                                        $valCuenta    = $fila['cuenta'] ?? $fila[2] ?? '';
                                        $valConcepto  = $fila['concepto'] ?? $fila[3] ?? '';
                                        $valValor     = $fila['valor_concepto'] ?? $fila['valor'] ?? $fila[4] ?? '';
                                        
                                        // Número de línea física dentro del Excel
                                        $numLinea     = $fila['linea'] ?? $fila['fila_excel'] ?? ($index + 1);

                                        // Limpieza básica
                                        $dniClean = strtolower(trim($valDni));
                                    @endphp

                                    @if($dniClean === 'dni' || strtolower(trim($valNombre)) === 'nombre')
                                        @continue
                                    @endif

                                    @if(empty($valDni) && empty($valNombre))
                                        @continue
                                    @endif

                                    <tr>
                                        <td class="text-center fw-bold text-muted">
                                            {{ $numLinea }}
                                            <input type="hidden" name="cuentas[{{ $index }}][linea]" value="{{ $numLinea }}">
                                            <input type="hidden" name="cuentas[{{ $index }}][no_colegiado]" value="{{ $valColegiado }}">
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark font-monospace fs-6">
                                                <i class="fas fa-id-badge me-1"></i>{{ $valColegiado }}
                                            </span>
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="cuentas[{{ $index }}][dni]" 
                                                   value="{{ $valDni }}" 
                                                   class="form-control form-control-sm input-searchable" 
                                                   required>
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="cuentas[{{ $index }}][nombre]" 
                                                   value="{{ $valNombre }}" 
                                                   class="form-control form-control-sm input-searchable" 
                                                   required>
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="cuentas[{{ $index }}][cuenta]" 
                                                   value="{{ $valCuenta }}" 
                                                   class="form-control form-control-sm input-searchable" 
                                                   required>
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="cuentas[{{ $index }}][concepto]" 
                                                   value="{{ $valConcepto }}" 
                                                   class="form-control form-control-sm input-searchable" 
                                                   required>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   step="0.01"
                                                   name="cuentas[{{ $index }}][valor_concepto]" 
                                                   value="{{ $valValor }}" 
                                                   class="form-control form-control-sm text-end input-searchable" 
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
                                        <span class="badge bg-danger fs-6">Fila {{ $log['linea'] ?? $loop->iteration }}</span>
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
    
    {{-- JS para Mover Columnas (ColReorder) --}}
    <script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>

    <script>
        $(document).ready(function() {
            const dtLanguage = {
                "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            };

            if ($('#tablaCuentas').length) {
                // 1. Configurar renderizado dinámico para buscar dentro de los inputs
                var table = $('#tablaCuentas').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [10, 25, 50, 100],
                    "language": dtLanguage,
                    "colReorder": true,
                    "orderCellsTop": true, // Mantiene el orden de click en los títulos principales
                    "columnDefs": [
                        {
                            "targets": "_all",
                            "render": function(data, type, row, meta) {
                                // Si es para buscar u ordenar, extraemos el valor actual del input o badge
                                if (type === 'filter' || type === 'sort') {
                                    var $cell = $('<div>').html(data);
                                    var $input = $cell.find('input');
                                    if ($input.length) {
                                        return $input.val();
                                    }
                                    return $cell.text().trim();
                                }
                                return data;
                            }
                        }
                    ]
                });

                // 2. Actualizar las búsquedas de DataTables cuando el usuario edita un input
                $('#tablaCuentas').on('keyup change', '.input-searchable', function() {
                    var cell = table.cell($(this).closest('td'));
                    cell.invalidate().draw(false);
                });

                // 3. Filtros individuales por columna
                $('#tablaCuentas thead .column-filter').on('keyup change clear', function() {
                    var colIndex = $(this).closest('th').index();
                    table.column(colIndex).search(this.value).draw();
                });

                // Evitar que al hacer clic en los inputs de filtro se reordene la columna
                $('#tablaCuentas thead .column-filter').on('click', function(e) {
                    e.stopPropagation();
                });

                // 4. Incluir inputs ocultos por paginación/filtros al enviar el formulario
                $('#formGuardarCuentas').on('submit', function(e) {
                    var form = this;
                    table.$('input').each(function() {
                        if (!$.contains(document, this)) {
                            $(form).append(
                                $('<input>')
                                    .attr('type', 'hidden')
                                    .attr('name', this.name)
                                    .attr('value', this.value)
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