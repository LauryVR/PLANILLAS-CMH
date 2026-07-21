@extends('layouts.template')

@push('head')
    <title>Inicio - Portal Colegio Médico</title>
    <meta name="description" content="Módulo de gestión de maestros y carga masiva de archivos Excel." />
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
            <div class="col-md-6 col-lg-5">
                <div class="card card-menu h-100 border-0 shadow-sm transition-all">
                    <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                        <div class="icon-shape bg-primary text-white rounded-circle mb-4 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-user-md fa-2x"></i>
                        </div>
                        <h3 class="card-title fw-bold h4 mb-2">Directorio de Maestros</h3>
                        <p class="card-text text-muted mb-4">
                            Consulte, edite y actualice la información de los colegiados registrados en el sistema.
                        </p>
                        <a href="{{ route('maestros.index') }}" class="btn btn-primary btn-lg w-100 mt-auto rounded-pill shadow-sm">
                            <i class="fas fa-list me-2"></i> Ir a Directorio
                        </a>
                    </div>
                </div>
            </div>

            {{-- Opción 2: Carga Masiva Excel --}}
            <div class="col-md-6 col-lg-5">
                <div class="card card-menu h-100 border-0 shadow-sm transition-all">
                    <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                        <div class="icon-shape bg-success text-white rounded-circle mb-4 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-file-excel fa-2x"></i>
                        </div>
                        <h3 class="card-title fw-bold h4 mb-2">Importación desde Excel Datos Maestros</h3>
                        <p class="card-text text-muted mb-4">
                            Cargue archivos masivos de datos, valide la información y corrija inconsistencias antes de guardar.
                        </p>
                        <a href="{{ route('excel.index') }}" class="btn btn-success btn-lg w-100 mt-auto rounded-pill shadow-sm">
                            <i class="fas fa-upload me-2"></i> Cargar Archivo
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>
@endsection

@push('styles')
<style>
    /* Efecto de elevación interactivo al pasar el cursor */
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