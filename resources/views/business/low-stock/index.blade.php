@extends('layouts.app')

@section('title', 'Low stock — ReapersVentory')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-3xl font-bold text-navy-900">Low stock</h1>
            <p class="mt-1 text-slate-600">
                Products at or below reorder level
                @if (count($lowStock) > 0)
                    · {{ count($lowStock) }} item{{ count($lowStock) === 1 ? '' : 's' }}
                @endif
            </p>
        </div>
        <a href="{{ route('business.inventory.index') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">
            Add stock
        </a>
    </div>

    <div class="mt-8 space-y-3">
        @forelse ($lowStock as $product)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-brand-200 bg-white p-4 shadow-sm">
                <div>
                    <p class="font-semibold text-navy-900">{{ $product->name }}</p>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ number_format((float) $product->inventoryBalances->sum('quantity'), 3) }} {{ $product->unit }}
                        <span class="text-slate-500">(alert at {{ number_format((float) $product->effectiveReorderLevel(), 3) }})</span>
                    </p>
                    @if ($product->sku)
                        <p class="mt-1 text-xs text-slate-400">SKU: {{ $product->sku }}</p>
                    @endif
                </div>
                <a href="{{ route('business.inventory.index', ['product' => $product->id]) }}" class="rounded-lg border border-brand-500 px-3 py-2 text-sm font-semibold text-brand-600 hover:bg-brand-50">
                    Add stock
                </a>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
                {{ $activeSubscription ? 'No low-stock products right now.' : 'Activate a subscription to manage inventory.' }}
            </div>
        @endforelse
    </div>

    <div class="mt-6 flex flex-wrap gap-4 text-sm font-semibold">
        <a href="{{ route('business.inventory.index') }}" class="text-brand-600 hover:text-brand-500">Inventory</a>
        <a href="{{ route('business.purchases.index') }}" class="text-brand-600 hover:text-brand-500">Purchases</a>
        <a href="{{ route('business.warehouses.index') }}" class="text-brand-600 hover:text-brand-500">Warehouses</a>
        <a href="{{ route('business.notifications.index') }}" class="text-brand-600 hover:text-brand-500">Alerts</a>
    </div>
</div>
@endsection
