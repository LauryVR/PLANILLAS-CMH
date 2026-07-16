<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('layouts.styles')
    <title>Base</title>
    <link rel="icon" href=".base/flaticon.png" />
    <link rel="shortcut icon" href=".base/flaticon.png" />
    <meta property="og:title" content="Base"/>
    <meta property="og:url" content="" />
    <meta property="og:image" content="" />
    <meta property="description" content="" />
    <meta property="image" content="" />
  </head>
  <body>
        @include('layouts.navbar')
        @yield('content')
        @include('layouts.footer')
        @include('layouts.scripts')
        
  </body>
</html>