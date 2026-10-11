<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'ANBG'))</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @stack('meta')
    @stack('preconnect')
    @vite($vite ?? ['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="@yield('body_class')">
    @yield('content')
    @stack('scripts')
</body>
</html>
