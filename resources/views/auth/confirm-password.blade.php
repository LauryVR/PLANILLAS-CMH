<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Contraseña - Colegio Médico</title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        /* Fondo verde profundo con degradado elegante para contraste */
        body {
            background: linear-gradient(135deg, #0e4d2d 0%, #198754 100%);
            min-height: 100vh;
        }

        /* Efecto de elevación para la tarjeta */
        .card-login {
            background-color: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        /* Estilo del botón verde */
        .btn-verde {
            background-color: #198754;
            border-color: #198754;
            color: #ffffff;
            transition: all 0.3s ease;
        }
        .btn-verde:hover {
            background-color: #0e4d2d;
            border-color: #0e4d2d;
            color: #ffffff;
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-7 col-lg-4">

                <!-- Card flotante -->
                <div class="card card-login border-0">

                    <!-- Encabezado con Fondo Verde Oscuro y Logo de public/ -->
                    <div class="card-header bg-success text-white text-center py-4 rounded-top-4">
                        <div class="mb-2">
                            <img src="{{ asset('logo transparente 3602X2702.png') }}" 
                                 alt="Logo Colegio Médico" 
                                 class="img-fluid" 
                                 style="max-height: 95px; width: auto;">
                        </div>
                        <h4 class="fw-bold mb-0">Colegio Médico</h4>
                        <p class="small text-white-50 mb-0">Área Segura</p>
                    </div>

                    <div class="card-body p-4 p-md-5">

                        <!-- Mensaje explicativo de seguridad -->
                        <div class="alert alert-warning border-0 shadow-sm text-dark small mb-4">
                            <i class="fas fa-user-shield text-warning me-1"></i>
                            Esta es un área segura de la aplicación. Por favor, confirma tu contraseña antes de continuar.
                        </div>

                        <!-- Mensajes de Error de Validación -->
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Atención</strong>
                                </div>
                                <ul class="mb-0 ps-3 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <!-- Formulario -->
                        <form method="POST" action="{{ route('password.confirm') }}">
                            @csrf

                            <!-- Campo Contraseña -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold text-secondary">
                                    <i class="fas fa-lock me-1"></i> Contraseña Actual
                                </label>
                                <input id="password" 
                                       type="password" 
                                       name="password" 
                                       class="form-control form-control-lg @error('password') is-invalid @enderror" 
                                       placeholder="••••••••"
                                       required 
                                       autocomplete="current-password"
                                       autofocus>
                            </div>

                            <!-- Botón de Confirmación -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-verde btn-lg fw-bold shadow-sm">
                                    <i class="fas fa-check-circle me-2"></i> Confirmar
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-4 text-white-50 small">
                    &copy; {{ date('Y') }} Colegio Médico. Todos los derechos reservados.
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>