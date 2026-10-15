@extends('layouts.app')

@section('title', 'Inventory — ReapersVentory')

@section('content')
<x-business-section title="Inventory" subtitle="See current stock, add stock (pcs/kg/etc), and track movements.">
    <x-slot:actions>
        <a href="{{ route('business.products.index') }}" class="rounded-lg bg-navy-900 px-3 py-2 text-sm font-semibold text-white hover:bg-navy-800">Products</a>
        <a href="{{ route('business.warehouses.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-navy-900 hover:bg-slate-50">Warehouses</a>
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-navy-900">Add / remove stock</h2>
            <p class="mt-1 text-xs text-slate-500">Choose product, warehouse, and quantity in that product’s unit.</p>
            <div class="mt-4"><livewire:business.stock-adjustment-form /></div>
        </div>

        <div class="rounded-2xl border border-brand-500/30 bg-brand-500/5 p-5 lg:col-span-2">
            <h2 class="font-semibold text-navy-900">Low-stock alerts</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($lowStock as $product)
                    <li class="flex justify-between gap-3">
                        <span>{{ $product->name }} ({{ $product->sku }})</span>
                        <span class="font-semibold text-brand-600">{{ number_format($product->totalQuantity(), 3) }} / reorder {{ number_format($product->reorder_level, 3) }}</span>
                    </li>
                @empty
                    <li class="text-slate-600">No low-stock products.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm overflow-x-auto">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="font-semibold text-navy-900">Balances</h2>
                <form method="GET" class="flex gap-2">
                    <select name="warehouse_id" class="rounded-lg px-2 py-1 text-sm ring-1 ring-slate-300">
                        <option value="">All warehouses</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-lg bg-navy-900 px-3 py-1 text-sm text-white">Filter</button>
                </form>
            </div>
            <table class="min-w-full text-sm">
                <thead class="border-b text-slate-500"><tr><th class="py-2 text-left">Product</th><th class="py-2 text-left">Warehouse</th><th class="py-2 text-left">Stock</th></tr></thead>
                <tbody>
                    @forelse ($balances as $balance)
                        <tr class="border-b border-slate-100">
                            <td class="py-3">
                                <div class="font-medium text-navy-900">{{ $balance->product->name }}</div>
                                <div class="text-xs text-slate-500">{{ $balance->product->sku }}</div>
                            </td>
                            <td class="py-3">{{ $balance->warehouse->name }}</td>
                            <td class="py-3 font-semibold">{{ number_format($balance->quantity, 3) }} {{ $balance->product->unit }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-slate-500">No stock yet. Use Add stock on the left.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $balances->links() }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm overflow-x-auto">
            <h2 class="font-semibold text-navy-900">Recent movements</h2>
            <table class="mt-3 min-w-full text-sm">
                <thead class="border-b text-slate-500"><tr><th class="py-2 text-left">Type</th><th class="py-2 text-left">Product</th><th class="py-2 text-left">Qty</th><th class="py-2 text-left">After</th></tr></thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr class="border-b border-slate-100">
                            <td class="py-3">{{ $movement->type->label() }}</td>
                            <td class="py-3">{{ $movement->product->name }}</td>
                            <td class="py-3">{{ number_format($movement->quantity, 3) }} {{ $movement->product->unit }}</td>
                            <td class="py-3">{{ number_format($movement->balance_after, 3) }} {{ $movement->product->unit }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-slate-500">No movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-business-section>
@endsection
