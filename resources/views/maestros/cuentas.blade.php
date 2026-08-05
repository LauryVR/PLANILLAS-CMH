@extends('layouts.template')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReorder.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .filter-row input, .filter-row select {
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
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gestión de Cuentas y Retenciones</h2>
    
    {{-- Botón para reiniciar todo --}}
   <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalReiniciar">
        <i class="fas fa-trash-alt me-1"></i> Reiniciar / Cargar Nuevos Archivos
    </button>
</div>
    {{-- 1. Card Cargar Archivo --}}
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
                    {{-- Input para seleccionar el archivo --}}
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="archivo" class="form-label fw-bold">
                            Seleccione el archivo Excel (.xlsx / .xls)
                        </label>
                        <input type="file" id="archivo" name="archivo" class="form-control" accept=".xlsx,.xls" required>
                    </div>

                    {{-- Botones de acción organizados correctamente --}}
                    <div class="col-md-6 d-flex flex-wrap gap-2 justify-content-md-end">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload me-1"></i> Previsualizar
                        </button>

                        <button type="button" class="btn btn-success" onclick="exportarExcelPorConcepto('cuota')">
                            <i class="fas fa-file-excel me-1"></i> Descargar Cuota Colegial
                        </button>

                        <button type="button" class="btn btn-info text-white" onclick="exportarExcelPorConcepto('prestamo')">
                            <i class="fas fa-file-excel me-1"></i> Descargar Préstamos
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. Card Cargar Archivo de Retenciones --}}

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-file-invoice-dollar me-2"></i> Cargar Archivo Excel - Retenciones
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('retenciones.cargar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-10 mb-3 mb-md-0">
                        <label for="archivo_retencion" class="form-label fw-bold">
                            Seleccione el archivo Excel de Retenciones (.xlsx / .xls)
                        </label>
                        <input type="file" id="archivo_retencion" name="archivo_retencion" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-warning text-dark w-100 fw-bold">
                            <i class="fas fa-upload me-1"></i> Previsualizar Retenciones
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

{{-- 4. Formulario para Cargar Archivo de Entes Retenedores --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0">
            <i class="fas fa-file-upload me-2"></i> Cargar Archivo Excel - Motor de Entes Retenedores
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('cargar.entes.retenedores') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="archivo_entes" class="form-label fw-bold">Seleccione el archivo Excel de Entes Retenedores (.xlsx / .xls)</label>
                <div class="input-group">
                    <input type="file" class="form-control" id="archivo_entes" name="archivo_entes" accept=".xlsx, .xls, .csv" required>
                    <button type="submit" class="btn btn-secondary text-white">
                        <i class="fas fa-cogs me-1"></i> Previsualizar Entes
                    </button>
                </div>
                <div class="form-text">Asegúrese de que el archivo contenga las columnas de DNI y los valores de retención (cuotas, préstamos, etc.).</div>
            </div>
        </form>
    </div>
</div>

    {{-- TABLA DE ERRORES DEL EXCEL --}}
    @if(!empty($filasConError) && count($filasConError) > 0)

        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>¡Atención!</strong>
            Se encontraron <strong>{{ count($filasConError) }}</strong> registros con errores o campos inválidos.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <div class="card border-danger shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    Registros pendientes de corrección
                </h5>
            </div>

            <div class="card-body">
                <table id="tablaErroresCarga"
                       class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Fila</th>
                            <th>Error</th>
                            <th>DNI</th>
                            <th>Nombre</th>
                            <th>Concepto</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($filasConError as $error)
                            <tr>
                                <td>
                                    <span class="badge bg-danger">
                                        {{ $error['fila'] ?? '' }}
                                    </span>
                                </td>

                                <td class="text-danger fw-bold">
                                    {{ $error['mensaje'] ?? '' }}
                                </td>

                                <td>
                                    {{ $error['datos']['dni'] ?? $error['datos'][0] ?? '' }}
                                </td>

                                <td>
                                    {{ $error['datos']['nombre'] ?? $error['datos'][1] ?? '' }}
                                </td>

                                <td>
                                    {{ $error['datos']['concepto'] ?? $error['datos'][3] ?? '' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @endif

    {{-- 2. Tabla de Resultados Editables --}}
    @if(!empty(session('datos')) || !empty($datos))
        <form id="formGuardarCuentas" action="{{ route('cuentas.guardar') }}" method="POST">
            @csrf
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="fas fa-edit me-2"></i> Previsualización y Edición de Cuentas
                        </h5>
                        {{-- Corrección: Se asegura que $datos sea un array antes de contar --}}
                        <small class="text-muted">Se van a procesar <strong>{{ count($datos ?? []) }}</strong> registros de cuentas.</small>
                    </div>

                    <div class="d-flex gap-2">
                        @if(session('errores_excel'))
                            <button type="button" 
                                    id="btnVerificar" 
                                    class="btn btn-warning d-inline-flex align-items-center gap-2 fw-semibold shadow-sm"
                                    onclick="verificarCorrecciones()">
                                <i class="bi bi-shield-check fs-5"></i>
                                <span>Verificar</span>
                            </button>

                            <button type="submit" id="btnGuardar" class="btn btn-success d-none">
                                <i class="fas fa-save me-1"></i> Guardar Cambios
                            </button>
                            <button type="button" id="btnBloqueado" class="btn btn-secondary" disabled title="Corrija los errores reportados abajo para poder guardar">
                                <i class="fas fa-lock me-1"></i> Correcciones requeridas
                            </button>
                        @else
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Guardar Cambios
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
                                    <th>N° Ref. Cuenta</th>
                                    <th>Cuenta Concepto</th>
                                    <th width="110" class="text-center">Tipo Cuenta</th>
                                    <th>Valor Concepto</th>
                                </tr>
                                <tr class="filter-row bg-light">
                                    <th></th>
                                    <th><input type="text" class="form-control form-control-sm column-filter" placeholder="Filtrar..."></th>
                                    <th><input type="text" class="form-control form-control-sm column-filter" placeholder="Filtrar DNI..."></th>
                                    <th><input type="text" class="form-control form-control-sm column-filter" placeholder="Filtrar Nombre..."></th>
                                    <th><input type="text" class="form-control form-control-sm column-filter" placeholder="Filtrar Ref..."></th>
                                    <th><input type="text" class="form-control form-control-sm column-filter" placeholder="Filtrar Concepto..."></th>
                                    <th><input type="text" class="form-control form-control-sm column-filter" placeholder="Tipo..."></th>
                                    <th><input type="text" class="form-control form-control-sm column-filter" placeholder="Filtrar Valor..."></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($datos ?? [] as $index => $fila)

                                    @php
                                        $valColegiado = $fila['no_colegiado'] ?? $fila['numero_colegiado'] ?? 'N/A';
                                        $valDni       = $fila['dni'] ?? $fila['identidad'] ?? $fila[0] ?? '';
                                        $valNombre    = $fila['nombre'] ?? $fila[1] ?? '';
                                        $valCuenta    = $fila['cuenta'] ?? $fila['no_cuenta'] ?? $fila[2] ?? '';
                                        $valConcepto  = trim($fila['concepto'] ?? $fila['cuenta_concepto'] ?? $fila[3] ?? '');
                                        $valValor     = $fila['valor_concepto'] ?? $fila['valor'] ?? $fila[4] ?? '';

                                        // Buscar el concepto en la BD
                                        $tipoSeleccionado = collect($tiposCuenta ?? [])
                                            ->first(function ($item) use ($valConcepto) {
                                                return strtoupper(trim($item->nombre))
                                                    === strtoupper(trim($valConcepto));
                                            });

                                        // Si no existe, deja el concepto vacío
                                        $valConcepto = $tipoSeleccionado
                                            ? $tipoSeleccionado->nombre
                                            : '';

                                        $valTipoCuenta = $tipoSeleccionado
                                            ? $tipoSeleccionado->tipo_cuenta_id
                                            : '';

                                        // Marcar fila incompleta
                                        $esIncompleto = empty($valConcepto);

                                        $numLinea = $fila['linea']
                                            ?? $fila['fila_excel']
                                            ?? ($index + 2);

                                        $dniClean = strtolower(trim($valDni));
                                    @endphp

                                    @if($dniClean === 'dni' || strtolower(trim($valNombre)) === 'nombre')
                                        @continue
                                    @endif

                                    @if(empty($valDni) && empty($valNombre))
                                        @continue
                                    @endif

                                    <tr data-fila-excel="{{ $numLinea }}"
                                        class="{{ $esIncompleto ? 'table-danger' : '' }}">

                                        <td class="text-center fw-bold text-muted">

                                            {{ $numLinea }}

                                            @if($esIncompleto)
                                                <span class="badge bg-danger d-block mt-1">
                                                    ⚠ Corregir
                                                </span>
                                            @endif

                                            <input type="hidden"
                                                   name="cuentas[{{ $index }}][linea]"
                                                   value="{{ $numLinea }}">

                                            <input type="hidden"
                                                   name="cuentas[{{ $index }}][no_colegiado]"
                                                   value="{{ $valColegiado }}">
                                        </td>

                                        <td>
                                            <span class="badge bg-info text-dark font-monospace fs-6 span-colegiado">
                                                <i class="fas fa-id-badge me-1"></i>{{ $valColegiado }}
                                            </span>
                                        </td>

                                        <td>
                                            <input type="text"
                                                   name="cuentas[{{ $index }}][dni]"
                                                   value="{{ $valDni }}"
                                                   class="form-control form-control-sm input-searchable input-dni"
                                                   required>
                                        </td>

                                        <td>
                                            <input type="text"
                                                   name="cuentas[{{ $index }}][nombre]"
                                                   value="{{ $valNombre }}"
                                                   class="form-control form-control-sm input-searchable input-nombre"
                                                   required>
                                        </td>

                                        <td>
                                            <input type="text"
                                                   name="cuentas[{{ $index }}][cuenta]"
                                                   value="{{ $valCuenta }}"
                                                   class="form-control form-control-sm input-searchable font-monospace input-cuenta"
                                                   required>
                                        </td>

                                        <td>
                                            <select name="cuentas[{{ $index }}][concepto]"
                                                    class="form-select form-control-sm select-concepto input-searchable {{ $esIncompleto ? 'is-invalid' : '' }}"
                                                    required>

                                                <option value="">-- Seleccionar --</option>

                                                @foreach($tiposCuenta ?? [] as $tc)
                                                    <option value="{{ $tc->nombre }}"
                                                            data-id="{{ $tc->tipo_cuenta_id }}"
                                                            {{ $valConcepto == $tc->nombre ? 'selected' : '' }}>
                                                        {{ $tc->nombre }}
                                                    </option>
                                                @endforeach

                                            </select>
                                        </td>

                                        <td class="text-center">
                                            <input type="number"
                                                   name="cuentas[{{ $index }}][tipo_cuenta]"
                                                   value="{{ $valTipoCuenta }}"
                                                   class="form-control form-control-sm text-center fw-bold bg-light input-tipo-cuenta input-searchable"
                                                   readonly>
                                        </td>

                                        <td>
                                            <input type="number"
                                                   step="0.01"
                                                   name="cuentas[{{ $index }}][valor_concepto]"
                                                   value="{{ $valValor }}"
                                                   class="form-control form-control-sm text-end input-searchable input-valor"
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


  {{-- 3. Tabla de Previsualización de Retenciones Cargadas --}}
@if(session('retenciones_cargadas') && count(session('retenciones_cargadas')) > 0)
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-file-invoice-dollar me-2"></i> Retenciones Cargadas ({{ count(session('retenciones_cargadas')) }} registros)
            </h5>
        </div>
        <div class="card-body">

            {{-- Alerta detallada si hay errores en las retenciones --}}
            @if(session('errores_retencion_detalle'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> ¡Atención! Error en Planilla de Retención</h5>
                    <p class="mb-2">Por favor, corrija los siguientes errores en su archivo Excel y <strong>vuelva a cargar la planilla de retención</strong>:</p>
                    <ul class="mb-0">
                        @foreach(session('errores_retencion_detalle') as $errDetalle)
                            <li>{{ $errDetalle }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table id="tablaRetenciones" class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>DNI</th>
                            <th>Nombre</th>
                            <th>Monto a Cobrar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(session('retenciones_cargadas') as $ret)
                            @php
                                $dniBruto = trim($ret['dni'] ?? '');
                                
                                if (is_numeric($dniBruto)) {
                                    $dniLimpio = number_format((float)$dniBruto, 0, '', '');
                                } else {
                                    $dniLimpio = $dniBruto;
                                }

                                if (strlen($dniLimpio) === 12) {
                                    $dniLimpio = '0' . $dniLimpio;
                                }

                                $tieneError = $ret['tiene_error'] ?? false;
                                $detalleError = $ret['detalle_error'] ?? '';
                            @endphp
                            
                            {{-- Si tiene error, se pinta la fila completa de rojo con table-danger sin alterar columnas --}}
                            <tr class="{{ $tieneError ? 'table-danger' : '' }}" @if($tieneError) title="{{ $detalleError }}" @endif>
                                <td>{{ $dniLimpio }}</td>
                                <td>{{ $ret['nombre'] ?? '' }}</td>
                                <td>{{ $ret['monto'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

    {{-- 3. Reporte de Errores y Validaciones --}}
    @if(session('errores_excel'))
        <div id="cardReporteErrores" class="card shadow-sm border-danger mb-4">
            <div class="card-header bg-danger text-white d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i> Errores y Validación de Datos
                    </h5>
                    <small>Corrija los campos señalados en la tabla superior</small>
                </div>
                <span id="badgeCantErrores" class="badge bg-white text-danger fw-bold fs-6">
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
                                @php
                                    $lineaErr = $log['linea'] ?? $log['fila_excel'] ?? ($loop->iteration + 1);
                                @endphp
                                <tr id="error-fila-{{ $lineaErr }}" data-linea="{{ $lineaErr }}">
                                    <td class="text-center fw-bold">
                                        <span class="badge bg-danger fs-6">Fila {{ $lineaErr }}</span>
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
{{-- 4. Tabla de Previsualización de Entes Retenedores Cargados --}}
@if(session('entes_retenedores') && count(session('entes_retenedores')) > 0)
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">
                <i class="fas fa-cogs me-2"></i> Motor de Entes Retenedores ({{ count(session('entes_retenedores')) }} registros)
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaEntesRetenedores" class="table table-bordered table-striped align-middle text-center">
                    <thead>
                        <tr>
                            <th>DNI</th>
                            <th>Cuota Cole</th>
                            <th>Automático</th>
                            <th>Estudio</th>
                            <th>Refinancia</th>
                            <th>Readecuaci</th>
                            <th>Personal</th>
                            <th>Compra Deu</th>
                            <th>Hipotecario</th>
                            <th>Vehículo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(session('entes_retenedores') as $ente)
                            @php
                                $dniBruto = trim($ente['dni'] ?? '');
                                if (is_numeric($dniBruto)) {
                                    $dniLimpio = number_format((float)$dniBruto, 0, '', '');
                                } else {
                                    $dniLimpio = $dniBruto;
                                }
                                if (strlen($dniLimpio) === 12) {
                                    $dniLimpio = '0' . $dniLimpio;
                                }

                                $tieneError = $ente['tiene_error'] ?? false;
                                $numLinea = $ente['linea'] ?? '';
                                $detalleError = $ente['detalle_error'] ?? "El DNI no existe en la base de datos (Fila {$numLinea})";
                            @endphp
                            
                            <tr class="{{ $tieneError ? 'table-danger' : '' }}">
                                <td class="fw-bold text-start">
                                    {{ $dniLimpio }}
                                    @if($tieneError)
                                        <i class="fas fa-exclamation-circle text-danger ms-1" 
                                           data-bs-toggle="tooltip" 
                                           title="Fila {{ $numLinea }}: {{ $detalleError }}"></i>
                                        <div class="small text-danger">Fila {{ $numLinea }}: No existe en base de datos maestros</div>
                                    @endif
                                </td>
                                <td>{{ $ente['cuota_cole'] }}</td>
                                <td>{{ $ente['automatico'] }}</td>
                                <td>{{ $ente['estudio'] }}</td>
                                <td>{{ $ente['refinancia'] }}</td>
                                <td>{{ $ente['readecuaci'] }}</td>
                                <td>{{ $ente['personal'] }}</td>
                                <td>{{ $ente['compra_deu'] }}</td>
                                <td>{{ $ente['hipotecario'] }}</td>
                                <td>{{ $ente['vehiculo'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
</div>

<!-- Modal de Confirmación para Reiniciar -->
<div class="modal fade" id="modalReiniciar" tabindex="-1" aria-labelledby="modalReiniciarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalReiniciarLabel">
                    <i class="fas.fa-exclamation-triangle me-2"></i> Confirmar Reinicio
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                <h5>¿Está seguro de querer limpiar todos los datos cargados?</h5>
                <p class="text-muted mb-0">Esta acción eliminará todas las previsualizaciones actuales de cuentas, retenciones y entes retenedores.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="{{ route('cuentas.reiniciar') }}" class="btn btn-danger">
                    <i class="fas fa-check me-1"></i> Sí, reiniciar todo
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
        var table = null;
        var tableErrores = null;

        $(document).ready(function() {
            const dtLanguage = { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" };

            if ($('#tablaCuentas').length) {
                table = $('#tablaCuentas').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [10, 25, 50, 100],
                    "language": dtLanguage,
                    "colReorder": true,
                    "orderCellsTop": true,
                    "columnDefs": [
                        {
                            "targets": "_all",
                            "render": function(data, type, row, meta) {
                                if (type === 'filter' || type === 'sort') {
                                    var $cell = $('<div>').html(data);
                                    var $input = $cell.find('input, select');
                                    return $input.length ? $input.val() : $cell.text().trim();
                                }
                                return data;
                            }
                        }
                    ]
                });

                // Precargar automáticamente tipo_cuenta
                $('.select-concepto').each(function() {
                    var tipoId = $(this)
                        .find('option:selected')
                        .data('id') || '';

                    $(this)
                        .closest('tr')
                        .find('.input-tipo-cuenta')
                        .val(tipoId);
                });

                // Cambios dinámicos de select y refresco
               $('#tablaCuentas').on('change', '.select-concepto', function() {

                    var $fila = $(this).closest('tr');

                    var tipoId = $(this)
                        .find('option:selected')
                        .data('id') || '';

                    $fila.find('.input-tipo-cuenta')
                        .val(tipoId);

                    if (tipoId !== '') {

                        $(this).removeClass('is-invalid');

                        $fila.removeClass('table-danger');

                        $fila.find('.badge.bg-danger').remove();
                    }

                    if (table) {

                        table.cell(
                            $fila.find('.input-tipo-cuenta')
                                .closest('td')
                        ).invalidate().draw(false);
                    }

                });

                $('#tablaCuentas').on('change keyup', '.input-searchable', function() {
                    $(this).removeClass('is-invalid');
                    table.cell($(this).closest('td')).invalidate().draw(false);
                });

                $('#tablaCuentas thead .column-filter').on('keyup change clear', function() {
                    var colIndex = $(this).closest('th').index();
                    table.column(colIndex).search(this.value).draw();
                });

                $('#tablaCuentas thead .column-filter').on('click', function(e) {
                    e.stopPropagation();
                });

                // Captura TODOS los campos de TODAS las páginas de DataTables antes de enviar el formulario
                $('#formGuardarCuentas').on('submit', function(e) {
                    var form = this;
                    
                    $(form).find('input[type="hidden"].dt-generated').remove();

                    table.$('input, select').each(function() {
                        if (!$.contains(document, this)) {
                            $(form).append(
                                $('<input>')
                                    .attr('type', 'hidden')
                                    .addClass('dt-generated')
                                    .attr('name', this.name)
                                    .attr('value', $(this).val())
                            );
                        }
                    });
                });
            }

            if ($('#tablaErrores').length) {
                tableErrores = $('#tablaErrores').DataTable({
                    "pageLength": 5,
                    "lengthMenu": [5, 10, 25, 50],
                    "language": dtLanguage
                });
            }
        });

        const btnGuardar = document.querySelector('#btnGuardar');

        if(btnGuardar){
            btnGuardar.addEventListener('click', function(e){

                let hayErrores = false;

                document.querySelectorAll('#tablaCuentas tbody tr')
                    .forEach(function(fila){

                    let selectConcepto =
                        fila.querySelector('.select-concepto');

                    if(
                        selectConcepto &&
                        selectConcepto.value === ''
                    ){
                        hayErrores = true;
                        fila.classList.add('table-danger');
                    }
                });

                if(hayErrores){
                    e.preventDefault();

                    Swal.fire({
                        icon:'warning',
                        title:'Conceptos pendientes',
                        text:'Debe seleccionar un concepto antes de guardar.'
                    });
                }
            });
        }

        // Función para actualizar contadores y UI de errores
        function actualizarEstadoErrores() {
            let erroresRestantes = 0;
            if (tableErrores) {
                erroresRestantes = tableErrores.rows().count();
            } else {
                erroresRestantes = $('#tablaErrores tbody tr').length;
            }

            $('#badgeCantErrores').text(erroresRestantes + ' Fila(s) Afectada(s)');

            if (erroresRestantes === 0) {
                $('#cardReporteErrores').fadeOut(400);
                $('#btnVerificar').addClass('d-none');
                $('#btnBloqueado').addClass('d-none');
                $('#btnGuardar').removeClass('d-none');
            } else {
                $('#cardReporteErrores').fadeIn(400);
            }
        }

        /**
         * Lógica asíncrona para extraer el DNI, validar contra el servidor y reflejar en la UI
         */
        async function verificarCorrecciones() {
            console.clear();
            console.log("🔍 === INICIANDO VERIFICACIÓN DE DNI EN LÍNEA ===");

            Swal.fire({
                title: 'Verificando identidades...',
                text: 'Consultando la base de datos de Maestros...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            let promesas = [];
            let erroresEncontrados = 0;

            var filas = table ? table.rows().nodes() : $('table tbody tr');

            $(filas).each(function(index) {
                let $fila = $(this);
                let $inputDni = $fila.find('.input-dni');
                let dniTexto = $inputDni.length ? $inputDni.val() : '';
                let nombreTexto = $fila.find('.input-nombre').val() || '';
                
                // Número de línea
                let numLinea = $fila.data('fila-excel') || $fila.data('linea') || (index + 1);
                let dniLimpio = String(dniTexto).trim();

                console.log(`📤 [Fila ${numLinea}] Enviando DNI a verificar: "${dniLimpio}"`);

                // Caso: DNI Vacío
                if (!dniLimpio) {
                    console.warn(`⚠️ [Fila ${numLinea}] El DNI está vacío.`);
                    $inputDni.removeClass('is-valid').addClass('is-invalid');
                    erroresEncontrados++;

                    // Actualizar o crear fila en la tabla de errores
                    actualizarFilaTablaErrores(numLinea, dniLimpio, nombreTexto, "El campo DNI no puede estar vacío.");
                    return;
                }

                let peticion = new Promise((resolve) => {
                    $.ajax({
                        url: "{{ route('cuentas.verificar-dni') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            dni: dniLimpio
                        }
                    }).done(function(res) {
                        console.log(`📥 [Fila ${numLinea}] Respuesta del Servidor:`, res);

                        let esValido = (res.success === true || res.valido === true || res === true);

                        if (esValido) {
                            console.log(`✅ [Fila ${numLinea}] DNI válido.`);
                            
                            $inputDni.val(res.dni_real || res.dni || dniLimpio);
                            $inputDni.removeClass('is-invalid').addClass('is-valid');

                            if (res.nombre_real) {
                                $fila.find('.input-nombre').val(res.nombre_real);
                            }
                            if (res.no_colegiado) {
                                $fila.find('.span-colegiado').html('<i class="fas fa-id-badge me-1"></i>' + res.no_colegiado);
                                $fila.find('input[name*="no_colegiado"]').val(res.no_colegiado);
                            }

                            // 1. SI ES VÁLIDO: REMOVER DE LA TABLA DE ERRORES
                            removerFilaTablaErrores(numLinea);

                        } else {
                            console.error(`❌ [Fila ${numLinea}] Inconsistencia detectada.`);
                            $inputDni.removeClass('is-valid').addClass('is-invalid');
                            erroresEncontrados++;

                            // 2. SI SIGUE CON ERROR: ACTUALIZAR O INSERTAR EN LA TABLA DE ERRORES
                            let mensajeError = res.mensaje || `La identidad/DNI '${dniLimpio}' no se encuentra registrada en Maestros.`;
                            actualizarFilaTablaErrores(numLinea, dniLimpio, nombreTexto, mensajeError);
                        }
                        resolve();
                    }).fail(function(err) {
                        console.error(`💥 [Fila ${numLinea}] Error HTTP/Servidor (${err.status})`);
                        $inputDni.removeClass('is-valid').addClass('is-invalid');
                        erroresEncontrados++;

                        let msgError = (err.responseJSON && err.responseJSON.mensaje) ? err.responseJSON.mensaje : 'Error al consultar servidor.';
                        actualizarFilaTablaErrores(numLinea, dniLimpio, nombreTexto, msgError);
                        resolve();
                    });
                });

                promesas.push(peticion);
            });

            await Promise.all(promesas);

            Swal.close();

            // Sincronizar contadores globales de la UI
            actualizarEstadoErrores();

            console.log(`📊 === RESUMEN: Proceso finalizado. Errores pendientes: ${erroresEncontrados} ===`);

            if (erroresEncontrados === 0) {
                $('.alert-danger').fadeOut();
                $('#cardReporteErrores').fadeOut();
                $('#btnBloqueado').addClass('d-none');
                $('#btnVerificar').addClass('d-none');
                $('#btnGuardar').removeClass('d-none').prop('disabled', false);

                Swal.fire({
                    icon: 'success',
                    title: '¡Identidades Verificadas!',
                    text: 'Todos los números coinciden correctamente con la tabla Maestros.',
                    confirmButtonColor: '#198754'
                });
            } else {
                $('#cardReporteErrores').fadeIn();
                $('#btnGuardar').addClass('d-none');
                $('#btnBloqueado').removeClass('d-none');

                Swal.fire({
                    icon: 'error',
                    title: 'Inconsistencias Detectadas',
                    text: 'Aún hay ' + erroresEncontrados + ' registro(s) cuyos números de DNI no coinciden con Datos Maestros.',
                    confirmButtonColor: '#dc3545'
                });
            }
        }

        // 🛠️ FUNCIÓN AUXILIAR 1: Buscar, actualizar o CREAR DINÁMICAMENTE filas en la tabla de errores
        function actualizarFilaTablaErrores(numLinea, dniNuevo, nombre, mensajeError) {
            // Asegurar que el contenedor de la tabla sea visible
            if ($('#cardReporteErrores').is(':hidden')) {
                $('#cardReporteErrores').fadeIn();
            }

            let htmlBadge = `<span class="badge bg-danger fs-6">Fila ${numLinea}</span>`;
            let htmlCampo = `<span class="fw-bold text-secondary">Identidad</span>`;
            let htmlValores = `<strong class="text-danger">DNI: ${dniNuevo}</strong> | <strong>Nombre: ${nombre}</strong>`;
            let htmlMensaje = `<ul class="mb-0 text-danger ps-3"><li><i class="fas fa-times-circle me-1"></i>${mensajeError}</li></ul>`;

            let $tr = $('#error-fila-' + numLinea);

            if (!$tr.length) {
                // Buscar por texto en caso de que falte el id atributado
                $('#tablaErrores tbody tr').each(function() {
                    let txt = $(this).find('td:first-child').text().trim();
                    if (txt === 'Fila ' + numLinea || txt === String(numLinea)) {
                        $tr = $(this);
                        return false;
                    }
                });
            }

            if ($tr && $tr.length) {
                // CASO A: Actualizar fila existente
                $tr.find('td').eq(2).html(htmlValores);
                $tr.find('td').eq(3).html(htmlMensaje);

                if (typeof tableErrores !== 'undefined' && tableErrores) {
                    tableErrores.row($tr).invalidate().draw(false);
                }
            } else {
                // CASO B: Crear nueva fila dinámicamente si el error surgió después
                if (typeof tableErrores !== 'undefined' && tableErrores) {
                    let nuevaFilaNodo = tableErrores.row.add([
                        htmlBadge,
                        htmlCampo,
                        htmlValores,
                        htmlMensaje
                    ]).draw(false).node();

                    $(nuevaFilaNodo).attr('id', 'error-fila-' + numLinea);
                    $(nuevaFilaNodo).attr('data-linea', numLinea);
                    $(nuevaFilaNodo).find('td:first-child').addClass('text-center fw-bold');
                } else {
                    let trHtml = `
                        <tr id="error-fila-${numLinea}" data-linea="${numLinea}">
                            <td class="text-center fw-bold">${htmlBadge}</td>
                            <td>${htmlCampo}</td>
                            <td>${htmlValores}</td>
                            <td>${htmlMensaje}</td>
                        </tr>
                    `;
                    $('#tablaErrores tbody').append(trHtml);
                }
            }

            // Actualizar contadores
            actualizarEstadoErrores();
        }

        // 🛠️ FUNCIÓN AUXILIAR 2: Remover la fila si el error se corrigió por completo
        function removerFilaTablaErrores(numLinea) {
            if (typeof tableErrores === 'undefined' || !tableErrores) return;

            let $tr = $('#error-fila-' + numLinea);

            if (!$tr.length) {
                $('#tablaErrores tbody tr').each(function() {
                    let txt = $(this).find('td:first-child').text().trim();
                    if (txt === 'Fila ' + numLinea || txt === String(numLinea)) {
                        $tr = $(this);
                        return false;
                    }
                });
            }

            if ($tr && $tr.length) {
                tableErrores.row($tr).remove().draw(false);
                actualizarEstadoErrores();
            }
        }
        
        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('select-concepto')) {
                let select = e.target;
                let selectedOption = select.options[select.selectedIndex];
                let tipoCuentaId = selectedOption.getAttribute('data-id') || '';
                let fila = select.closest('tr');
                let inputTipo = fila.querySelector('.input-tipo-cuenta');

                if (inputTipo) {
                    inputTipo.value = tipoCuentaId;
                }

                if (tipoCuentaId !== '') {
                    select.classList.remove('is-invalid');
                    fila.classList.remove('table-danger');
                    let badge = fila.querySelector('.badge.bg-danger');
                    if (badge) {
                        badge.remove();
                    }
                }
            }
        });


// Función para exportar según el concepto seleccionado desde la tabla actual
function exportarExcelPorConcepto(tipo) {
    let registros = [];
    
    // Obtener las filas de la tabla de forma segura (funciona con DataTables y HTML plano)
    let filas = (typeof table !== 'undefined' && table) ? table.rows().nodes() : $('#tablaCuentas tbody tr');

    $(filas).each(function(index, fila) {
        let $fila = $(fila);
        
        // Extraer celdas de forma limpia asegurando que cada columna tenga su valor correcto
        let noColegiado = $fila.find('input[name*="no_colegiado"]').val() || $fila.find('td').eq(1).text().trim();
        let dni = $fila.find('.input-dni').val() || $fila.find('td').eq(2).text().trim();
        let nombre = $fila.find('.input-nombre').val() || $fila.find('td').eq(3).text().trim();
        let numRef = $fila.find('input[name*="num_ref"]').val() || $fila.find('td').eq(4).text().trim();
        let concepto = $fila.find('.select-concepto').val() || $fila.find('td').eq(5).text().trim();
        let tipoCuenta = $fila.find('.input-tipo-cuenta').val() || $fila.find('td').eq(6).text().trim();
        let valor = $fila.find('input[name*="valor"]').val() || $fila.find('td').eq(7).text().trim();

        // Evitar agregar filas vacías
        if (noColegiado !== "" || dni !== "") {
            registros.push({
                no_colegiado: noColegiado,
                dni: dni,
                nombre: nombre,
                num_ref: numRef,
                cuenta_concepto: concepto,
                tipo_cuenta: tipoCuenta,
                valor_concepto: valor
            });
        }
    });

    if (registros.length === 0) {
        Swal.fire('Atención', 'No hay registros en la tabla para exportar.', 'warning');
        return;
    }

    // Enviar los datos mediante un formulario dinámico POST oculto hacia Laravel
    let form = document.createElement('form');
    form.method = 'POST';
    form.action = "{{ route('cuentas.exportar.concepto') }}";

    let csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = "{{ csrf_token() }}";
    form.appendChild(csrfInput);

    let tipoInput = document.createElement('input');
    tipoInput.type = 'hidden';
    tipoInput.name = 'tipo_filtro';
    tipoInput.value = tipo;
    form.appendChild(tipoInput);

    let dataInput = document.createElement('input');
    dataInput.type = 'hidden';
    dataInput.name = 'registros';
    dataInput.value = JSON.stringify(registros);
    form.appendChild(dataInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

    </script>
@endpush
