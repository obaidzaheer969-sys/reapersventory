<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'ReapersVentory'))</title>
    <link rel="icon" href="{{ asset('images/reapersventory-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app-brand.css') }}?v=2">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            50: '#eef3f9',
                            100: '#d5e1ef',
                            700: '#16325a',
                            800: '#0f2342',
                            900: '#0a1628',
                            950: '#060d18',
                        },
                        brand: {
                            400: '#ff8a3d',
                            500: '#f15a24',
                            600: '#e04812',
                            700: '#c0380c',
                        },
                    },
                    fontFamily: {
                        sans: ['Segoe UI', 'Helvetica Neue', 'Arial', 'sans-serif'],
                        display: ['Trebuchet MS', 'Segoe UI', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    @livewireStyles
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 text-navy-900 antialiased">
    <div class="min-h-screen flex flex-col">
        @include('layouts.partials.nav')

        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset

        @include('layouts.partials.footer')
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
