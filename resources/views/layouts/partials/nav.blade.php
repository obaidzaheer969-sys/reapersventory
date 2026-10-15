<header class="border-b border-navy-900/10 bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
        <a href="{{ route('home') }}" class="flex flex-col items-start">
            <span class="rv-logo h-12">
                <img src="{{ asset('images/reapersventory-logo.png') }}" alt="ReapersVentory" class="h-12 w-auto">
            </span>
            <span class="mt-1 leading-tight">
                <span class="block text-[10px] font-semibold text-navy-900 sm:text-xs">Inventory, Billing, Business Growth.</span>
                <span class="block text-[9px] text-slate-500 sm:text-[10px]">A Reapers Tech Product</span>
            </span>
        </a>

        <nav class="flex items-center gap-2 text-sm font-semibold sm:gap-3">
            <a href="{{ route('pricing') }}" class="hidden text-navy-800 hover:text-brand-600 sm:inline">Pricing</a>

            @auth
                @if (auth()->user()->isSuperAdmin())
                    <a href="/admin" class="text-navy-800 hover:text-brand-600">Admin</a>
                @endif
                @if (auth()->user()->isBusinessOwner())
                    <a href="{{ route('business.dashboard') }}" class="text-navy-800 hover:text-brand-600">Dashboard</a>
                    <a href="{{ route('business.sales.index') }}" class="hidden text-navy-800 hover:text-brand-600 md:inline">Sales</a>
                    <a href="{{ route('business.products.index') }}" class="hidden text-navy-800 hover:text-brand-600 lg:inline">Products</a>
                    <a href="{{ route('business.reports.index') }}" class="hidden text-navy-800 hover:text-brand-600 lg:inline">Reports</a>
                    <a href="{{ route('business.subscription.index') }}" class="hidden text-navy-800 hover:text-brand-600 sm:inline">Billing</a>
                    <a href="{{ route('business.low-stock.index') }}" class="inline-flex items-center gap-1.5 text-navy-800 hover:text-brand-600">
                        Low stock
                        @if (($navLowStockCount ?? 0) > 0)
                            <span data-testid="nav-low-stock-count" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-500 px-1.5 text-[10px] font-bold leading-none text-white">{{ $navLowStockCount > 9 ? '9+' : $navLowStockCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('business.notifications.index') }}" class="inline-flex items-center gap-1.5 text-navy-800 hover:text-brand-600">
                        Alerts
                        @if (($unreadNotificationsCount ?? 0) > 0)
                            <span data-testid="nav-alerts-count" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-500 px-1.5 text-[10px] font-bold leading-none text-white">{{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}</span>
                        @endif
                    </a>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-navy-900 px-3 py-2 text-white hover:bg-navy-800">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-navy-800 hover:text-brand-600">Login</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-brand-500 px-3 py-2 text-white hover:bg-brand-600">Start free</a>
            @endauth
        </nav>
    </div>
</header>
