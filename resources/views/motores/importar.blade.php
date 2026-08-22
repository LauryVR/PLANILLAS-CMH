@extends('layouts.template')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Carga Masiva y Gestión de Detalles</h2>
            <p class="text-muted small mb-0">Importe registros masivos o consulte y edite los detalles de configuración por motor.</p>
        </div>
        <a href="{{ route('inicio') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fas fa-arrow-left me-1"></i> Volver a Inicio
        </a>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif



<!-- Mensajes de Error (La validación que agregamos en el controlador) -->
@if ($errors->any())
    <div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border: 1px solid #f5c6cb; border-radius: 4px;">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

    <div class="row mb-4">
        {{-- Formulario de Carga --}}
        <div class="col-lg-6 mb-4 mb-lg-0">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 p-2 rounded-3 text-success me-3">
                            <i class="fas fa-file-excel fa-lg"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Subir Archivo Excel o CSV</h5>
                    </div>
                    
                    <form action="{{ route('motor.importar') }}" method="POST" enctype="multipart/form-data" id="form-importar">
                        @csrf
                        <div class="mb-3">
                            <label for="motor_retencion_id_upload" class="form-label fw-semibold small text-secondary">Seleccione el Motor Configurado (Ente Retenedor):</label>
                            <select name="motor_retencion_id" id="motor_retencion_id_upload" class="form-select" required>
                                <option value="">-- Seleccione Motor y Ente --</option>
                                @foreach($motores as $motor)
                                    <option value="{{ $motor->id }}">
                                        {{ $motor->nombre_motor }} (Ente: {{ $motor->enteRetencion->nombre ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="archivo" class="form-label fw-semibold small text-secondary">Archivo :</label>
                            <input type="file" name="archivo" id="archivo" class="form-control" accept=".xlsx, .xls, .csv" required>
                        </div>

                        <button type="button" id="btn-previsualizar" class="btn btn-info text-white w-100 fw-bold py-2 shadow-sm rounded-pill mb-2">
                            <i class="fas fa-eye me-2"></i> Previsualizar Datos
                        </button>

                        <button type="submit" id="btn-procesar" class="btn btn-primary w-100 fw-bold py-2 shadow-sm rounded-pill" style="display: none;">
                            <i class="fas fa-upload me-2"></i> Confirmar y Procesar Carga Masiva
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Guía de Formato del Excel --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-light">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-info-circle text-primary me-2"></i> Estructura Requerida</h5>
                    <p class="text-muted small">Su archivo debe contener las siguientes columnas en orden:</p>
                    
                    <ul class="list-unstyled small text-secondary">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i><strong>Columna 1:</strong> DNI</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i><strong>Columna 2:</strong> Cuota Colegial</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i><strong>Columna 3:</strong> Automáticos</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i><strong>Columna 4:</strong> Estudio</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i><strong>Columna 5:</strong> Refinanciamiento</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i><strong>Columnas 6 al 10:</strong> Readecuación, Personal, Compra Deuda, Hipotecario, Vehículo, Empleado</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
  {{-- Sección de Visualización y Edición Estilo Excel por Motor --}}
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form action="{{ route('detalles.actualizar.masivo') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row align-items-center mb-4">
                <div class="col-md-5">
                    <h5 class="fw-bold mb-1"><i class="fas fa-table text-primary me-2"></i> Registros del Motor Seleccionado</h5>
                    <p class="text-muted small mb-0">Modifique los valores (0 o 1) directamente en la tabla y guarde los cambios abajo.</p>
                </div>
                
                {{-- Selector de Motor y Botón de Descarga --}}
                <div class="col-md-7 d-flex gap-2 justify-content-end">
                    <div class="flex-grow-1">
                        <select id="filtro_motor_id" class="form-select border-primary">
                            <option value="">-- Filtrar tabla por Motor --</option>
                            @foreach($motores as $motor)
                                <option value="{{ $motor->id }}">{{ $motor->nombre_motor }} (Ente: {{ $motor->enteRetencion->nombre ?? 'N/A' }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <a href="#" id="btn-descargar-excel" class="btn btn-success text-nowrap" style="display: none;" title="Descargar datos actuales en Excel">
                            <i class="fas fa-file-excel me-1"></i> Descargar Excel
                        </a>
                    </div>
                </div>
            </div>
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="tabla-detalles">
        <thead class="table-light text-uppercase fs-7 text-secondary">
            <tr>
                <th>DNI</th>
                <th>N° Colegiado</th>
                <th>Nombre</th>
                <th>Cuota</th>
                <th>Auto</th>
                <th>Estudio</th>
                <th>Refi</th>
                <th>Readecuación</th>
                <th>Personal</th>
                <th>Compra Deuda</th>
                <th>Hipoteca</th>
                <th>Vehículo</th>
                <th>Empleado</th>
                <th class="text-center text-nowrap">Últ. Act.</th>
            </tr>
        </thead>

        <tbody id="contenido-detalles">

            @isset($detalles)

                @foreach($detalles as $detalle)

                <tr>

                    <td>
                        {{ $detalle->dni }}
                        <input
                            type="hidden"
                            name="detalles[{{ $detalle->id }}][dni]"
                            value="{{ $detalle->dni }}">
                    </td>

                    <td>{{ $detalle->numero_colegiado }}</td>

                    <td>{{ $detalle->colegiado_nombre }}</td>

                    <td>
                        <input type="number"
                            name="detalles[{{ $detalle->id }}][cuota_colegial]"
                            value="{{ $detalle->cuota_colegial }}"
                            class="form-control form-control-sm text-center border-0 bg-light p-1"
                            min="0" max="1" step="1"
                            style="width:85px;">
                    </td>

                    <td>
                        <input type="number"
                            name="detalles[{{ $detalle->id }}][automaticos]"
                            value="{{ $detalle->automaticos }}"
                            class="form-control form-control-sm text-center border-0 bg-light p-1"
                            min="0" max="1" step="1"
                            style="width:85px;">
                    </td>

                    <td>
                        <input type="number"
                            name="detalles[{{ $detalle->id }}][estudio]"
                            value="{{ $detalle->estudio }}"
                            class="form-control form-control-sm text-center border-0 bg-light p-1"
                            min="0" max="1" step="1"
                            style="width:85px;">
                    </td>

                    <td>
                        <input type="number"
                            name="detalles[{{ $detalle->id }}][refinanciamiento]"
                            value="{{ $detalle->refinanciamiento }}"
                            class="form-control form-control-sm text-center border-0 bg-light p-1"
                            min="0" max="1" step="1"
                            style="width:85px;">
                    </td>

                    <td>
                        <input type="number"
                            name="detalles[{{ $detalle->id }}][readecuacion]"
                            value="{{ $detalle->readecuacion }}"
                            class="form-control form-control-sm text-center border-0 bg-light p-1"
                            min="0" max="1" step="1"
                            style="width:85px;">
                    </td>

                    <td>
                        <input type="number"
                            name="detalles[{{ $detalle->id }}][personal]"
                            value="{{ $detalle->personal }}"
                            class="form-control form-control-sm text-center border-0 bg-light p-1"
                            min="0" max="1" step="1"
                            style="width:85px;">
                    </td>

                    <td>
                        <input type="number"
                            name="detalles[{{ $detalle->id }}][compra_deuda]"
                            value="{{ $detalle->compra_deuda }}"
                            class="form-control form-control-sm text-center border-0 bg-light p-1"
                            min="0" max="1" step="1"
                            style="width:85px;">
                    </td>

                    <td>
                        <input type="number"
                            name="detalles[{{ $detalle->id }}][hipotecario]"
                            value="{{ $detalle->hipotecario }}"
                            class="form-control form-control-sm text-center border-0 bg-light p-1"
                            min="0" max="1" step="1"
                            style="width:85px;">
                    </td>

                    <td>
                        <input type="number"
                            name="detalles[{{ $detalle->id }}][vehiculo]"
                            value="{{ $detalle->vehiculo }}"
                            class="form-control form-control-sm text-center border-0 bg-light p-1"
                            min="0" max="1" step="1"
                            style="width:85px;">
                    </td>

                    <td>
                        <input type="number"
                            name="detalles[{{ $detalle->id }}][empleado]"
                            value="{{ $detalle->empleado ?? 0 }}"
                            class="form-control form-control-sm text-center border-0 bg-light p-1"
                            min="0" max="1" step="1"
                            style="width:85px;">
                    </td>

                    <td class="text-center text-nowrap">

                        <div
                            class="small text-muted"
                            style="font-size:0.75rem;"
                            title="Actualizado: {{ $detalle->updated_at }}">

                            {{ $detalle->updated_at
                                ? \Carbon\Carbon::parse($detalle->updated_at)->format('d/m/Y H:i')
                                : '-' }}

                        </div>

                        <span
                            class="badge bg-light text-secondary border"
                            style="font-size:0.7rem;"
                            title="Actualizado por ID: {{ $detalle->updated_by ?? 'N/D' }}">

                            <i class="fas fa-user me-1"></i>
                            {{ $detalle->updated_by ?? 'N/D' }}

                        </span>

                    </td>

                </tr>

                @endforeach

            @else

                <tr>
                    <td colspan="14" class="text-center text-muted py-5">

                        <i class="fas fa-search fa-2x text-light mb-2"></i>

                        <p class="mb-0">
                            Seleccione un motor en el filtro superior o previsualice un archivo para ver los registros.
                        </p>

                    </td>
                </tr>

            @endisset

        </tbody>
    </table>
</div>

            <!-- Controles de Paginación -->
            <div class="d-flex justify-content-between align-items-center mt-3" id="paginacion-container" style="display: none;">
                <div class="text-muted small" id="paginacion-info">
                    Mostrando 0 a 0 de 0 registros
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="paginacion-links">
                        <!-- Botones generados dinámicamente por JS -->
                    </ul>
                </nav>
            </div>

            <!-- Botón para Guardar con ID asignado -->
            <div class="mt-4 text-end">
                <button type="submit" id="btn-guardar-masivo" class="btn btn-success px-4 shadow-sm">
                    <i class="fas fa-save me-2"></i> Guardar Cambios Masivos de la Tabla
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Control dinámico para el botón de descarga según el motor seleccionado
    document.getElementById('filtro_motor_id').addEventListener('change', function() {
        let motorId = this.value;
        let btnDescargar = document.getElementById('btn-descargar-excel');
        
        if (motorId) {
            btnDescargar.href = `/motores/${motorId}/descargar`;
            btnDescargar.style.display = 'inline-block';
        } else {
            btnDescargar.style.display = 'none';
        }
    });
</script>
@endpush

    {{-- Modal de Notificaciones --}}
    <div class="modal fade" id="notificacionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="notifTitle">Información</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4" id="notifMessage"></div>
                <div class="modal-footer border-0 pt-0 justify-content-center">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    // Variables globales para la paginación
    let todosLosRegistrosHtml = [];
    let paginaActual = 1;
    let registrosPorPagina = 10; // Puedes cambiar la cantidad de filas por página aquí

    function renderizarTablaPaginada() {
        let tbody = document.getElementById('contenido-detalles');
        let infoPaginacion = document.getElementById('paginacion-info');
        let contenedorPaginacion = document.getElementById('paginacion-container');

        if (todosLosRegistrosHtml.length === 0) {
            if (contenedorPaginacion) contenedorPaginacion.style.display = 'none';
            return;
        }

        if (contenedorPaginacion) contenedorPaginacion.style.display = 'flex';

        let totalRegistros = todosLosRegistrosHtml.length;
        let totalPaginas = Math.ceil(totalRegistros / registrosPorPagina);

        if (paginaActual > totalPaginas) paginaActual = totalPaginas;
        if (paginaActual < 1) paginaActual = 1;

        let inicio = (paginaActual - 1) * registrosPorPagina;
        let fin = inicio + registrosPorPagina;
        let registrosPagina = todosLosRegistrosHtml.slice(inicio, fin);

        // Limpiar y poblar el tbody con los registros de la página actual
        tbody.innerHTML = '';
        registrosPagina.forEach(tr => tbody.appendChild(tr));

        // Actualizar texto de información
        let mostrarHasta = Math.min(fin, totalRegistros);
        infoPaginacion.innerText = `Mostrando ${totalRegistros > 0 ? inicio + 1 : 0} a ${mostrarHasta} de ${totalRegistros} registros`;

        // Renderizar botones de paginación
        renderizarBotonesPaginacion(totalPaginas);
    }

    function renderizarBotonesPaginacion(totalPaginas) {
        let ulLinks = document.getElementById('paginacion-links');
        ulLinks.innerHTML = '';

        if (totalPaginas <= 1) return;

        // Botón Anterior
        let liAnterior = document.createElement('li');
        liAnterior.className = `page-item ${paginaActual === 1 ? 'disabled' : ''}`;
        liAnterior.innerHTML = `<a class="page-link" href="#" data-pagina="${paginaActual - 1}">Anterior</a>`;
        liAnterior.addEventListener('click', function(e) {
            e.preventDefault();
            if (paginaActual > 1) {
                paginaActual--;
                renderizarTablaPaginada();
            }
        });
        ulLinks.appendChild(liAnterior);

        // Botones numéricos (limitados para no saturar si son muchas páginas)
        let maxBotones = 5;
        let inicioPag = Math.max(1, paginaActual - Math.floor(maxBotones / 2));
        let finPag = Math.min(totalPaginas, inicioPag + maxBotones - 1);

        if (finPag - inicioPag + 1 < maxBotones) {
            inicioPag = Math.max(1, finPag - maxBotones + 1);
        }

        for (let i = inicioPag; i <= finPag; i++) {
            let li = document.createElement('li');
            li.className = `page-item ${paginaActual === i ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#" data-pagina="${i}">${i}</a>`;
            li.addEventListener('click', function(e) {
                e.preventDefault();
                paginaActual = i;
                renderizarTablaPaginada();
            });
            ulLinks.appendChild(li);
        }

        // Botón Siguiente
        let liSiguiente = document.createElement('li');
        liSiguiente.className = `page-item ${paginaActual === totalPaginas ? 'disabled' : ''}`;
        liSiguiente.innerHTML = `<a class="page-link" href="#" data-pagina="${paginaActual + 1}">Siguiente</a>`;
        liSiguiente.addEventListener('click', function(e) {
            e.preventDefault();
            if (paginaActual < totalPaginas) {
                paginaActual++;
                renderizarTablaPaginada();
            }
        });
        ulLinks.appendChild(liSiguiente);
    }

    // Control dinámico para el botón de descarga según el motor seleccionado
    const filtroMotorGlobal = document.getElementById('filtro_motor_id');
    if (filtroMotorGlobal) {
        filtroMotorGlobal.addEventListener('change', function() {
            let motorId = this.value;
            let btnDescargar = document.getElementById('btn-descargar-excel');
            
            if (btnDescargar) {
                if (motorId) {
                    btnDescargar.href = `/motores/${motorId}/descargar`;
                    btnDescargar.style.display = 'inline-block';
                } else {
                    btnDescargar.style.display = 'none';
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        function mostrarNotificacion(titulo, mensaje, esError = false) {
            document.getElementById('notifTitle').innerText = titulo;
            document.getElementById('notifMessage').innerHTML = `<p class="${esError ? 'text-danger' : 'text-success'} mb-0">${mensaje}</p>`;
            let modalElement = document.getElementById('notificacionModal');
            if (modalElement) {
                let modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }

        // Previsualización de archivos subidos
        const btnPrevisualizar = document.getElementById('btn-previsualizar');
        if (btnPrevisualizar) {
            btnPrevisualizar.addEventListener('click', function() {
                let motorId = document.getElementById('motor_retencion_id_upload').value;
                let fileInput = document.getElementById('archivo');
                let tbody = document.getElementById('contenido-detalles');
                let btnGuardarMasivo = document.getElementById('btn-guardar-masivo');
                let contenedorPaginacion = document.getElementById('paginacion-container');

                if (!motorId) {
                    mostrarNotificacion("Atención", "Debe seleccionar un Motor Configurado / Ente Retenedor antes de previsualizar el archivo.", true);
                    return;
                }

                if (fileInput.files.length === 0) {
                    mostrarNotificacion("Atención", "Por favor seleccione un archivo Excel primero.", true);
                    return;
                }

                let formData = new FormData();
                formData.append('archivo', fileInput.files[0]);
                formData.append('motor_retencion_id', motorId);
                formData.append('_token', '{{ csrf_token() }}');

                tbody.innerHTML = `<tr><td colspan="14" class="text-center py-4"><div class="spinner-border text-primary me-2"></div>Procesando y cruzando datos con la base de datos...</td></tr>`;
                if (contenedorPaginacion) contenedorPaginacion.style.display = 'none';

          fetch('{{ route("motor.previsualizar") }}', {
    method: 'POST',
    body: formData
})
.then(async response => {

    const text = await response.text();

    let json;

    try {
        json = JSON.parse(text);
    } catch (e) {

        console.error('Respuesta del servidor:', text);

        throw new Error(
            `El servidor respondió con HTTP ${response.status} y no devolvió JSON válido.`
        );
    }

    if (!response.ok) {
        throw new Error(
            json.error || `Error HTTP ${response.status}`
        );
    }

    return json;

})
.then(responseObj => {

    console.log('Respuesta recibida:', responseObj);

    todosLosRegistrosHtml = [];
    paginaActual = 1;

    let contador = 0;

    let filasData = responseObj.data || [];
    let tieneErrores = responseObj.tiene_errores || false;

    if (!Array.isArray(filasData)) {
        filasData = [];
    }

    let dnisVistos = {};
    let filasConDuplicados = [];

    filasData.forEach((item, index) => {

        let numeroFilaExcel = index + 2;

        let dniLimpio = item.dni
            ? String(item.dni).trim()
            : '';

        if (dniLimpio) {

            if (dnisVistos[dniLimpio]) {

                filasConDuplicados.push(numeroFilaExcel);

                item.es_valido = false;

                let primeraFila = dnisVistos[dniLimpio];

                if (!filasConDuplicados.includes(primeraFila)) {
                    filasConDuplicados.push(primeraFila);
                }

            } else {

                dnisVistos[dniLimpio] = numeroFilaExcel;
            }
        }
    });

    if (filasConDuplicados.length > 0) {
        filasConDuplicados.sort((a, b) => a - b);
        tieneErrores = true;
    }

    filasData.forEach(item => {

        let tr = document.createElement('tr');

        if (!item.es_valido) {
            tr.classList.add('table-danger');
        }

        tr.innerHTML = `
            <td>
                ${item.dni ?? ''}
                <input type="hidden" name="detalles[${item.id ?? ''}][dni]" value="${item.dni ?? ''}">
            </td>
            <td class="${!item.es_valido ? 'text-danger fw-bold' : ''}">
                ${item.numero_colegiado ?? 'N/A'}
            </td>
            <td class="${!item.es_valido ? 'text-danger fw-bold' : ''}">
                ${item.nombre ?? 'N/A'}
            </td>
            <td><input type="number" value="${item.cuota ?? 0}" class="form-control form-control-sm"></td>
            <td><input type="number" value="${item.auto ?? 0}" class="form-control form-control-sm"></td>
            <td><input type="number" value="${item.estudio ?? 0}" class="form-control form-control-sm"></td>
            <td><input type="number" value="${item.refi ?? 0}" class="form-control form-control-sm"></td>
            <td><input type="number" value="${item.readecuacion ?? 0}" class="form-control form-control-sm"></td>
            <td><input type="number" value="${item.personal ?? 0}" class="form-control form-control-sm"></td>
            <td><input type="number" value="${item.compra_deuda ?? 0}" class="form-control form-control-sm"></td>
            <td><input type="number" value="${item.hipotecario ?? 0}" class="form-control form-control-sm"></td>
            <td><input type="number" value="${item.vehiculo ?? 0}" class="form-control form-control-sm"></td>
            <td><input type="number" value="${item.empleado ?? 0}" class="form-control form-control-sm"></td>
            <td class="text-center text-nowrap">
                <div class="small text-muted" style="font-size: 0.75rem;">-</div>
                <span class="badge bg-light text-secondary border">
                    <i class="fas fa-user me-1"></i>N/D
                </span>
            </td>
        `;

        todosLosRegistrosHtml.push(tr);
        contador++;
    });

    renderizarTablaPaginada();

    let btnProcesar = document.getElementById('btn-procesar');

    if (contador === 0) {

        tbody.innerHTML = `
            <tr>
                <td colspan="14" class="text-center text-danger py-4">
                    No se encontraron registros válidos.
                </td>
            </tr>
        `;

        if (btnProcesar) btnProcesar.style.display = 'none';
        if (btnGuardarMasivo) btnGuardarMasivo.style.display = 'none';
        if (contenedorPaginacion) contenedorPaginacion.style.display = 'none';

    } else if (filasConDuplicados.length > 0) {

        if (btnProcesar) btnProcesar.style.display = 'none';
        if (btnGuardarMasivo) btnGuardarMasivo.style.display = 'none';

        mostrarNotificacion(
            "Atención: DNIs Duplicados",
            `Filas duplicadas: ${filasConDuplicados.join(', ')}`,
            true
        );

    } else if (tieneErrores) {

        if (btnProcesar) btnProcesar.style.display = 'none';
        if (btnGuardarMasivo) btnGuardarMasivo.style.display = 'none';

        mostrarNotificacion(
            "Atención",
            "Existen registros no encontrados en el sistema.",
            true
        );

    } else {

        if (btnProcesar) btnProcesar.style.display = 'block';

        if (btnGuardarMasivo) {
            btnGuardarMasivo.style.display = 'inline-block';
            btnGuardarMasivo.disabled = false;
        }

        btnPrevisualizar.style.display = 'none';

        mostrarNotificacion(
            "Previsualización Lista",
            "El archivo fue leído correctamente."
        );
    }

})
.catch(error => {

    console.error("Error en previsualización:", error);

    tbody.innerHTML = `
        <tr>
            <td colspan="14" class="text-center text-danger py-4">
                ${error.message}
            </td>
        </tr>
    `;

    mostrarNotificacion(
        "Error de Lectura",
        error.message,
        true
    );

});

        // Filtrar registros de la base de datos por motor
        const filtroMotor = document.getElementById('filtro_motor_id');
        if (filtroMotor) {
            filtroMotor.addEventListener('change', function() {
                let motorId = this.value;
                let tbody = document.getElementById('contenido-detalles');
                let btnGuardarMasivo = document.getElementById('btn-guardar-masivo');
                let contenedorPaginacion = document.getElementById('paginacion-container');

                if (!motorId) {
                    todosLosRegistrosHtml = [];
                    tbody.innerHTML = `<tr><td colspan="14" class="text-center text-muted py-5"><i class="fas fa-search fa-2x text-light mb-2"></i><p class="mb-0">Seleccione un motor para ver registros.</p></td></tr>`;
                    if (contenedorPaginacion) contenedorPaginacion.style.display = 'none';
                    if (btnGuardarMasivo) btnGuardarMasivo.style.display = 'inline-block';
                    return;
                }

                tbody.innerHTML = `<tr><td colspan="14" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Cargando...</td></tr>`;
                if (contenedorPaginacion) contenedorPaginacion.style.display = 'none';

                fetch(`/motores/${motorId}/detalles-json`)
                    .then(response => response.json())
                    .then(data => {
                        todosLosRegistrosHtml = [];
                        paginaActual = 1;

                        if (data.length === 0) {
                            tbody.innerHTML = `<tr><td colspan="14" class="text-center text-muted py-4">No hay registros asociados a este motor.</td></tr>`;
                            if (btnGuardarMasivo) btnGuardarMasivo.style.display = 'none';
                            return;
                        }

                        if (btnGuardarMasivo) btnGuardarMasivo.style.display = 'inline-block';

                        data.forEach(item => {
                            let tr = document.createElement('tr');
                            
                            // Formatear la fecha si existe
                            let fechaFormateada = '-';
                            if (item.updated_at) {
                                let fecha = new Date(item.updated_at);
                                let dia = String(fecha.getDate()).padStart(2, '0');
                                let mes = String(fecha.getMonth() + 1).padStart(2, '0');
                                let anio = fecha.getFullYear();
                                let horas = String(fecha.getHours()).padStart(2, '0');
                                let minutos = String(fecha.getMinutes()).padStart(2, '0');
                                fechaFormateada = `${dia}/${mes}/${anio} ${horas}:${minutos}`;
                            }

                            tr.innerHTML = `
                                <td>
                                    ${item.dni}
                                    <input type="hidden" name="detalles[${item.id}][dni]" value="${item.dni}">
                                </td>
                                <td>${item.numero_colegiado ?? 'N/A'}</td>
                                <td>${item.nombre}</td>
                                <td><input type="number" name="detalles[${item.id}][cuota_colegial]" value="${item.cuota_colegial ?? 0}" class="form-control form-control-sm text-center border-0 bg-light p-1" min="0" step="0.01" style="width: 85px;"></td>
                                <td><input type="number" name="detalles[${item.id}][automaticos]" value="${item.automaticos ?? 0}" class="form-control form-control-sm text-center border-0 bg-light p-1" min="0" step="0.01" style="width: 85px;"></td>
                                <td><input type="number" name="detalles[${item.id}][estudio]" value="${item.estudio ?? 0}" class="form-control form-control-sm text-center border-0 bg-light p-1" min="0" step="0.01" style="width: 85px;"></td>
                                <td><input type="number" name="detalles[${item.id}][refinanciamiento]" value="${item.refinanciamiento ?? 0}" class="form-control form-control-sm text-center border-0 bg-light p-1" min="0" step="0.01" style="width: 85px;"></td>
                                <td><input type="number" name="detalles[${item.id}][readecuacion]" value="${item.readecuacion ?? 0}" class="form-control form-control-sm text-center border-0 bg-light p-1" min="0" step="0.01" style="width: 85px;"></td>
                                <td><input type="number" name="detalles[${item.id}][personal]" value="${item.personal ?? 0}" class="form-control form-control-sm text-center border-0 bg-light p-1" min="0" step="0.01" style="width: 85px;"></td>
                                <td><input type="number" name="detalles[${item.id}][compra_deuda]" value="${item.compra_deuda ?? 0}" class="form-control form-control-sm text-center border-0 bg-light p-1" min="0" step="0.01" style="width: 85px;"></td>
                                <td><input type="number" name="detalles[${item.id}][hipotecario]" value="${item.hipotecario ?? 0}" class="form-control form-control-sm text-center border-0 bg-light p-1" min="0" step="0.01" style="width: 85px;"></td>
                                <td><input type="number" name="detalles[${item.id}][vehiculo]" value="${item.vehiculo ?? 0}" class="form-control form-control-sm text-center border-0 bg-light p-1" min="0" step="0.01" style="width: 85px;"></td>
                                <td><input type="number" name="detalles[${item.id}][empleado]" value="${item.empleado ?? 0}" class="form-control form-control-sm text-center border-0 bg-light p-1" min="0" step="0.01" style="width: 85px;"></td>
                                <td class="text-center text-nowrap">
                                    <div class="small text-muted" style="font-size: 0.75rem;">${fechaFormateada}</div>
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;"><i class="fas fa-user me-1"></i>${item.updated_by ?? 'N/D'}</span>
                                </td>
                            `;
                            todosLosRegistrosHtml.push(tr);
                        });

                        renderizarTablaPaginada();
                    })
                    .catch(error => {
                        console.error("Error al cargar detalles:", error);
                        tbody.innerHTML = `<tr><td colspan="14" class="text-center text-danger py-4">Error al cargar los registros.</td></tr>`;
                    });
            });
        }
    });
</script>


@push('scripts')
<style>
    /* Ocultar flechas en inputs de tipo número (Chrome, Safari, Edge, Opera) */
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Ocultar flechas en inputs de tipo número (Firefox) */
    input[type=number] {
        -moz-appearance: textfield;
    }
</style>
@endpush
@endpush
