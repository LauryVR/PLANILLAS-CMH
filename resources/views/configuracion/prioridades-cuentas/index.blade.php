@extends('layouts.template')

@push('head')
    <title>Prioridades de Deducción - Portal Colegio Médico</title>
    <style>
        .drag-handle {
            cursor: grab;
        }
        .drag-handle:active {
            cursor: grabbing;
        }
        .sortable-ghost {
            opacity: 0.4;
            background-color: #e9ecef !important;
        }
    </style>
@endpush

@section('content')
<div class="container py-4" style="max-width: 1100px;">

    {{-- Encabezado --}}
    <div class="row mb-4 align-items-center g-3">
        <div class="col-12 col-md-7">
            <h2 class="fw-bold text-dark mb-1 fs-3">
                <i class="fas fa-sort-amount-down-alt me-2 text-primary"></i>Jerarquía Global de Deducciones
            </h2>
            <p class="text-muted mb-0 small">
                Establezca el orden de prioridad único que aplica consecutivamente a todas las cuentas registradas.
            </p>
        </div>
        <div class="col-12 col-md-5 text-start text-md-end">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                <a href="{{ route('configuracion.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-medium">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <button type="button" class="btn btn-primary btn-sm rounded-pill shadow-sm fw-bold px-3" data-bs-toggle="modal" data-bs-target="#modalCrearPrioridad">
                    <i class="fas fa-plus-circle me-1"></i> Añadir Cuenta a la Prioridad
                </button>
            </div>
        </div>
    </div>

    {{-- Formulario Principal de Reordenamiento --}}
    <form action="{{ route('configuracion.prioridades-cuentas.reordenar') }}" method="POST" id="formPrioridades">
        @csrf
        @method('PUT')

        <div class="card shadow-sm border-0 rounded-3 overflow-hidden mb-4">
            
            {{-- Header de la Tabla --}}
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold text-secondary small">
                    <i class="fas fa-info-circle me-1 text-primary"></i> 
                    Arrastre los elementos o use las flechas para ajustar la prioridad consecutiva.
                </span>
                <button type="submit" class="btn btn-success btn-sm rounded-pill fw-bold px-3">
                    <i class="fas fa-save me-1"></i> Guardar Nuevo Orden
                </button>
            </div>

            {{-- Tabla de Lista de Prioridades --}}
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaPrioridades" style="min-width: 600px;">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px;"></th>
                                <th class="text-center" style="width: 90px;">Orden</th>
                                <th>Tipo de Cuenta</th>
                                <th class="text-center" style="width: 130px;">Estado</th>
                                <th class="text-center pe-3" style="width: 180px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="sortableList">
                            @forelse($prioridades as $index => $item)
                                <tr class="item-prioridad" data-id="{{ $item->id }}">
                                    {{-- Agarradera para Drag & Drop --}}
                                    <td class="text-center text-muted drag-handle fs-6">
                                        <i class="fas fa-grip-vertical"></i>
                                    </td>
                                    
                                    {{-- Badge del Número de Prioridad --}}
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-circle p-2 fs-6 shadow-sm d-inline-flex align-items-center justify-content-center numero-prioridad" style="width: 34px; height: 34px;">
                                            {{ $loop->iteration }}
                                        </span>
                                        {{-- Campo oculto enviando id => nueva_posicion --}}
                                        <input type="hidden" name="prioridades[{{ $item->id }}]" class="input-prioridad-val" value="{{ $loop->iteration }}">
                                    </td>

                                    {{-- Nombre del Tipo de Cuenta --}}
                                    <td class="fw-bold text-dark fs-7">
                                        {{ $item->tipoCuenta->nombre ?? 'Sin Asignar' }}
                                        @if(isset($item->tipoCuenta->tipo_cuenta_id))
                                            <span class="text-muted ms-1 fs-8">({{ $item->tipoCuenta->tipo_cuenta_id }})</span>
                                        @endif
                                    </td>

                                    {{-- Estado --}}
                                    <td class="text-center">
                                        @if($item->activo)
                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                <i class="fas fa-check-circle me-1"></i>Activo
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1 opacity-75">
                                                <i class="fas fa-ban me-1"></i>Inactivo
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Botones para Subir/Bajar y Toggle --}}
                                    <td class="text-center pe-3">
                                        <div class="btn-group gap-1" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-mover-arriba" title="Subir prioridad">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-mover-abajo" title="Bajar prioridad">
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm {{ $item->activo ? 'btn-outline-danger' : 'btn-outline-success' }} ms-1 rounded-2 btn-toggle-estado"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalToggleEstado"
                                                    data-id="{{ $item->id }}"
                                                    data-nombre="{{ $item->tipoCuenta->nombre ?? 'Cuenta' }}"
                                                    data-activo="{{ $item->activo ? '1' : '0' }}"
                                                    data-route="{{ route('configuracion.prioridades-cuentas.toggle', $item->id) }}"
                                                    title="{{ $item->activo ? 'Inactivar' : 'Activar' }}">
                                                <i class="fas {{ $item->activo ? 'fa-eye-slash' : 'fa-check' }}"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-list-ol fa-2x mb-2 d-block opacity-50"></i>
                                        No hay cuentas asignadas en la jerarquía de prioridad.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-light py-3 text-end">
                <button type="submit" class="btn btn-success btn-sm fw-bold px-4 rounded-pill">
                    <i class="fas fa-save me-1"></i> Guardar Cambios de Prioridad
                </button>
            </div>
        </div>
    </form>
</div>

{{-- MODAL AÑADIR CUENTA --}}
<div class="modal fade" id="modalCrearPrioridad" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="fas fa-plus-circle me-2"></i>Añadir Tipo de Cuenta a la Jerarquía
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('configuracion.prioridades-cuentas.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="tipo_cuenta_id" class="form-label fw-bold">Seleccionar Tipo de Cuenta</label>
                        <select name="tipo_cuenta_id" id="tipo_cuenta_id" class="form-select" required>
                            <option value="">-- Seleccione Tipo de Cuenta --</option>
                            @foreach($tiposCuenta as $cuenta)
                                <option value="{{ $cuenta->id }}">
                                    {{ $cuenta->nombre }} ({{ $cuenta->tipo_cuenta_id }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">Se agregará automáticamente al final del orden de cobro/deducción.</small>
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input type="hidden" name="activo" value="0">
                        <input class="form-check-input" type="checkbox" id="nuevo_activo" name="activo" value="1" checked>
                        <label class="form-check-label fw-bold" for="nuevo_activo">Activo para Deducciones</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="fas fa-plus me-1"></i> Agregar a la Lista
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL CONFIRMACIÓN ESTADO --}}
<div class="modal fade" id="modalToggleEstado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;" id="iconContainerToggle">
                    <i class="fas fa-exclamation-triangle fa-2x" id="iconToggleModal"></i>
                </div>
                <h5 class="fw-bold mb-2 fs-6" id="tituloToggleModal">¿Cambiar Estado?</h5>
                <p class="text-muted small mb-0" id="mensajeToggleModal">
                    ¿Desea cambiar la disponibilidad de esta cuenta en las deducciones?
                </p>
                <div class="mt-3 p-2 bg-light rounded font-monospace small fw-bold text-dark" id="nombreRegistroToggle"></div>
            </div>
            <div class="modal-footer border-0 bg-light justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancelar</button>
                <form id="formToggleEstado" method="POST" action="">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm fw-bold px-3" id="btnConfirmarToggle">Confirmar</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- MODAL NOTIFICACIÓN ÉXITO --}}
@if(session('success'))
<div class="modal fade" id="modalNotificacionExito" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-check fa-2x"></i>
                </div>
                <h5 class="fw-bold text-success mb-2 fs-6">¡Proceso Exitoso!</h5>
                <p class="text-muted small mb-0">{{ session('success') }}</p>
            </div>
            <div class="modal-footer border-0 bg-light justify-content-center py-2">
                <button type="button" class="btn btn-success btn-sm fw-bold w-100" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
{{-- Librería SortableJS --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tbody = document.getElementById('sortableList');

        // Función central que recalcula los números (1, 2, 3...) tras cualquier movimiento
        function reindexarFilas() {
            const filas = tbody.querySelectorAll('.item-prioridad');
            filas.forEach((fila, index) => {
                const nuevoOrden = index + 1;
                
                // Actualizar el número visual en el Badge
                const badge = fila.querySelector('.numero-prioridad');
                if (badge) badge.textContent = nuevoOrden;

                // Actualizar el valor del input hidden enviado al backend
                const input = fila.querySelector('.input-prioridad-val');
                if (input) input.value = nuevoOrden;
            });
        }

        // 1. Integración Drag & Drop
        if (typeof Sortable !== 'undefined' && tbody) {
            new Sortable(tbody, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function () {
                    reindexarFilas();
                }
            });
        }

        // 2. Acciones manuales de flechas (Subir / Bajar)
        if (tbody) {
            tbody.addEventListener('click', function (e) {
                const btnArriba = e.target.closest('.btn-mover-arriba');
                const btnAbajo = e.target.closest('.btn-mover-abajo');

                if (btnArriba) {
                    const fila = btnArriba.closest('tr');
                    const anterior = fila.previousElementSibling;
                    if (anterior) {
                        tbody.insertBefore(fila, anterior);
                        reindexarFilas();
                    }
                }

                if (btnAbajo) {
                    const fila = btnAbajo.closest('tr');
                    const siguiente = fila.nextElementSibling;
                    if (siguiente) {
                        tbody.insertBefore(siguiente, fila);
                        reindexarFilas();
                    }
                }
            });
        }

        // 3. Modal Notificación Exito
        @if(session('success'))
            var elExito = document.getElementById('modalNotificacionExito');
            if (elExito) {
                new bootstrap.Modal(elExito).show();
            }
        @endif

        // 4. Modal Estado (Activar/Inactivar)
        var toggleButtons = document.querySelectorAll('.btn-toggle-estado');
        toggleButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var route = this.getAttribute('data-route');
                var nombre = this.getAttribute('data-nombre');
                var activo = this.getAttribute('data-activo') === '1';

                document.getElementById('formToggleEstado').setAttribute('action', route);
                document.getElementById('nombreRegistroToggle').textContent = nombre;

                var iconContainer = document.getElementById('iconContainerToggle');
                var icon = document.getElementById('iconToggleModal');
                var titulo = document.getElementById('tituloToggleModal');
                var mensaje = document.getElementById('mensajeToggleModal');
                var btnConfirmar = document.getElementById('btnConfirmarToggle');

                if (activo) {
                    iconContainer.className = 'rounded-circle d-inline-flex align-items-center justify-content-center mb-3 bg-danger-subtle text-danger';
                    icon.className = 'fas fa-ban fa-2x';
                    titulo.textContent = '¿Desactivar de Prioridad?';
                    mensaje.textContent = 'Esta cuenta se omitirá temporalmente del proceso de deducción consecutiva.';
                    btnConfirmar.className = 'btn btn-danger btn-sm fw-bold px-3';
                    btnConfirmar.textContent = 'Sí, Inactivar';
                } else {
                    iconContainer.className = 'rounded-circle d-inline-flex align-items-center justify-content-center mb-3 bg-success-subtle text-success';
                    icon.className = 'fas fa-check-circle fa-2x';
                    titulo.textContent = '¿Activar en Prioridad?';
                    mensaje.textContent = 'Esta cuenta volverá a tomarse en cuenta en el orden establecido.';
                    btnConfirmar.className = 'btn btn-success btn-sm fw-bold px-3';
                    btnConfirmar.textContent = 'Sí, Activar';
                }
            });
        });
    });
</script>
@endpush