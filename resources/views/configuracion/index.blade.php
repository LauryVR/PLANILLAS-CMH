@extends('layouts.template')

@push('head')
    <title>Configuración - Portal Colegio Médico</title>
@endpush

@section('content')
<div class="container py-4">
    
    {{-- Header del Menú de Configuración --}}
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h2 class="fw-bold text-primary display-6">
                <i class="fas fa-cogs me-2"></i>Módulo de Configuración
            </h2>
            <p class="text-muted fs-5">Seleccione el parámetro o catálogo que desea administrar</p>
            <hr class="w-25 mx-auto border-primary border-2 opacity-75">
        </div>
    </div>

    {{-- Grid de Tarjetas de Menú --}}
    <div class="row g-4 justify-content-center">

        {{-- Card 1: Tipos de Cuenta --}}
        <div class="col-md-6 col-lg-4">
            <div class="card card-menu h-100 border-0 shadow-sm transition-all">
                <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                    
                    <div class="icon-shape bg-primary text-white rounded-circle mb-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px;">
                        <i class="fas fa-university fa-2x"></i>
                    </div>
                    
                    <h3 class="card-title fw-bold h5 mb-2">Tipos de Cuenta</h3>
                    <p class="card-text text-muted mb-4 small">
                        Añada, edite o parametrice los distintos tipos de cuentas bancarias y contables.
                    </p>
                    
                    <a href="{{ route('configuracion.tipos-cuenta.index') }}" class="btn btn-primary btn-lg w-100 mt-auto rounded-pill shadow-sm">
                        <i class="fas fa-plus-circle me-2"></i> Administrar Cuentas
                    </a>
                </div>
            </div>
        </div>

        {{-- Card 2: Entes de Retención --}}
        <div class="col-md-6 col-lg-4">
            <div class="card card-menu h-100 border-0 shadow-sm transition-all">
                <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                    
                    <div class="icon-shape bg-success text-white rounded-circle mb-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px;">
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                    
                    <h3 class="card-title fw-bold h5 mb-2">Entes de Retención</h3>
                    <p class="card-text text-muted mb-4 small">
                        Registre entidades, instituciones de previsión y bancos para retenciones de planilla.
                    </p>
                    
                    <a href="{{ route('configuracion.entes-retencion.index') }}" class="btn btn-success btn-lg w-100 mt-auto rounded-pill shadow-sm">
                        <i class="fas fa-plus-circle me-2"></i> Administrar Entes
                    </a>
                </div>
            </div>
        </div>

        {{-- Card 3: Motores de Retención --}}
        <div class="col-md-6 col-lg-4">
            <div class="card card-menu h-100 border-0 shadow-sm transition-all">
                <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                    
                    <div class="icon-shape bg-primary text-white rounded-circle mb-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px;">
                        <i class="fas fa-cogs fa-2x"></i>
                    </div>
                    
                    <h3 class="card-title fw-bold h5 mb-2">Motores de Retención</h3>
                    <p class="card-text text-muted mb-4 small">
                        Configure y gestione los motores de cálculo por ente retenedor.
                    </p>
                    
                    <a href="{{ route('motores.index') }}" class="btn btn-primary text-white fw-bold btn-lg w-100 mt-auto rounded-pill shadow-sm">
                        <i class="fas fa-sliders-h me-2"></i> Gestionar Motores
                    </a>
                </div>
            </div>
        </div>

        {{-- Card 4: Prioridad de Cuentas --}}
        <div class="col-md-6 col-lg-4">
            <div class="card card-menu h-100 border-0 shadow-sm transition-all">
                <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                    
                    <div class="icon-shape bg-warning text-dark rounded-circle mb-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px;">
                        <i class="fas fa-sort-amount-down-alt fa-2x"></i>
                    </div>
                    
                    <h3 class="card-title fw-bold h5 mb-2">Prioridad de Cuentas</h3>
                    <p class="card-text text-muted mb-4 small">
                        Modifique y establezca el orden de jerarquía y prioridad en la deducción de cuentas.
                    </p>
                    
                    <a href="{{ route('configuracion.prioridades-cuentas.index') }}" class="btn btn-warning text-dark fw-bold btn-lg w-100 mt-auto rounded-pill shadow-sm">
                        <i class="fas fa-sliders-h me-2"></i> Configurar Prioridades
                    </a>
                </div>
            </div>
        </div>

        {{-- Card 5: Administración de Usuarios --}}
        <div class="col-md-6 col-lg-4">
            <div class="card card-menu h-100 border-0 shadow-sm transition-all">
                <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                    
                    <div class="icon-shape bg-purple text-white rounded-circle mb-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px;">
                        <i class="fas fa-users-cog fa-2x"></i>
                    </div>
                    
                    <h3 class="card-title fw-bold h5 mb-2">Administración de Usuarios</h3>
                    <p class="card-text text-muted mb-4 small">
                        Gestione cuentas de usuario, asignación de roles, permisos del sistema y accesos.
                    </p>
                    
                    <a href="{{ route('admin.users.index') }}" class="btn btn-purple text-white fw-bold btn-lg w-100 mt-auto rounded-pill shadow-sm">
                        <i class="fas fa-user-shield me-2"></i> Gestionar Usuarios
                    </a>
                </div>
            </div>
        </div>

    </div>

    {{-- Botón para regresar --}}
    <div class="row mt-5">
        <div class="col-12 text-center">
            <a href="{{ route('inicio') }}" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i> Volver al Panel Principal
            </a>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .card-menu {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-radius: 1rem;
    }
    
    .card-menu:hover {
        transform: translateY(-8px);
        box-shadow: 0 1rem 2rem rgba(0,0,0,0.12) !important;
    }

    .icon-shape {
        transition: transform 0.3s ease;
    }

    .card-menu:hover .icon-shape {
        transform: scale(1.1);
    }

    /* Clases personalizadas para el color Púrpura/Violeta de Usuarios */
    .bg-purple {
        background-color: #6f42c1 !important;
    }

    .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
    }

    .btn-purple:hover {
        background-color: #59339d;
        border-color: #543192;
        color: #fff;
    }
</style>
@endpush