@extends('layouts.template')

@section('content')
<div class="container py-5">

    {{-- Alertas de éxito / error estáticas (respaldo) --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow border-0">

        {{-- Encabezado del módulo --}}
        <div class="card-header bg-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0 fs-4">
                    <i class="fas fa-users-cog me-2"></i> Gestión e Historia de Usuarios
                </h3>

                <a href="{{ route('admin.users.create') }}" class="btn btn-light text-primary fw-bold">
                    <i class="fas fa-user-plus me-1"></i> Nuevo Usuario
                </a>
            </div>
        </div>

        <div class="card-body">

            {{-- Buscador y total de registros --}}
            <div class="row mb-3 align-items-center">
                <div class="col-md-5 mb-2 mb-md-0">
                    <h6 class="mb-0">
                        Total de usuarios:
                        <span class="badge bg-primary fs-6">{{ $usuarios->total() }}</span>
                    </h6>
                </div>

                <div class="col-md-7">
                    <form action="{{ route('admin.users.index') }}" method="GET">
                        <div class="input-group">
                            <input type="text"
                                   name="buscar"
                                   class="form-control"
                                   placeholder="Buscar por nombre o correo..."
                                   value="{{ request('buscar') }}">

                            <button class="btn btn-primary" type="submit" title="Buscar">
                                <i class="fas fa-search"></i> Buscar
                            </button>

                            @if(request('buscar'))
                                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-danger" title="Limpiar filtro">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- Tabla de Usuarios --}}
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th width="60">ID</th>
                            <th>Nombre Completo</th>
                            <th>Correo Electrónico</th>
                            <th width="140" class="text-center">Rol</th>
                            <th width="110" class="text-center">Estado</th>
                            <th width="160">Fecha Registro</th>
                            <th width="240" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuarios as $usuario)
                            <tr>
                                <td class="fw-bold">{{ $usuario->id }}</td>
                                <td>{{ $usuario->name }}</td>
                                <td>{{ $usuario->email }}</td>
                                
                                {{-- Badge de Rol --}}
                                <td class="text-center">
                                    @if($usuario->role === 'ADMINISTRADOR')
                                        <span class="badge bg-dark text-white px-2 py-1">
                                            <i class="fas fa-user-shield me-1"></i> Admin
                                        </span>
                                    @else
                                        <span class="badge bg-secondary px-2 py-1">
                                            <i class="fas fa-user me-1"></i> Usuario
                                        </span>
                                    @endif
                                </td>

                                {{-- Badge de Estado (1: Activo, 0: Inactivo) --}}
                                <td class="text-center">
                                    @if((int)$usuario->status === 1)
                                        <span class="badge bg-success px-2 py-1">Activo</span>
                                    @else
                                        <span class="badge bg-danger px-2 py-1">Inactivo</span>
                                    @endif
                                </td>

                                <td>{{ $usuario->created_at ? $usuario->created_at->format('d/m/Y h:i A') : 'N/A' }}</td>
                                
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        {{-- Botón Editar Datos --}}
                                        <button type="button" 
                                                class="btn btn-warning btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal{{ $usuario->id }}"
                                                title="Editar datos">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        {{-- Botón Cambiar Contraseña --}}
                                        <button type="button" 
                                                class="btn btn-info btn-sm text-white" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#passwordModal{{ $usuario->id }}"
                                                title="Cambiar contraseña">
                                            <i class="fas fa-key"></i>
                                        </button>

                                        {{-- Botón Cambiar Rol --}}
                                        <button type="button" 
                                                class="btn btn-secondary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#roleModal{{ $usuario->id }}"
                                                title="Cambiar rol">
                                            <i class="fas fa-user-tag"></i>
                                        </button>

                                        {{-- Botones de Estado y Eliminar (Protección para usuario actual) --}}
                                        @if(auth()->id() !== $usuario->id)
                                            {{-- Cambiar Estado Directo (Un solo clic) --}}
                                            <form action="{{ route('admin.users.toggle-status', $usuario->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="btn {{ (int)$usuario->status === 1 ? 'btn-outline-danger' : 'btn-outline-success' }} btn-sm"
                                                        title="{{ (int)$usuario->status === 1 ? 'Inactivar usuario' : 'Activar usuario' }}">
                                                    <i class="fas {{ (int)$usuario->status === 1 ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                                </button>
                                            </form>

                                        
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle me-1"></i> No se encontraron usuarios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if($usuarios->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $usuarios->withQueryString()->links() }}
                </div>
            @endif

        </div>
    </div>
</div>

{{-- MODALES DECLARADOS FUERA DE LA TABLA --}}
@foreach($usuarios as $usuario)

    <!-- MODAL 1: EDITAR DATOS -->
    <div class="modal fade" id="editModal{{ $usuario->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.users.update', $usuario->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-user-edit me-1"></i> Editar Usuario #{{ $usuario->id }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre Completo</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $usuario->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $usuario->email) }}" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning fw-bold">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: CAMBIAR CONTRASEÑA -->
    <div class="modal fade" id="passwordModal{{ $usuario->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.users.password', $usuario->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-key me-1"></i> Cambiar Contraseña: {{ $usuario->name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nueva Contraseña</label>
                            <input type="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Confirmar Nueva Contraseña</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Repite la contraseña" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info text-white fw-bold">Actualizar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 3: CAMBIAR ROL -->
    <div class="modal fade" id="roleModal{{ $usuario->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.users.role', $usuario->id) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="modal-header bg-secondary text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-user-tag me-1"></i> Asignar Rol: {{ $usuario->name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body text-start">
                        <label class="form-label fw-bold mb-2">Selecciona el rol para este usuario:</label>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="role" id="roleUser{{ $usuario->id }}" value="USUARIO" {{ $usuario->role === 'USUARIO' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="roleUser{{ $usuario->id }}">
                                <i class="fas fa-user me-1 text-secondary"></i> Usuario Estándar
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role" id="roleAdmin{{ $usuario->id }}" value="ADMINISTRADOR" {{ $usuario->role === 'ADMINISTRADOR' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="roleAdmin{{ $usuario->id }}">
                                <i class="fas fa-user-shield me-1 text-primary"></i> Administrador
                            </label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold">Actualizar Rol</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if(auth()->id() !== $usuario->id)
        <!-- MODAL 4: ELIMINAR -->
        <div class="modal fade" id="deleteModal{{ $usuario->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('admin.users.destroy', $usuario->id) }}" method="POST">
                        @csrf
                        @method('DELETE')

                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title fw-bold">
                                <i class="fas fa-exclamation-triangle me-1"></i> Eliminar Usuario
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body text-start">
                            ¿Estás seguro de que deseas eliminar al usuario <strong>{{ $usuario->name }}</strong> ({{ $usuario->email }})?
                            <br><small class="text-danger">Esta acción no se puede deshacer.</small>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger fw-bold">Sí, Eliminar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

@endforeach
@endsection

@push('scripts')
<!-- SweetAlert2 -->
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
@endpush