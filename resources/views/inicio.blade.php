
@extends('layouts.template')

@push('head')
    <title>Inicio - Portal Colegio Médico</title>
    <meta name="description" content="Módulo de gestión de maestros, cuentas por cobrar y administración de usuarios." />
    <meta property="og:title" content="Portal de Gestión - Colegio Médico"/>
    <meta property="og:type" content="website" />
@endpush

@section('content')
<section class="bienvenidos py-5">
    <div class="container">
        
        {{-- Encabezado --}}
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h1 class="titulo-bienvenidos fw-bold text-uppercase display-5 text-primary">
                    Panel Principal
                </h1>
                <p class="text-muted fs-5">Seleccione la función a la que desea acceder</p>
                <hr class="w-25 mx-auto border-primary border-2 opacity-75">
            </div>
        </div>

        {{-- Menú interactivo de accesos directos --}}
        <div class="row g-4 justify-content-center">

            {{-- Opción 1: Gestión de Maestros --}}
            <div class="col-md-6 col-lg-3">
                <div class="card card-menu h-100 border-0 shadow-sm transition-all">
                    <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                        
                        <div class="icon-shape bg-primary text-white rounded-circle mb-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px;">
                            <i class="fas fa-address-book fa-2x"></i>
                        </div>
                        
                        <h3 class="card-title fw-bold h5 mb-2">Directorio de Maestros</h3>
                        <p class="card-text text-muted mb-4 small">
                            Consulte, edite y actualice la información de los colegiados registrados en el sistema.
                        </p>
                        <a href="{{ route('maestros.index') }}" class="btn btn-primary btn-lg w-100 mt-auto rounded-pill shadow-sm">
                            <i class="fas fa-list me-2"></i> Ir a Directorio
                        </a>
                    </div>
                </div>
            </div>

            {{-- Opción 2: Carga Masiva Excel --}}
            <div class="col-md-6 col-lg-3">
                <div class="card card-menu h-100 border-0 shadow-sm transition-all">
                    <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                        
                        <div class="icon-shape bg-success text-white rounded-circle mb-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px;">
                            <i class="fas fa-file-excel fa-2x"></i>
                        </div>
                        
                        <h3 class="card-title fw-bold h5 mb-2">Importación Excel Maestros</h3>
                        <p class="card-text text-muted mb-4 small">
                            Cargue archivos masivos de datos, valide la información y corrija inconsistencias.
                        </p>
                        <a href="{{ route('excel.index') }}" class="btn btn-success btn-lg w-100 mt-auto rounded-pill shadow-sm">
                            <i class="fas fa-upload me-2"></i> Cargar Archivo
                        </a>
                    </div>
                </div>
            </div>

            {{-- Opción 3: Cuentas por Cobrar (CxC) --}}
            <div class="col-md-6 col-lg-3">
                <div class="card card-menu h-100 border-0 shadow-sm transition-all">
                    <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                        
                        <div class="icon-shape bg-warning text-dark rounded-circle mb-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px;">
                            <i class="fas fa-file-invoice-dollar fa-2x"></i>
                        </div>
                        
                        <h3 class="card-title fw-bold h5 mb-2">Cuentas por Cobrar (CxC)</h3>
                        <p class="card-text text-muted mb-4 small">
                            Consulte y gestione los estados de cuenta, cargos y valores a cobrar por colegiado.
                        </p>
                        <a href="{{ url('/cuentas') }}" class="btn btn-warning text-dark fw-bold btn-lg w-100 mt-auto rounded-pill shadow-sm">
                            <i class="fas fa-hand-holding-usd me-2"></i> Ir a CxC
                        </a>
                    </div>
                </div>
            </div>

           {{-- Opción 4: Gestión de Usuarios / Administración --}}
<div class="col-md-6 col-lg-3">
    <div class="card card-menu h-100 border-0 shadow-sm transition-all">
        <div class="card-body p-4 text-center d-flex flex-column align-items-center">
            
            <div class="icon-shape bg-purple text-white rounded-circle mb-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px;">
                <i class="fas fa-users-cog fa-2x"></i>
            </div>
            
            <h3 class="card-title fw-bold h5 mb-2">Administración de Usuarios</h3>
            <p class="card-text text-muted mb-4 small">
                Gestione cuentas de usuario, asignación de roles, permisos del sistema y accesos.
            </p>
            
            {{-- AQUÍ ESTÁ EL CAMBIO: --}}
            <a href="{{ route('admin.users.index') }}" class="btn btn-purple text-white fw-bold btn-lg w-100 mt-auto rounded-pill shadow-sm">
                <i class="fas fa-user-shield me-2"></i> Gestionar Usuarios
            </a>

        </div>
    </div>
</div>
        </div> {{-- Fin .row --}}

    </div> {{-- Fin .container --}}
</section>
@endsection

@push('styles')
<style>
    /* Estilos personalizados para el módulo de Administración (Púrpura) */
    .bg-purple {
        background-color: #6f42c1 !important;
    }

    .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
    }

    .btn-purple:hover, .btn-purple:focus {
        background-color: #59319d;
        border-color: #59319d;
        color: #fff;
    }

    /* Efectos hover interactivos de las tarjetas */
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
</style>
@endpush