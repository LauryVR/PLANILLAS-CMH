@auth
<nav class="navbar navbar-expand-lg navbar-dark bg-navbar-custom fixed-top shadow">
  <div class="container-fluid px-4">
    
    <!-- Brand / Logo apuntando a Inicio -->
    <a class="navbar-brand me-auto d-flex align-items-center brand-hover" href="{{ route('inicio') }}">
      <img src="{{ asset('logo transparente 3602X2702.png') }}" alt="Colegio Médico" class="logo me-2" style="max-height: 42px;">
      <span class="fw-bold fs-5 text-white d-none d-sm-inline-block tracking-wide">Colegio Médico</span>
    </a>

    <!-- Botón hamburguesa (Mobile) -->
    <button class="navbar-toggler border-0 shadow-none px-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Enlaces del Menú -->
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2 pt-3 pt-lg-0">

        <!-- Inicio -->
        <li class="nav-item">
          <a class="nav-link nav-link-interactive {{ request()->routeIs('inicio') ? 'active-custom' : '' }}" href="{{ route('inicio') }}">
            <i class="fas fa-home me-1"></i> <span>Inicio</span>
          </a>
        </li>

        <!-- Menú Desplegable: Gestiones -->
        <li class="nav-item dropdown">
          <a class="nav-link nav-link-interactive dropdown-toggle {{ request()->routeIs('maestros.*') || request()->routeIs('excel.*') || request()->routeIs('cuentas.*') ? 'active-custom' : '' }}" 
             href="#" id="navbarGestiones" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-tasks me-1"></i> <span>Gestiones</span>
          </a>
          
          <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 overflow-hidden animate slideIn mt-2 p-2" aria-labelledby="navbarGestiones">
            
            <!-- Opción: Directorio de Maestros -->
            <li>
              <a class="dropdown-item p-2 rounded-3 d-flex align-items-center dropdown-item-custom" href="{{ route('maestros.index') }}">
                <div class="icon-box bg-emerald-light text-emerald me-3 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                  <i class="fas fa-user-md fa-lg"></i>
                </div>
                <div>
                  <div class="fw-bold text-dark">Directorio de Maestros</div>
                  <small class="text-muted d-block">Consulta y edición de colegiados</small>
                </div>
              </a>
            </li>

            <li><hr class="dropdown-divider my-2 opacity-10"></li>

            <!-- Opción: Carga Masiva Excel -->
            <li>
              <a class="dropdown-item p-2 rounded-3 d-flex align-items-center dropdown-item-custom" href="{{ route('excel.index') }}">
                <div class="icon-box bg-success-light text-success me-3 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                  <i class="fas fa-file-excel fa-lg"></i>
                </div>
                <div>
                  <div class="fw-bold text-dark">Importar desde Excel</div>
                  <small class="text-muted d-block">Carga masiva de datos maestros</small>
                </div>
              </a>
            </li>

            <li><hr class="dropdown-divider my-2 opacity-10"></li>

            <!-- Opción: Carga de Cuentas por Cobrar -->
            <li>
              <a class="dropdown-item p-2 rounded-3 d-flex align-items-center dropdown-item-custom" href="{{ route('cuentas.index') }}">
                <div class="icon-box bg-warning-light text-warning me-3 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                  <i class="fas fa-receipt fa-lg"></i>
                </div>
                <div>
                  <div class="fw-bold text-dark">Cuentas por Cobrar (CxC)</div>
                  <small class="text-muted d-block">Importación y validación de cobros</small>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- Menú de Usuario -->
        <li class="nav-item dropdown ms-lg-2">
          <a class="nav-link nav-link-interactive dropdown-toggle d-flex align-items-center gap-2 {{ request()->routeIs('password.change.edit') ? 'active-custom' : '' }}" 
             href="#" id="navbarUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
              {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <span>{{ Auth::user()->name }}</span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 overflow-hidden animate slideIn mt-2 p-2" aria-labelledby="navbarUser">
            <li>
              <div class="px-3 py-2">
                <p class="mb-0 fw-bold text-dark small">{{ Auth::user()->name }}</p>
                <p class="mb-0 text-muted small text-truncate" style="max-width: 180px;">{{ Auth::user()->email }}</p>
              </div>
            </li>

            <li><hr class="dropdown-divider my-1 opacity-10"></li>

            <!-- Opción: Cambiar Contraseña -->
            <li>
              <a class="dropdown-item p-2 rounded-3 d-flex align-items-center dropdown-item-custom" href="{{ route('password.change.edit') }}">
                <div class="icon-box bg-primary-light text-primary me-2 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                  <i class="fas fa-key"></i>
                </div>
                <span class="fw-semibold text-dark">Cambiar Contraseña</span>
              </a>
            </li>

            <li><hr class="dropdown-divider my-1 opacity-10"></li>

            <!-- Opción: Cerrar Sesión -->
            <li>
              <form method="POST" action="{{ route('logout') }}" id="logout-form">
                @csrf
                <button type="submit" class="dropdown-item p-2 rounded-3 d-flex align-items-center text-danger dropdown-item-custom fw-semibold">
                  <div class="icon-box bg-danger-light text-danger me-2 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                    <i class="fas fa-sign-out-alt"></i>
                  </div>
                  <span>Cerrar Sesión</span>
                </button>
              </form>
            </li>
          </ul>
        </li>

      </ul>
    </div>

  </div>
</nav>
@endauth

<style>
  /* Color verde corporativo con degradado */
  .bg-navbar-custom {
    background: linear-gradient(135deg, #0e6251 0%, #117864 100%);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  /* Animación ligera en el logo */
  .brand-hover img {
    transition: transform 0.3s ease;
  }
  .brand-hover:hover img {
    transform: scale(1.06);
  }

  /* Estilos interactivos para enlaces del menú */
  .nav-link-interactive {
    color: rgba(255, 255, 255, 0.9) !important;
    font-weight: 500;
    padding: 0.5rem 1rem !important;
    border-radius: 50px;
    transition: all 0.25s ease-in-out;
  }

  .nav-link-interactive:hover {
    color: #ffffff !important;
    background-color: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
  }

  /* Estado activo cuando está en la página actual */
  .active-custom {
    color: #ffffff !important;
    background-color: rgba(255, 255, 255, 0.22) !important;
    font-weight: 700;
  }

  /* Animación del menú desplegable */
  @media (min-width: 992px) {
    .animate {
      animation-duration: 0.25s;
      animation-fill-mode: both;
    }
    .slideIn {
      animation-name: slideInNavbar;
    }
  }

  @keyframes slideInNavbar {
    0% {
      transform: translateY(10px);
      opacity: 0;
    }
    100% {
      transform: translateY(0);
      opacity: 1;
    }
  }

  /* Íconos e interacción en el Dropdown */
  .icon-box {
    width: 42px;
    height: 42px;
    transition: transform 0.25s ease;
  }

  .bg-emerald-light {
    background-color: #e8f8f5;
  }
  .text-emerald {
    color: #117864;
  }

  .bg-success-light {
    background-color: #eaf2e8;
  }

  .bg-warning-light {
    background-color: #fff8e7;
  }

  .bg-danger-light {
    background-color: #fce8e6;
  }

  .bg-primary-light {
    background-color: #e7f1ff;
  }

  .dropdown-item-custom {
    transition: background-color 0.2s ease, transform 0.2s ease;
  }

  .dropdown-item-custom:hover {
    background-color: #f2f9f6;
    transform: translateX(4px);
  }

  .dropdown-item-custom:hover .icon-box {
    transform: scale(1.1);
  }

  .tracking-wide {
    letter-spacing: 0.5px;
  }
</style>