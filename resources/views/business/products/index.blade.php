@extends('layouts.app')

@section('title', 'Products — ReapersVentory')

@section('content')
<x-business-section title="Products" subtitle="Manage SKUs, barcodes, and pricing for your catalog.">
    <x-slot:actions>
        <form method="GET" class="flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name/SKU/barcode"
                   class="rounded-lg px-3 py-2 text-sm ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            <button class="rounded-lg bg-navy-900 px-3 py-2 text-sm font-semibold text-white">Search</button>
        </form>
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-1">
            <h2 class="font-semibold text-navy-900">Add product</h2>
            <div class="mt-4">
                <livewire:business.product-form />
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b text-slate-500">
                    <tr>
                        <th class="py-2 pr-3">Name</th>
                        <th class="py-2 pr-3">SKU</th>
                        <th class="py-2 pr-3">Price</th>
                        <th class="py-2 pr-3">Stock</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-3">
                                <div class="font-medium text-navy-900">{{ $product->name }}</div>
                                <div class="text-xs text-slate-500">{{ $product->category?->name ?? 'Uncategorized' }}</div>
                            </td>
                            <td class="py-3 pr-3">{{ $product->sku }}</td>
                            <td class="py-3 pr-3">{{ number_format($product->selling_price, 2) }}</td>
                            <td class="py-3 pr-3">{{ number_format($product->stock_on_hand ?? 0, 3) }}</td>
                            <td class="py-3 pr-3">{{ $product->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="py-3">
                                <a href="{{ route('business.products.edit', $product) }}" class="font-semibold text-brand-600 hover:text-brand-500">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-slate-500">No products yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $products->links() }}</div>
        </div>
    </div>
</x-business-section>
@endsection
