@extends('layouts.template')

@push('head')
    <title>Entes de Retención - Portal Colegio Médico</title>
@endpush

@section('content')
<div class="container py-4" style="max-width: 1100px;">

    {{-- Encabezado --}}
    <div class="row mb-4 align-items-center g-3">
        <div class="col-12 col-md-7">
            <h2 class="fw-bold text-success mb-1 fs-3">
                <i class="fas fa-building me-2"></i>Gestión de Entes de Retención
            </h2>
            <p class="text-muted mb-0 small">Catálogo de entidades e instituciones autorizadas para retenciones.</p>
        </div>
        <div class="col-12 col-md-5 text-start text-md-end">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                <a href="{{ route('configuracion.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-medium">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <button type="button" class="btn btn-success btn-sm rounded-pill shadow-sm fw-bold px-3" data-bs-toggle="modal" data-bs-target="#modalCrearEnte">
                    <i class="fas fa-plus-circle me-1"></i> Nuevo Ente
                </button>
            </div>
        </div>
    </div>

    {{-- Card Principal --}}
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        
        {{-- Header con Filtros --}}
        <div class="card-header bg-white py-3 border-bottom">
            <form method="GET" action="{{ route('configuracion.entes-retencion.index') }}" class="row g-2 align-items-center">
                
                <div class="col-12 col-sm-6 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" name="buscar" class="form-control bg-light border-start-0" placeholder="Buscar por ID, Código o Nombre..." value="{{ $buscar }}">
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-4">
                    <select name="estado" class="form-select form-select-sm bg-light" onchange="this.form.submit()">
                        <option value="">-- Todos los Estados --</option>
                        <option value="1" {{ $estado === '1' ? 'selected' : '' }}>Solo Activos</option>
                        <option value="0" {{ $estado === '0' ? 'selected' : '' }}>Solo Inactivos</option>
                    </select>
                </div>

                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    @if($buscar || $estado !== null)
                        <a href="{{ route('configuracion.entes-retencion.index') }}" class="btn btn-sm btn-outline-danger" title="Limpiar Filtros">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tabla de Datos --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width: 650px;">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3 text-center" style="width: 70px;">ID</th>
                            <th class="text-center" style="width: 150px;">Código Ente</th>
                            <th>Nombre del Ente</th>
                            <th class="text-center" style="width: 120px;">Estado</th>
                            <th class="text-center pe-3" style="width: 140px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entes as $ente)
                            <tr>
                                <td class="ps-3 text-center fw-bold text-secondary fs-7">#{{ $ente->id }}</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                        {{ $ente->ente_retencion_id }}
                                    </span>
                                </td>
                                <td class="fw-bold text-dark fs-7">{{ $ente->nombre }}</td>
                                <td class="text-center">
                                    @if($ente->activo)
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
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditarEnte{{ $ente->id }}"
                                                title="Editar">
                                            <i class="fas fa-edit"></i> <span class="d-none d-lg-inline">Editar</span>
                                        </button>

                                        <button type="button" class="btn btn-sm {{ $ente->activo ? 'btn-outline-danger' : 'btn-outline-success' }} rounded-2 px-2 py-1 btn-toggle-estado"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalToggleEstado"
                                                data-id="{{ $ente->id }}"
                                                data-nombre="{{ $ente->nombre }}"
                                                data-activo="{{ $ente->activo }}"
                                                data-route="{{ route('configuracion.entes-retencion.toggle', $ente->id) }}"
                                                title="{{ $ente->activo ? 'Inactivar' : 'Activar' }}">
                                            <i class="fas {{ $ente->activo ? 'fa-eye-slash' : 'fa-check' }}"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-search fa-2x mb-2 d-block opacity-50"></i>
                                    No se encontraron entes de retención.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Footer Paginación --}}
        @if($entes->hasPages())
            <div class="card-footer bg-white py-3 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <small class="text-muted text-center text-sm-start">
                    Mostrando <strong>{{ $entes->firstItem() }}</strong> a <strong>{{ $entes->lastItem() }}</strong> de <strong>{{ $entes->total() }}</strong> registros
                </small>
                <div>
                    {{ $entes->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

{{-- MODAL CREAR --}}
<div class="modal fade" id="modalCrearEnte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="fas fa-plus-circle me-2"></i>Nuevo Ente de Retención
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('configuracion.entes-retencion.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="nuevo_ente_retencion_id" class="form-label fw-bold">Código del Ente (Único)</label>
                        <input type="text" class="form-control" id="nuevo_ente_retencion_id" name="ente_retencion_id" value="{{ old('ente_retencion_id') }}" placeholder="Ej: BANHCAFE, IHSS" required>
                    </div>

                    <div class="mb-3">
                        <label for="nuevo_nombre" class="form-label fw-bold">Nombre</label>
                        <input type="text" class="form-control" id="nuevo_nombre" name="nombre" value="{{ old('nombre') }}" placeholder="Ej: Banco Hondureño del Café" required>
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" id="nuevo_activo" name="activo" value="1" checked>
                        <label class="form-check-label fw-bold" for="nuevo_activo">Registro Activo</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold">
                        <i class="fas fa-save me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODALES EDITAR --}}
@foreach($entes as $ente)
    <div class="modal fade" id="modalEditarEnte{{ $ente->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold fs-6">
                        <i class="fas fa-edit me-2"></i>Editar Ente de Retención #{{ $ente->id }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('configuracion.entes-retencion.update', $ente->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4 text-start">
                        <div class="mb-3">
                            <label for="ente_retencion_id_{{ $ente->id }}" class="form-label fw-bold">Código del Ente</label>
                            <input type="text" class="form-control" id="ente_retencion_id_{{ $ente->id }}" name="ente_retencion_id" value="{{ old('ente_retencion_id', $ente->ente_retencion_id) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="nombre_{{ $ente->id }}" class="form-label fw-bold">Nombre</label>
                            <input type="text" class="form-control" id="nombre_{{ $ente->id }}" name="nombre" value="{{ old('nombre', $ente->nombre) }}" required>
                        </div>

                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" id="activo_{{ $ente->id }}" name="activo" value="1" {{ $ente->activo ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="activo_{{ $ente->id }}">Registro Activo</label>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success btn-sm fw-bold">
                            <i class="fas fa-save me-1"></i> Actualizar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

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
                <p class="text-muted small mb-2">No se pudo completar la operación debido a:</p>
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
        @if(session('success'))
            var modalExito = new bootstrap.Modal(document.getElementById('modalNotificacionExito'));
            modalExito.show();
        @endif

        @if($errors->any())
            var modalError = new bootstrap.Modal(document.getElementById('modalNotificacionError'));
            modalError.show();
        @endif

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
                    mensaje.textContent = 'El ente de retención no estará disponible en el sistema.';
                    btnConfirmar.className = 'btn btn-danger btn-sm fw-bold px-3';
                    btnConfirmar.textContent = 'Sí, Inactivar';
                } else {
                    iconContainer.className = 'rounded-circle d-inline-flex align-items-center justify-content-center mb-3 bg-success-subtle text-success';
                    icon.className = 'fas fa-check-circle fa-2x';
                    titulo.textContent = '¿Activar Registro?';
                    mensaje.textContent = 'El ente de retención volverá a estar disponible.';
                    btnConfirmar.className = 'btn btn-success btn-sm fw-bold px-3';
                    btnConfirmar.textContent = 'Sí, Activar';
                }
            });
        });
    });
</script>
@endpush