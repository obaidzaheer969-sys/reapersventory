<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('earning.brand').' — '.config('earning.tagline'))</title>
    <link rel="icon" href="{{ asset('images/reapersventory-logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: { 950: '#060a12', 900: '#0a111e', 850: '#0e1728', 800: '#122035', 700: '#1b2c46' },
                        mint: { 200: '#a7f3d0', 300: '#6ee7b7', 400: '#34d399', 500: '#10b981', 600: '#059669' },
                        gold: { 300: '#fcd34d', 400: '#fbbf24', 500: '#f59e0b' },
                    },
                    fontFamily: {
                        sans: ['Segoe UI', 'Helvetica Neue', 'Arial', 'sans-serif'],
                        display: ['Trebuchet MS', 'Segoe UI', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <style>
        .glow-a { background: radial-gradient(circle at 20% 10%, rgba(16,185,129,.22), transparent 45%); }
        .glow-b { background: radial-gradient(circle at 80% 80%, rgba(59,130,246,.16), transparent 45%); }
        .card { background: linear-gradient(160deg, rgba(255,255,255,.06), rgba(255,255,255,.02)); }
    </style>
</head>
<body class="min-h-screen bg-ink-950 font-sans text-slate-100 antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 glow-a"></div>
    <div class="pointer-events-none fixed inset-0 -z-10 glow-b"></div>

    <header class="absolute right-4 top-4 z-20 flex items-center gap-2 sm:right-8 sm:top-6" aria-label="Account navigation">
        <a href="{{ route('login') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10 hover:text-white">
            Login
        </a>
        <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20 transition hover:from-mint-300 hover:to-mint-400">
            Register
        </a>
    </header>

    <div class="grid min-h-screen lg:grid-cols-2">
        <aside class="relative hidden flex-col justify-between overflow-hidden border-r border-white/10 p-10 lg:flex xl:p-14">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-mint-400 to-mint-600 text-xl font-black text-ink-950 shadow-lg shadow-mint-500/25">₨</span>
                <span class="leading-tight">
                    <span class="block font-display text-2xl font-bold text-white">{{ config('earning.brand') }}</span>
                </span>
            </a>

            <div class="max-w-lg">
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-mint-300">Watch · Follow · Earn</p>
                <h1 class="mt-4 font-display text-4xl font-bold leading-tight text-white xl:text-5xl">
                    Ghar baithe simple tasks complete karein aur PKR kamayen.
                </h1>
                <p class="mt-4 text-base leading-relaxed text-slate-300">
                    YouTube, Facebook, Instagram, WhatsApp aur ads ke tasks. Har task ke baad reward seedha aap ke wallet me — Easypaisa, JazzCash, UPaisa ya NayaPay par payout.
                </p>

                <div class="mt-8 grid grid-cols-3 gap-3">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-bold text-white">PKR</p>
                        <p class="mt-1 text-[11px] text-slate-400">Rewards</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-bold text-white">24h</p>
                        <p class="mt-1 text-[11px] text-slate-400">Daily reset</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-bold text-white">4</p>
                        <p class="mt-1 text-[11px] text-slate-400">Wallets</p>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap gap-2 text-xs font-semibold text-slate-300">
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5">▶ YouTube</span>
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5">f Facebook</span>
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5">◎ Instagram</span>
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5">✆ WhatsApp</span>
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5">★ Ads</span>
                </div>
            </div>

            <p class="text-xs text-slate-500">Secure login · Daily task locks · Transparent wallet history</p>
        </aside>

        <main class="flex items-center justify-center px-4 py-10 sm:px-8">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-2.5 lg:hidden">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-mint-400 to-mint-600 text-lg font-black text-ink-950">₨</span>
                    <span class="font-display text-xl font-bold text-white">{{ config('earning.brand') }}</span>
                </a>

                <div class="card rounded-3xl border border-white/10 p-6 shadow-2xl shadow-black/40 sm:p-8">
                    @if (session('status'))
                        <div class="mb-5 rounded-xl border border-mint-400/30 bg-mint-500/10 px-3 py-2 text-sm text-mint-200">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-5 rounded-xl border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200">
                            <ul class="list-inside list-disc space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>

                <p class="mt-6 text-center text-xs text-slate-500">
                    &copy; {{ date('Y') }} {{ config('earning.brand') }} · All amounts in PKR
                </p>
            </div>
        </main>
    </div>
</body>
</html>
