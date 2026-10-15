@extends('layouts.app')

@section('title', 'Dashboard — ReapersVentory')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex items-start gap-4">
            @if ($isPaid && $business->brandLogoUrl())
                <span class="rv-logo h-14 shrink-0">
                    <img src="{{ $business->brandLogoUrl() }}" alt="{{ $business->brandDisplayName() }}" class="h-14 w-auto rounded-md object-contain">
                </span>
            @endif
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-600">Business dashboard</p>
                <h1 class="mt-1 font-display text-3xl font-bold text-navy-900">
                    {{ $isPaid ? $business->brandDisplayName() : $business->name }}
                </h1>
                <p class="mt-1 text-slate-600">Welcome back, {{ $user->name }}.</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('business.sales.create') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">New sale</a>
            <a href="{{ route('business.products.index') }}" class="rounded-lg bg-navy-900 px-4 py-2 text-sm font-semibold text-white hover:bg-navy-800">Products</a>
            <a href="{{ route('business.inventory.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-navy-900 hover:bg-slate-50">Add stock</a>
        </div>
    </div>

    @unless ($notificationsReady)
        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Alerts database is not ready yet. Run hosting deploy/update so notifications can be saved.
        </div>
    @endunless

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Subscription</p>
            <p class="mt-2 text-lg font-semibold text-navy-900">{{ $activeSubscription ? $activeSubscription->status->label() : 'Inactive' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Products</p>
            <p class="mt-2 text-lg font-semibold text-navy-900">{{ $productCount }}</p>
        </div>
        <a href="{{ route('business.low-stock.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:border-brand-300">
            <p class="text-sm text-slate-500">Low-stock items</p>
            <p class="mt-2 text-lg font-semibold {{ count($lowStock) > 0 ? 'text-brand-600' : 'text-navy-900' }}">{{ count($lowStock) }}</p>
        </a>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Warehouses</p>
            <p class="mt-2 text-lg font-semibold text-navy-900">{{ $warehouseCount }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold text-navy-900">Recent alerts</h2>
                <a href="{{ route('business.notifications.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-500">View all</a>
            </div>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($recentNotifications as $notification)
                    @php $data = $notification->data; @endphp
                    <li>
                        <a href="{{ route('business.notifications.read', $notification->id) }}" class="block rounded-lg border border-slate-100 px-3 py-2 hover:bg-slate-50 {{ $notification->read_at ? 'opacity-70' : '' }}">
                            <p class="font-semibold text-navy-900">{{ $data['title'] ?? 'Notification' }}</p>
                            <p class="text-slate-600">{{ $data['body'] ?? '' }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                        </a>
                    </li>
                @empty
                    <li class="text-slate-600">No alerts yet. Sales and system alerts will show here.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold text-navy-900">Recent sales</h2>
                <a href="{{ route('business.sales.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-500">All sales</a>
            </div>
            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                @forelse ($recentSales as $sale)
                    <li class="flex items-center justify-between gap-3 border-b border-slate-100 py-2">
                        <a href="{{ route('business.sales.show', $sale) }}" class="font-semibold text-navy-900 hover:text-brand-600">
                            {{ $sale->sale_number }}
                            <span class="font-normal text-slate-500">· {{ $sale->customerLabel() }}</span>
                        </a>
                        <span class="font-semibold">{{ number_format((float) $sale->total, 2) }}</span>
                    </li>
                @empty
                    <li class="text-slate-600">No sales yet. <a href="{{ route('business.sales.create') }}" class="font-semibold text-brand-600">Create a sale</a></li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-8 rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="font-semibold text-navy-900">Quick links</h2>
        <ul class="mt-3 grid grid-cols-2 gap-2 text-sm font-semibold sm:grid-cols-4">
            <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.categories.index') }}">Categories</a></li>
            <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.customers.index') }}">Customers</a></li>
            <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.suppliers.index') }}">Suppliers</a></li>
            <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.reports.index') }}">Reports</a></li>
            <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.invoices.index') }}">Invoices</a></li>
            <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.subscription.index') }}">Billing</a></li>
            <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.warehouses.index') }}">Warehouses</a></li>
            <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.team-users.index') }}">Team users</a></li>
            <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.low-stock.index') }}">Low stock</a></li>
            <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.notifications.index') }}">Alerts</a></li>
            <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.profile.edit') }}">Profile</a></li>
        </ul>
    </div>

    <div class="mt-8">
        <livewire:business.update-branding />
    </div>
</div>
@endsection
