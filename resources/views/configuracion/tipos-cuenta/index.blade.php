@extends('layouts.template')

@push('head')
    <title>Tipos de Cuenta - Portal Colegio Médico</title>
@endpush

@section('content')
{{-- Usamos container centrado con ancho máximo para que no se extienda al 100% --}}
<div class="container py-4" style="max-width: 1100px;">

    {{-- Encabezado --}}
    <div class="row mb-4 align-items-center g-3">
        <div class="col-12 col-md-7">
            <h2 class="fw-bold text-primary mb-1 fs-3">
                <i class="fas fa-university me-2"></i>Gestión de Tipos de Cuenta
            </h2>
            <p class="text-muted mb-0 small">Catálogo de tipos de cuenta registrados en el sistema.</p>
        </div>
        <div class="col-12 col-md-5 text-start text-md-end">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                <a href="{{ route('configuracion.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-medium">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <button type="button" class="btn btn-primary btn-sm rounded-pill shadow-sm fw-bold px-3" data-bs-toggle="modal" data-bs-target="#modalCrearTipoCuenta">
                    <i class="fas fa-plus-circle me-1"></i> Nuevo Tipo de Cuenta
                </button>
            </div>
        </div>
    </div>

    {{-- Card Principal Centrado y Compacto --}}
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        
        {{-- Header con Filtros y Buscador --}}
        <div class="card-header bg-white py-3 border-bottom">
            <form method="GET" action="{{ route('configuracion.tipos-cuenta.index') }}" class="row g-2 align-items-center">
                
                {{-- Buscador --}}
                <div class="col-12 col-sm-6 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" name="buscar" class="form-control bg-light border-start-0" placeholder="Buscar por ID, Código, Cuenta SAP o Nombre..." value="{{ $buscar }}">
                    </div>
                </div>

                {{-- Filtro por Estado --}}
                <div class="col-12 col-sm-6 col-md-4">
                    <select name="estado" class="form-select form-select-sm bg-light" onchange="this.form.submit()">
                        <option value="">-- Todos los Estados --</option>
                        <option value="1" {{ $estado === '1' ? 'selected' : '' }}>Solo Activos</option>
                        <option value="0" {{ $estado === '0' ? 'selected' : '' }}>Solo Inactivos</option>
                    </select>
                </div>

                {{-- Botones de Filtrado --}}
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    @if($buscar || $estado !== null)
                        <a href="{{ route('configuracion.tipos-cuenta.index') }}" class="btn btn-sm btn-outline-danger" title="Limpiar Filtros">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tabla de Datos Responsive --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width: 750px;">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3 text-center" style="width: 70px;">ID</th>
                            <th class="text-center" style="width: 120px;">Código ID</th>
                            <th>Nombre</th>
                            <th class="text-center" style="width: 130px;">Cuenta SAP</th>
                            <th class="text-center" style="width: 110px;">Estado</th>
                            <th class="text-center pe-3" style="width: 130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tiposCuenta as $cuenta)
                            <tr>
                                <td class="ps-3 text-center fw-bold text-secondary fs-7">#{{ $cuenta->id }}</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                        {{ $cuenta->tipo_cuenta_id }}
                                    </span>
                                </td>
                                <td class="fw-bold text-dark fs-7">{{ $cuenta->nombre }}</td>
                                <td class="text-center">
                                    @if($cuenta->cuenta_sap)
                                        <span class="badge bg-info-subtle text-info border border-info font-monospace px-2 py-1">
                                            {{ $cuenta->cuenta_sap }}
                                        </span>
                                    @else
                                        <span class="text-muted small fst-italic">N/D</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($cuenta->activo)
                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                            <i class="fas fa-check-circle me-1"></i>Activo
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                                            <i class="fas fa-ban me-1"></i>Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group gap-1" role="group">
                                        {{-- Botón Editar --}}
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditarTipoCuenta{{ $cuenta->id }}"
                                                title="Editar">
                                            <i class="fas fa-edit"></i> <span class="d-none d-lg-inline">Editar</span>
                                        </button>

                                        {{-- Botón Cambiar Estado --}}
                                        <button type="button" class="btn btn-sm {{ $cuenta->activo ? 'btn-outline-danger' : 'btn-outline-success' }} rounded-2 px-2 py-1 btn-toggle-estado"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalToggleEstado"
                                                data-id="{{ $cuenta->id }}"
                                                data-nombre="{{ $cuenta->nombre }}"
                                                data-activo="{{ $cuenta->activo }}"
                                                data-route="{{ route('configuracion.tipos-cuenta.toggle', $cuenta->id) }}"
                                                title="{{ $cuenta->activo ? 'Inactivar' : 'Activar' }}">
                                            <i class="fas {{ $cuenta->activo ? 'fa-eye-slash' : 'fa-check' }}"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-search fa-2x mb-2 d-block opacity-50"></i>
                                    No se encontraron tipos de cuenta.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Footer con Paginación --}}
        @if($tiposCuenta->hasPages())
            <div class="card-footer bg-white py-3 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <small class="text-muted text-center text-sm-start">
                    Mostrando <strong>{{ $tiposCuenta->firstItem() }}</strong> a <strong>{{ $tiposCuenta->lastItem() }}</strong> de <strong>{{ $tiposCuenta->total() }}</strong> registros
                </small>
                <div>
                    {{ $tiposCuenta->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

{{-- MODAL CREAR NUEVO REGISTRO --}}
<div class="modal fade" id="modalCrearTipoCuenta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="fas fa-plus-circle me-2"></i>Nuevo Tipo de Cuenta
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('configuracion.tipos-cuenta.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="nuevo_tipo_cuenta_id" class="form-label fw-bold">Tipo Cuenta ID (Código Único)</label>
                        <input type="text" class="form-control" id="nuevo_tipo_cuenta_id" name="tipo_cuenta_id" value="{{ old('tipo_cuenta_id') }}" placeholder="Ej: 10, CTA-AHORROS" required>
                    </div>

                    <div class="mb-3">
                        <label for="nuevo_nombre" class="form-label fw-bold">Nombre</label>
                        <input type="text" class="form-control" id="nuevo_nombre" name="nombre" value="{{ old('nombre') }}" placeholder="Ej: VEHICULO DIRECTO" required>
                    </div>

                    <div class="mb-3">
                        <label for="nuevo_cuenta_sap" class="form-label fw-bold">Cuenta SAP <span class="text-muted fw-normal small">(Opcional)</span></label>
                        <input type="text" class="form-control font-monospace" id="nuevo_cuenta_sap" name="cuenta_sap" value="{{ old('cuenta_sap') }}" placeholder="Ej: 11020101">
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" id="nuevo_activo" name="activo" value="1" checked>
                        <label class="form-check-label fw-bold" for="nuevo_activo">Registro Activo</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="fas fa-save me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODALES DE EDITAR --}}
@foreach($tiposCuenta as $cuenta)
    <div class="modal fade" id="modalEditarTipoCuenta{{ $cuenta->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold fs-6">
                        <i class="fas fa-edit me-2"></i>Editar Tipo de Cuenta #{{ $cuenta->id }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('configuracion.tipos-cuenta.update', $cuenta->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4 text-start">
                        <div class="mb-3">
                            <label for="tipo_cuenta_id_{{ $cuenta->id }}" class="form-label fw-bold">Tipo Cuenta ID (Código)</label>
                            <input type="text" class="form-control" id="tipo_cuenta_id_{{ $cuenta->id }}" name="tipo_cuenta_id" value="{{ old('tipo_cuenta_id', $cuenta->tipo_cuenta_id) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="nombre_{{ $cuenta->id }}" class="form-label fw-bold">Nombre</label>
                            <input type="text" class="form-control" id="nombre_{{ $cuenta->id }}" name="nombre" value="{{ old('nombre', $cuenta->nombre) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="cuenta_sap_{{ $cuenta->id }}" class="form-label fw-bold">Cuenta SAP <span class="text-muted fw-normal small">(Opcional)</span></label>
                            <input type="text" class="form-control font-monospace" id="cuenta_sap_{{ $cuenta->id }}" name="cuenta_sap" value="{{ old('cuenta_sap', $cuenta->cuenta_sap) }}" placeholder="Ej: 11020101">
                        </div>

                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" id="activo_{{ $cuenta->id }}" name="activo" value="1" {{ $cuenta->activo ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="activo_{{ $cuenta->id }}">Registro Activo</label>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">
                            <i class="fas fa-save me-1"></i> Actualizar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

{{-- MODAL DE CONFIRMACIÓN ACTIVAR / INACTIVAR --}}
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
                    ¿Está seguro de cambiar el estado de este registro?
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
                <h5 class="fw-bold text-success mb-2 fs-6">¡Operación Exitosa!</h5>
                <p class="text-muted small mb-0">{{ session('success') }}</p>
            </div>
            <div class="modal-footer border-0 bg-light justify-content-center py-2">
                <button type="button" class="btn btn-success btn-sm fw-bold w-100" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- MODAL NOTIFICACIÓN ERROR --}}
@if($errors->any())
<div class="modal fade" id="modalNotificacionError" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="fas fa-exclamation-triangle me-2"></i>Atención: Corrija los Errores
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-2">No se pudo completar la operación debido a los siguientes inconvenientes:</p>
                <ul class="text-danger mb-0 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-danger btn-sm fw-bold" data-bs-dismiss="modal">Aceptar</button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // 1. Desplegar Modal de Éxito automáticamente
        @if(session('success'))
            var modalExito = new bootstrap.Modal(document.getElementById('modalNotificacionExito'));
            modalExito.show();
        @endif

        // 2. Desplegar Modal de Error automáticamente
        @if($errors->any())
            var modalError = new bootstrap.Modal(document.getElementById('modalNotificacionError'));
            modalError.show();
        @endif

        // 3. Script dinámico para el Modal de Inactivar / Activar
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
                    titulo.textContent = '¿Inactivar Registro?';
                    mensaje.textContent = 'El tipo de cuenta no estará disponible en las selecciones principales.';
                    btnConfirmar.className = 'btn btn-danger btn-sm fw-bold px-3';
                    btnConfirmar.textContent = 'Sí, Inactivar';
                } else {
                    iconContainer.className = 'rounded-circle d-inline-flex align-items-center justify-content-center mb-3 bg-success-subtle text-success';
                    icon.className = 'fas fa-check-circle fa-2x';
                    titulo.textContent = '¿Activar Registro?';
                    mensaje.textContent = 'El tipo de cuenta volverá a estar disponible en el portal.';
                    btnConfirmar.className = 'btn btn-success btn-sm fw-bold px-3';
                    btnConfirmar.textContent = 'Sí, Activar';
                }
            });
        });
    });
</script>
@endpush