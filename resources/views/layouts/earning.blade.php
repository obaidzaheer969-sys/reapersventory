@php
    $siteApproved = (bool) \App\Models\SystemSetting::getValue('site_approved', false);
    $adsEnabled = (bool) \App\Models\SystemSetting::getValue('ads_enabled', false);
    $adCode = $adsEnabled ? (string) \App\Models\SystemSetting::getValue('ad_code', '') : '';
@endphp
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
        .glow-a { background: radial-gradient(circle at 15% 0%, rgba(16,185,129,.20), transparent 45%); }
        .glow-b { background: radial-gradient(circle at 85% 10%, rgba(59,130,246,.16), transparent 45%); }
        .card { background: linear-gradient(160deg, rgba(255,255,255,.055), rgba(255,255,255,.02)); }
        .ring-soft { box-shadow: inset 0 0 0 1px rgba(255,255,255,.08); }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-ink-950 font-sans text-slate-100 antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 glow-a"></div>
    <div class="pointer-events-none fixed inset-0 -z-10 glow-b"></div>

    @php
        $navUser = auth()->user();
        $unreadCount = $navUser ? $navUser->unreadNotifications()->count() : 0;
        $navLinks = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => '⌂'],
            ['label' => 'Admin Tasks', 'route' => 'tasks.index', 'icon' => '✓'],
            ['label' => 'Community', 'route' => 'campaigns.index', 'icon' => '◎'],
            ['label' => 'My Campaigns', 'route' => 'campaigns.mine', 'icon' => '★'],
            ['label' => 'Earnings', 'route' => 'earnings.index', 'icon' => '₨'],
            ['label' => 'Wallet', 'route' => 'wallet.index', 'icon' => '◈'],
            ['label' => 'Deposit', 'route' => 'deposits.index', 'icon' => '↓'],
            ['label' => 'Announcements', 'route' => 'announcements.index', 'icon' => '!' ],
        ];
    @endphp

    <header class="sticky top-0 z-40 border-b border-white/10 bg-ink-950/80 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-mint-400 to-mint-600 text-lg font-black text-ink-950 shadow-lg shadow-mint-500/20">₨</span>
                <span class="leading-tight">
                    <span class="block font-display text-lg font-bold tracking-tight text-white">{{ config('earning.brand') }}</span>
                </span>
            </a>

            <nav class="ml-6 hidden items-center gap-1 lg:flex">
                @foreach ($navLinks as $link)
                    <a href="{{ route($link['route']) }}"
                       class="rounded-xl px-3 py-2 text-sm font-semibold transition {{ request()->routeIs($link['route']) ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('notifications.index') }}" class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-slate-200 transition hover:bg-white/10" aria-label="Notifications">
                    <span class="text-lg">🔔</span>
                    @if ($unreadCount > 0)
                        <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-mint-400 px-1 text-[10px] font-bold text-ink-950">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </a>

                <div class="relative hidden sm:block">
                    <button type="button" data-profile-toggle class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 py-1.5 pl-1.5 pr-3 text-left transition hover:bg-white/10">
                        @if ($navUser?->avatar)
                            <img src="{{ $navUser->avatar }}" alt="" class="h-8 w-8 rounded-lg object-cover">
                        @else
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-mint-400 to-mint-600 text-sm font-bold text-ink-950">{{ strtoupper(substr($navUser?->name ?? 'U', 0, 1)) }}</span>
                        @endif
                        <span class="leading-tight">
                            <span class="block max-w-[120px] truncate text-xs font-bold text-white">{{ $navUser?->name }}</span>
                            <span class="block text-[10px] text-slate-400">PKR {{ number_format((float) ($navUser?->wallet_balance ?? 0), 2) }}</span>
                        </span>
                    </button>

                    <div data-profile-menu class="absolute right-0 mt-2 hidden w-56 overflow-hidden rounded-2xl border border-white/10 bg-ink-850 shadow-2xl shadow-black/40">
                        <div class="border-b border-white/10 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-white">{{ $navUser?->name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ $navUser?->email }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">Profile settings</a>
                        <a href="{{ route('earnings.index') }}" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">My earnings</a>
                        <a href="{{ route('campaigns.mine') }}" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">My campaigns</a>
                        <a href="{{ route('deposits.index') }}" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">Deposit money</a>
                        <a href="{{ route('wallet.index') }}" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">Wallet & payouts</a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-white/10">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2.5 text-left text-sm font-semibold text-rose-300 hover:bg-rose-500/10">Logout</button>
                        </form>
                    </div>
                </div>

                <button type="button" data-mobile-toggle class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-slate-200 lg:hidden" aria-label="Menu">
                    <span class="text-lg">☰</span>
                </button>
            </div>
        </div>

        <div data-mobile-menu class="hidden border-t border-white/10 bg-ink-900 px-4 pb-4 pt-2 lg:hidden">
            @foreach ($navLinks as $link)
                <a href="{{ route($link['route']) }}" class="block rounded-xl px-3 py-2.5 text-sm font-semibold {{ request()->routeIs($link['route']) ? 'bg-white/10 text-white' : 'text-slate-300' }}">{{ $link['label'] }}</a>
            @endforeach
            <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-300">Profile</a>
            <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-white/10 pt-2">
                @csrf
                <button type="submit" class="block w-full rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-rose-300">Logout</button>
            </form>
        </div>
    </header>

    <main class="relative mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-5 rounded-2xl border border-mint-400/30 bg-mint-500/10 px-4 py-3 text-sm text-mint-200">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                <p class="font-semibold">Kuch masla hua:</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($siteApproved)
            <div class="mb-4 inline-flex items-center gap-1.5 rounded-full border border-mint-400/30 bg-mint-500/10 px-3 py-1 text-xs font-semibold text-mint-200">
                <span aria-hidden="true">✓</span> Approved
            </div>
        @endif

        @if ($adCode !== '')
            <div class="mb-5 overflow-hidden rounded-2xl border border-white/10 bg-white/5 px-3 py-3" aria-label="Advertisement">
                {!! $adCode !!}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="relative mx-auto w-full max-w-7xl px-4 pb-8 pt-4 text-xs text-slate-500 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center justify-between gap-2 border-t border-white/10 pt-4 sm:flex-row">
            <p>&copy; {{ date('Y') }} {{ config('earning.brand') }}. All earnings are shown in PKR.</p>
            <p>{{ config('earning.tagline') }} · Support: {{ config('earning.support_email') }}</p>
        </div>
    </footer>

    <script>
        document.querySelectorAll('[data-mobile-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                document.querySelector('[data-mobile-menu]')?.classList.toggle('hidden');
            });
        });

        document.querySelectorAll('[data-profile-toggle]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                document.querySelector('[data-profile-menu]')?.classList.toggle('hidden');
            });
        });

        document.addEventListener('click', function () {
            document.querySelector('[data-profile-menu]')?.classList.add('hidden');
        });
    </script>

    <script>
        (function () {
            var nodes = Array.prototype.slice.call(document.querySelectorAll('[data-countdown-to]'));

            if (nodes.length === 0) {
                return;
            }

            function pad(value) {
                return String(value).padStart(2, '0');
            }

            function tick() {
                var now = Date.now();
                var reload = false;

                nodes.forEach(function (node) {
                    var target = parseInt(node.getAttribute('data-countdown-to'), 10);

                    if (isNaN(target)) {
                        return;
                    }

                    var totalSeconds = Math.max(0, Math.floor((target - now) / 1000));
                    var days = Math.floor(totalSeconds / 86400);
                    var hours = Math.floor((totalSeconds % 86400) / 3600);
                    var minutes = Math.floor((totalSeconds % 3600) / 60);
                    var seconds = totalSeconds % 60;

                    if (totalSeconds === 0) {
                        node.textContent = 'Unlocked';
                        if (node.hasAttribute('data-countdown-reload')) {
                            reload = true;
                        }

                        return;
                    }

                    node.textContent = (days > 0 ? days + 'd ' : '') + pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);
                });

                if (reload && !window.__earningTimerReloaded) {
                    window.__earningTimerReloaded = true;
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 1500);
                }
            }

            tick();
            window.setInterval(tick, 1000);
        })();
    </script>
    @stack('scripts')
</body>
</html>
