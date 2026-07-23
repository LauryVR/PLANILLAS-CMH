<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight d-flex align-items-center">
            <i class="fas fa-user-plus me-2 text-success"></i>
            {{ __('Registrar Nuevo Usuario / Médico') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">

                    <!-- Tarjeta del Formulario Interno -->
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        
                        <!-- Encabezado de la Tarjeta -->
                        <div class="card-header bg-success text-white py-3 px-4">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-id-card me-2"></i>Datos de la Cuenta
                            </h5>
                            <small class="text-white-50">Ingresa la información del nuevo usuario del sistema.</small>
                        </div>

                        <div class="card-body p-4 bg-white">

                            <!-- Mensajes de Confirmación/Éxito -->
                            @if (session('status'))
                                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                                    <i class="fas fa-check-circle me-1"></i> {{ session('status') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <!-- Mensajes de Error de Validación -->
                            <x-auth-validation-errors class="alert alert-danger mb-4" :errors="$errors" />

                            <form method="POST" action="{{ route('register') }}">
                                @csrf

                                <!-- Nombre Completo -->
                                <div class="mb-3">
                                    <x-label for="name" class="form-label fw-semibold text-secondary" :value="__('Nombre Completo')" />
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-secondary"><i class="fas fa-user"></i></span>
                                        <x-input id="name" 
                                                 class="form-control" 
                                                 type="text" 
                                                 name="name" 
                                                 :value="old('name')" 
                                                 placeholder="Ej. Dr. Juan Pérez"
                                                 required 
                                                 autofocus />
                                    </div>
                                </div>

                                <!-- Correo Electrónico -->
                                <div class="mb-3">
                                    <x-label for="email" class="form-label fw-semibold text-secondary" :value="__('Correo Electrónico')" />
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-secondary"><i class="fas fa-envelope"></i></span>
                                        <x-input id="email" 
                                                 class="form-control" 
                                                 type="email" 
                                                 name="email" 
                                                 :value="old('email')" 
                                                 placeholder="correo@colegiomedico.hn"
                                                 required />
                                    </div>
                                </div>

                                <!-- Contraseña -->
                                <div class="mb-3">
                                    <x-label for="password" class="form-label fw-semibold text-secondary" :value="__('Contraseña')" />
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-secondary"><i class="fas fa-key"></i></span>
                                        <x-input id="password" 
                                                 class="form-control"
                                                 type="password"
                                                 name="password"
                                                 placeholder="••••••••"
                                                 required 
                                                 autocomplete="new-password" />
                                    </div>
                                </div>

                                <!-- Confirmar Contraseña -->
                                <div class="mb-4">
                                    <x-label for="password_confirmation" class="form-label fw-semibold text-secondary" :value="__('Confirmar Contraseña')" />
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-secondary"><i class="fas fa-lock"></i></span>
                                        <x-input id="password_confirmation" 
                                                 class="form-control"
                                                 type="password"
                                                 name="password_confirmation" 
                                                 placeholder="••••••••"
                                                 required />
                                    </div>
                                </div>

                                <!-- Botones de Acción -->
                                <div class="d-flex align-items-center justify-content-between pt-2">
                                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Cancelar
                                    </a>

                                    <button type="submit" class="btn btn-success fw-bold px-4">
                                        <i class="fas fa-save me-1"></i> Guardar Usuario
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>