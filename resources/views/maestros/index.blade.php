@extends('layouts.template')

@section('content')

<div class="container py-5">

    {{-- Alertas de éxito / error --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow border-0">

        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">

                <h3 class="mb-0 fs-4">
                    <i class="fas fa-users me-2"></i>
                    Datos Maestros
                </h3>

                {{-- Botón para abrir el modal de Excel --}}
                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#cargarExcelModal">
                   <a href="{{ route('excel.index') }}" class="btn btn-light">
    <i class="fas fa-file-excel text-success me-1"></i>
    Cargar Excel
</a>
                </button>

            </div>
        </div>

        <div class="card-body">

            <div class="row mb-3 align-items-center">

                <div class="col-md-6 mb-2 mb-md-0">
                    <h6 class="mb-0">
                        Total de registros:
                        <span class="badge bg-primary">
                            {{ $maestros->total() }}
                        </span>
                    </h6>
                </div>

                {{-- Buscador dinámico --}}
                <div class="col-md-6">
                    <form action="{{ route('maestros.index') }}" method="GET">
                        <div class="input-group">
                            <input
                                type="text"
                                name="buscar"
                                class="form-control"
                                placeholder="Buscar por nombre o DNI..."
                                value="{{ request('buscar') }}">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request('buscar'))
                                <a href="{{ route('maestros.index') }}" class="btn btn-outline-danger" title="Limpiar búsqueda">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

            </div>

            <div class="table-responsive">

                <table class="table table-striped table-hover table-bordered align-middle">

                    <thead class="table-dark">
                        <tr>
                            <th width="80">ID</th>
                            <th>Nombre</th>
                            <th>DNI</th>
                            <th>No Colegiado</th>
                            <th width="120" class="text-center">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($maestros as $maestro)

                            <tr>
                                <td>{{ $maestro->id }}</td>
                                <td>{{ $maestro->nombre }}</td>
                                <td>{{ $maestro->dni }}</td>
                                <td>{{ $maestro->no_colegiado }}</td>

                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editarModal{{ $maestro->id }}">
                                        <i class="fas fa-edit me-1"></i> Editar
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal Editar -->
                            <div class="modal fade" id="editarModal{{ $maestro->id }}" tabindex="-1" aria-labelledby="editarModalLabel{{ $maestro->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">

                                        <form action="{{ route('maestros.update', $maestro->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')

                                            <div class="modal-header bg-warning text-dark">
                                                <h5 class="modal-title" id="editarModalLabel{{ $maestro->id }}">
                                                    <i class="fas fa-edit me-1"></i> Editar Registro #{{ $maestro->id }}
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body text-start">

                                                <div class="mb-3">
                                                    <label for="nombre_{{ $maestro->id }}" class="form-label fw-bold">Nombre Completo</label>
                                                    <input type="text" class="form-control" id="nombre_{{ $maestro->id }}" name="nombre" value="{{ old('nombre', $maestro->nombre) }}" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="dni_{{ $maestro->id }}" class="form-label fw-bold">DNI</label>
                                                    <input type="text" class="form-control" id="dni_{{ $maestro->id }}" name="dni" value="{{ old('dni', $maestro->dni) }}" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="no_colegiado_{{ $maestro->id }}" class="form-label fw-bold">No. Colegiado</label>
                                                    <input type="text" class="form-control" id="no_colegiado_{{ $maestro->id }}" name="no_colegiado" value="{{ old('no_colegiado', $maestro->no_colegiado) }}">
                                                </div>

                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-warning">Guardar Cambios</button>
                                            </div>

                                        </form>

                                    </div>
                                </div>
                            </div>

                        @empty

                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle me-1"></i> No se encontraron registros.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            @if($maestros->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $maestros->withQueryString()->links() }}
                </div>
            @endif

        </div>

    </div>

</div>



<!-- Incluir biblioteca SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: "{{ session('success') }}",
            confirmButtonColor: '#198754'
        });
    </script>
@endif

@if($errors->any())
    <script>
        let errores = @json($errors->all());
        Swal.fire({
            icon: 'error',
            title: 'Error de validación',
            html: '<ul style="text-align: left;">' + errores.map(e => `<li>${e}</li>`).join('') + '</ul>',
            confirmButtonColor: '#dc3545'
        });
    </script>
@endif

@endsection