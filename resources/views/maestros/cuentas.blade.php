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

        $catalogoConceptos = [
            'CUOTA COLEGIAL'             => 1,
            'AUTOMATICOS'                => 2,
            'ESTUDIO'                    => 3,
            'FIDUCIARIO REFINANCIAMIENTO' => 4,
            'HIPOTECARIO DIRECTO'        => 5,
            'PERSONAL'                   => 6,
            'PRESTAMOS COMPRA DEUDA'     => 7,
            'READECUACION DE DEUDA'      => 8,
            'VEHICULO DIRECTO'           => 9,
        ];
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
                        <small class="text-muted">Se van a procesar <strong>{{ count($datos) }}</strong> registros de cuentas.</small>
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
                                @foreach($datos as $index => $fila)
                                    @php
                                        $valColegiado = $fila['no_colegiado'] ?? $fila['numero_colegiado'] ?? 'N/A';
                                        $valDni       = $fila['dni'] ?? $fila['identidad'] ?? $fila[0] ?? '';
                                        $valNombre    = $fila['nombre'] ?? $fila[1] ?? '';
                                        $valCuenta    = $fila['cuenta'] ?? $fila['no_cuenta'] ?? $fila[2] ?? '';
                                        $valConcepto  = trim($fila['concepto'] ?? $fila['cuenta_concepto'] ?? $fila[3] ?? '');
                                        $valValor     = $fila['valor_concepto'] ?? $fila['valor'] ?? $fila[4] ?? '';
                                        
                                        $conceptoUpper = mb_strtoupper($valConcepto, 'UTF-8');
                                        $valTipoCuenta = 10;
                                        $conceptoSeleccionado = '';

                                        foreach ($catalogoConceptos as $nombreCatalogo => $idTipo) {
                                            if (str_contains($conceptoUpper, 'CUOTA') || str_contains($conceptoUpper, 'COLEGIAL')) {
                                                $conceptoSeleccionado = 'CUOTA COLEGIAL';
                                                $valTipoCuenta = 1;
                                                break;
                                            } elseif (str_contains($conceptoUpper, 'PERSONAL') || str_contains($conceptoUpper, 'PRESTAMO')) {
                                                $conceptoSeleccionado = 'PERSONAL';
                                                $valTipoCuenta = 6;
                                                break;
                                            } elseif (str_contains($conceptoUpper, $nombreCatalogo)) {
                                                $conceptoSeleccionado = $nombreCatalogo;
                                                $valTipoCuenta = $idTipo;
                                                break;
                                            }
                                        }

                                        $numLinea = $fila['linea'] ?? $fila['fila_excel'] ?? ($index + 2);
                                        $dniClean = strtolower(trim($valDni));
                                    @endphp

                                    @if($dniClean === 'dni' || strtolower(trim($valNombre)) === 'nombre')
                                        @continue
                                    @endif

                                    @if(empty($valDni) && empty($valNombre))
                                        @continue
                                    @endif

                                    <tr data-fila-excel="{{ $numLinea }}">
                                        <td class="text-center fw-bold text-muted">
                                            {{ $numLinea }}
                                            <input type="hidden" name="cuentas[{{ $index }}][linea]" value="{{ $numLinea }}">
                                            <input type="hidden" name="cuentas[{{ $index }}][no_colegiado]" value="{{ $valColegiado }}">
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
                                                    class="form-select form-control-sm select-concepto input-searchable" 
                                                    required>
                                                <option value="">-- Seleccionar --</option>
                                                @foreach($catalogoConceptos as $conceptoNombre => $idTipo)
                                                    <option value="{{ $conceptoNombre }}" 
                                                            data-tipo-id="{{ $idTipo }}"
                                                            {{ ($conceptoSeleccionado == $conceptoNombre || $valConcepto == $conceptoNombre) ? 'selected' : '' }}>
                                                        {{ $conceptoNombre }}
                                                    </option>
                                                @endforeach
                                                @if(!array_key_exists($valConcepto, $catalogoConceptos) && !empty($valConcepto) && empty($conceptoSeleccionado))
                                                    <option value="{{ $valConcepto }}" data-tipo-id="10" selected>{{ $valConcepto }}</option>
                                                @endif
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" 
                                                   name="cuentas[{{ $index }}][tipo_cuenta]" 
                                                   value="{{ $valTipoCuenta }}" 
                                                   class="form-control form-control-sm text-center fw-bold bg-light input-tipo-cuenta input-searchable" 
                                                   readonly 
                                                   tabindex="-1">
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

                // Cambios dinámicos de select y refresco
                $('#tablaCuentas').on('change', '.select-concepto', function() {
                    var $selectedOption = $(this).find('option:selected');
                    var tipoId = $selectedOption.data('tipo-id') || 10;
                    var $fila = $(this).closest('tr');
                    $fila.find('.input-tipo-cuenta').val(tipoId);

                    table.cell($(this).closest('td')).invalidate();
                    table.cell($fila.find('.input-tipo-cuenta').closest('td')).invalidate().draw(false);
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
        let numLinea = $fila.data('fila-excel');

        // Limpiar únicamente espacios extras
        let dniLimpio = String(dniTexto).trim();

        console.log(`📤 [Fila ${numLinea || index + 1}] Enviando DNI a verificar: "${dniLimpio}"`);

        if (!dniLimpio) {
            console.warn(`⚠️ [Fila ${numLinea || index + 1}] El DNI está vacío.`);
            $inputDni.addClass('is-invalid');
            erroresEncontrados++;
            return;
        }

        // Creamos una promesa envuelta para garantizar que Promise.all espere la resolución de todas
        let peticion = new Promise((resolve) => {
            $.ajax({
                url: "{{ route('cuentas.verificar-dni') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    dni: dniLimpio
                }
            }).done(function(res) {
                console.log(`📥 [Fila ${numLinea || index + 1}] Respuesta del Servidor:`, res);

                // ✅ ACEPTA 'success: true' O 'valido: true'
                let esValido = (res.success === true || res.valido === true || res === true);

                if (esValido) {
                    console.log(`✅ [Fila ${numLinea || index + 1}] DNI válido.`);
                    
                    // Actualiza input DNI con respuesta o mantén el limpio
                    $inputDni.val(res.dni_real || res.dni || dniLimpio);
                    $inputDni.removeClass('is-invalid').addClass('is-valid');

                    if (res.nombre_real) {
                        $fila.find('.input-nombre').val(res.nombre_real);
                    }
                    if (res.no_colegiado) {
                        $fila.find('.span-colegiado').html('<i class="fas fa-id-badge me-1"></i>' + res.no_colegiado);
                        $fila.find('input[name*="no_colegiado"]').val(res.no_colegiado);
                    }

                    if (typeof tableErrores !== 'undefined' && tableErrores && numLinea) {
                        let $trError = $('#error-fila-' + numLinea);
                        if ($trError.length) {
                            tableErrores.row($trError).remove().draw(false);
                            if (typeof actualizarEstadoErrores === 'function') {
                                actualizarEstadoErrores();
                            }
                        }
                    }
                } else {
                    console.error(`❌ [Fila ${numLinea || index + 1}] Inconsistencia:`, {
                        dniEnviado: dniLimpio,
                        respuestaBackend: res
                    });
                    $inputDni.removeClass('is-valid').addClass('is-invalid');
                    erroresEncontrados++;
                }
                resolve();
            }).fail(function(err) {
                console.error(`💥 [Fila ${numLinea || index + 1}] Error HTTP/Servidor (${err.status}):`, err.responseJSON || err.responseText);
                $inputDni.removeClass('is-valid').addClass('is-invalid');
                erroresEncontrados++;
                resolve();
            });
        });

        promesas.push(peticion);
    });

    // Esperar a que terminen todas las llamadas AJAX
    await Promise.all(promesas);

    Swal.close();

    console.log(`📊 === RESUMEN: Proceso finalizado. Errores pendientes: ${erroresEncontrados} ===`);

    if (erroresEncontrados === 0) {
        $('.alert-danger').fadeOut();
        $('#cardReporteErrores').fadeOut();
        $('#btnBloqueado').addClass('d-none');
        $('#btnGuardar').removeClass('d-none').prop('disabled', false);

        Swal.fire({
            icon: 'success',
            title: '¡Identidades Verificadas!',
            text: 'Todos los números coinciden correctamente con la tabla Maestros.',
            confirmButtonColor: '#198754'
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Inconsistencias Detectadas',
            text: 'Aún hay ' + erroresEncontrados + ' registro(s) cuyos números de DNI no coinciden con Maestros. Revisa la consola del navegador (F12).',
            confirmButtonColor: '#dc3545'
        });
    }
}
    </script>
@endpush
