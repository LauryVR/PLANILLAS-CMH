@extends('layouts.template')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1 text-dark">Gestión de Motores de Retención</h2>
            <p class="text-muted small mb-0">Registre y vincule los motores de retención a sus entes retenedores correspondientes.</p>
        </div>
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

    <div class="row mb-5">
        {{-- Formulario para Registrar un nuevo Motor --}}
        <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-3 text-primary me-3">
                            <i class="fas fa-plus-circle fa-lg"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Registrar Nuevo Motor</h5>
                    </div>
                    
                    <form action="{{ route('motores.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="ente_retencion_id" class="form-label fw-semibold small text-secondary">Ente Retenedor</label>
                            <select name="ente_retencion_id" id="ente_retencion_id" class="form-select" required>
                                <option value="">-- Seleccione un Ente --</option>
                                @foreach($entes as $ente)
                                    <option value="{{ $ente->id }}" {{ old('ente_retencion_id') == $ente->id ? 'selected' : '' }}>
                                        {{ $ente->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small text-muted">Seleccione el ente proveniente de la base de datos externa.</div>
                        </div>

                        <div class="mb-4">
                            <label for="nombre_motor" class="form-label fw-semibold small text-secondary">Nombre del Motor</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-cogs text-muted"></i></span>
                                <input type="text" name="nombre_motor" id="nombre_motor" class="form-control border-start-0 ps-0" placeholder="Ej: Motor de Retención IHSS" value="{{ old('nombre_motor') }}" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 fw-bold py-2 shadow-sm rounded-pill">
                            <i class="fas fa-save me-2"></i> Guardar Motor
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Panel de Estadísticas Rápidas --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-light h-100 d-flex flex-column justify-content-center p-4">
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <span class="text-muted small text-uppercase fw-bold">Total Motores</span>
                        <h2 class="fw-bold text-primary mb-0 mt-1">{{ count($motores) }}</h2>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small text-uppercase fw-bold">Entes Disponibles</span>
                        <h2 class="fw-bold text-success mb-0 mt-1">{{ count($entes) }}</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Listado de Motores Registrados --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-list text-primary me-2"></i> Motores Configurados</h5>
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">
                {{ count($motores) }} Registrados
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase fs-7 text-secondary">
                        <tr>
                            <th class="py-3 px-4"># ID</th>
                            <th class="py-3">Nombre del Motor</th>
                            <th class="py-3">Ente Asociado</th>
                            <th class="py-3">Fecha de Creación</th>
                            <th class="py-3 text-center">Estado</th>
                            <th class="py-3 text-end px-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($motores as $motor)
                            <tr>
                                <td class="px-4 fw-semibold text-muted">#{{ $motor->id }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $motor->nombre_motor }}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle p-1 text-center me-2 text-secondary" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-building small"></i>
                                        </div>
                                        <span>{{ $motor->enteRetencion->nombre ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="text-muted small">
                                    {{ $motor->created_at ? $motor->created_at->format('d/m/Y H:i') : 'N/A' }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $motor->activo ? 'success' : 'secondary' }} bg-opacity-10 text-{{ $motor->activo ? 'success' : 'secondary' }} px-3 py-2 rounded-pill fw-bold">
                                        {{ $motor->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-end px-4">
                                    <button type="button" class="btn btn-sm btn-light text-secondary rounded-circle p-2" title="Opciones">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <div class="py-3">
                                        <i class="fas fa-folder-open fa-3x text-light mb-3"></i>
                                        <p class="mb-1 fw-semibold">No hay motores registrados todavía.</p>
                                        <p class="text-muted small mb-0">Utiliza el formulario superior para registrar uno nuevo.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .fs-7 { font-size: 0.75rem; }
    .card { border-radius: 1rem; }
    .table > :not(caption) > * > * { padding: 1rem 0.75rem; }
</style>
@endpush