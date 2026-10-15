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
            <a href="{{ route('business.products.index') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Products</a>
            <a href="{{ route('business.subscription.index') }}" class="rounded-lg bg-navy-900 px-4 py-2 text-sm font-semibold text-white hover:bg-navy-800">Subscription</a>
        </div>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Subscription</p>
            <p class="mt-2 text-lg font-semibold text-navy-900">{{ $activeSubscription ? $activeSubscription->status->label() : 'Inactive' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Products</p>
            <p class="mt-2 text-lg font-semibold text-navy-900">{{ $productCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Warehouses</p>
            <p class="mt-2 text-lg font-semibold text-navy-900">{{ $warehouseCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Open purchases</p>
            <p class="mt-2 text-lg font-semibold text-navy-900">{{ $openPurchases }}</p>
        </div>
    </div>

    <div class="mt-8">
        <livewire:business.update-branding />
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-dashed border-brand-500/40 bg-brand-500/5 p-6">
            <h2 class="font-semibold text-navy-900">Low-stock alerts</h2>
            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                @forelse ($lowStock as $product)
                    <li>{{ $product->name }} — {{ number_format($product->totalQuantity(), 3) }} on hand</li>
                @empty
                    <li>{{ $activeSubscription ? 'No low-stock products.' : 'Activate a subscription to manage inventory.' }}</li>
                @endforelse
            </ul>
            <div class="mt-4 flex flex-wrap gap-3 text-sm font-semibold">
                <a href="{{ route('business.inventory.index') }}" class="text-brand-600 hover:text-brand-500">Inventory</a>
                <a href="{{ route('business.purchases.index') }}" class="text-brand-600 hover:text-brand-500">Purchases</a>
                <a href="{{ route('business.suppliers.index') }}" class="text-brand-600 hover:text-brand-500">Suppliers</a>
                <a href="{{ route('business.warehouses.index') }}" class="text-brand-600 hover:text-brand-500">Warehouses</a>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="font-semibold text-navy-900">Quick links</h2>
            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.categories.index') }}">Categories</a></li>
                <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.profile.edit') }}">Business profile</a></li>
                <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.sales.index') }}">Sales & invoices</a></li>
                <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.reports.index') }}">Financial reports</a></li>
                <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.subscription.index') }}">Billing & branding</a></li>
                <li><a class="text-brand-600 hover:text-brand-500" href="{{ route('business.notifications.index') }}">Notifications / alerts</a></li>
            </ul>
        </div>
    </div>
</div>
@endsection
