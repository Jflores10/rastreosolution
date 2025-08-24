<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name') }} | @yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
</head>
<body>
    <div id="app" class="container-fluid">
        @yield('content')        
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>

