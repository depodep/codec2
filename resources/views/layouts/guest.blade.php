<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login - SIATRACK')</title>

    <!-- Tailwind CSS CDN & FontAwesome -->
    <!-- ADD THIS -->
@vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Global Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">

    @stack('styles')
</head>
<body class="bg-[#d1d5db] min-h-screen flex items-center justify-center p-4 lg:p-8">
    @yield('content')
    @stack('scripts')
</body>
</html>