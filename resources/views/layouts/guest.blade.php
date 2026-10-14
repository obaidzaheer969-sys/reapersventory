<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'ReapersVentory'))</title>
    <link rel="icon" href="{{ asset('images/reapersventory-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app-brand.css') }}?v=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            700: '#16325a',
                            800: '#0f2342',
                            900: '#0a1628',
                            950: '#060d18',
                        },
                        brand: {
                            400: '#ff8a3d',
                            500: '#f15a24',
                            600: '#e04812',
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
</head>
<body class="min-h-screen bg-gradient-to-br from-navy-950 via-navy-900 to-navy-800 text-white antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
        <a href="{{ route('home') }}" class="mb-8 text-center">
            <span class="rv-logo mx-auto block h-24">
                <img src="{{ asset('images/reapersventory-logo.png') }}" alt="ReapersVentory" class="mx-auto h-24 w-auto">
            </span>
            <p class="mt-3 text-sm font-semibold text-white">Inventory, Billing, Business Growth.</p>
            <p class="mt-1 text-xs text-slate-300">A Reapers Tech Product</p>
        </a>

        <div class="w-full max-w-md rounded-2xl border border-white/10 bg-white p-8 text-navy-900 shadow-2xl shadow-black/30">
            @yield('content')
        </div>
    </div>
    @livewireScripts
</body>
</html>
