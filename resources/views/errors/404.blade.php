<head>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <title>Error 404 - Hotel Plaza JuanCarlos</title>
    <meta name="description" content="Error 404 en sitio web Hotel Plaza JuanCarlos." />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
</head>

@extends('layouts.template')
@section('content')
<body>
    <div class="container mt-5 pt-5">
        <div class="alert alert-danger text-center">
            <h1 class="display-3" style="font-weight: 900;">404</h1>
            <p class="display-7">Esta página no fue encontrada.</p>
        </div>
    </div>
</body>
@endsection
