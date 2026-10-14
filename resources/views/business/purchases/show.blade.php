@extends('layouts.app')

@section('title', $purchase->purchase_number.' — ReapersVentory')

@section('content')
<x-business-section :title="$purchase->purchase_number" :subtitle="'Supplier: '.$purchase->supplier->name">
    <x-slot:actions>
        <a href="{{ route('business.purchases.index') }}" class="text-sm font-semibold text-navy-800 hover:text-brand-600">Back</a>
        @if ($purchase->status->canReceive())
            <form method="POST" action="{{ route('business.purchases.receive', $purchase) }}">
                @csrf
                <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Receive & update stock</button>
            </form>
            <form method="POST" action="{{ route('business.purchases.cancel', $purchase) }}">
                @csrf
                <button class="rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Cancel</button>
            </form>
        @endif
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Status</p><p class="mt-1 font-semibold">{{ $purchase->status->label() }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Warehouse</p><p class="mt-1 font-semibold">{{ $purchase->warehouse->name }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Date</p><p class="mt-1 font-semibold">{{ $purchase->purchase_date->toFormattedDateString() }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Total</p><p class="mt-1 font-semibold">{{ number_format($purchase->total, 2) }}</p></div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="border-b text-slate-500">
                <tr>
                    <th class="py-2 text-left">Product</th>
                    <th class="py-2 text-left">Qty</th>
                    <th class="py-2 text-left">Unit cost</th>
                    <th class="py-2 text-left">Line total</th>
                    <th class="py-2 text-left">Received</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->items as $item)
                    <tr class="border-b border-slate-100">
                        <td class="py-3">{{ $item->product->name }} ({{ $item->product->sku }})</td>
                        <td class="py-3">{{ number_format($item->quantity, 3) }}</td>
                        <td class="py-3">{{ number_format($item->unit_cost, 2) }}</td>
                        <td class="py-3">{{ number_format($item->line_total, 2) }}</td>
                        <td class="py-3">{{ number_format($item->received_quantity, 3) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <dl class="mt-4 ml-auto max-w-xs space-y-1 text-sm">
            <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ number_format($purchase->subtotal, 2) }}</dd></div>
            <div class="flex justify-between"><dt>Tax</dt><dd>{{ number_format($purchase->tax, 2) }}</dd></div>
            <div class="flex justify-between"><dt>Discount</dt><dd>{{ number_format($purchase->discount, 2) }}</dd></div>
            <div class="flex justify-between font-semibold"><dt>Total</dt><dd>{{ number_format($purchase->total, 2) }}</dd></div>
        </dl>
    </div>
</x-business-section>
@endsection
