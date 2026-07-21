
<!doctype html>
<html lang="es">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    @include('layouts.styles')
    @stack('styles') {{-- Carga estilos dinámicos de las vistas --}}

    <title>Base</title>
    <link rel="icon" href="{{ asset('base/flaticon.png') }}" />
    <link rel="shortcut icon" href="{{ asset('base/flaticon.png') }}" />
    <meta property="og:title" content="Base"/>
    <meta property="og:url" content="" />
    <meta property="og:image" content="" />
    <meta property="description" content="" />
    <meta property="image" content="" />

    <style>
      /* Asegura que no haya márgenes externos y compensa la altura del navbar fixed */
      html, body {
        margin: 0 !important;
        padding: 0 !important;
      }
      body {
        padding-top: 70px !important; /* Ajusta la altura exacta que ocupa el navbar */
      }
    </style>
  </head>
  
  <body>
      {{-- Navbar Superior --}}
      @include('layouts.navbar')
      
      {{-- Contenido Principal --}}
      <main class="py-4">
          @yield('content')
      </main>

      {{-- Pie de página y Scripts --}}
      @include('layouts.footer')
      @include('layouts.scripts')
      @stack('scripts')
  </body>
</html>