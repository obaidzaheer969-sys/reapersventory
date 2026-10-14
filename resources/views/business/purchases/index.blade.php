@extends('layouts.app')

@section('title', 'Purchases — ReapersVentory')

@section('content')
<x-business-section title="Purchases" subtitle="Create purchase orders and receive stock into warehouses.">
    <x-slot:actions>
        <a href="{{ route('business.purchases.create') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">New purchase</a>
    </x-slot:actions>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="border-b text-slate-500">
                <tr>
                    <th class="py-2 text-left">Number</th>
                    <th class="py-2 text-left">Supplier</th>
                    <th class="py-2 text-left">Warehouse</th>
                    <th class="py-2 text-left">Total</th>
                    <th class="py-2 text-left">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchases as $purchase)
                    <tr class="border-b border-slate-100">
                        <td class="py-3">
                            <a href="{{ route('business.purchases.show', $purchase) }}" class="font-semibold text-brand-600 hover:text-brand-500">{{ $purchase->purchase_number }}</a>
                        </td>
                        <td class="py-3">{{ $purchase->supplier->name }}</td>
                        <td class="py-3">{{ $purchase->warehouse->name }}</td>
                        <td class="py-3">{{ number_format($purchase->total, 2) }}</td>
                        <td class="py-3">{{ $purchase->status->label() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-slate-500">No purchases yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $purchases->links() }}</div>
    </div>
</x-business-section>
@endsection
