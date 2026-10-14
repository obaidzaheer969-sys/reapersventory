@extends('layouts.app')

@section('title', $sale->sale_number.' — ReapersVentory')

@section('content')
<x-business-section :title="$sale->sale_number" :subtitle="'Customer: '.($sale->customer?->name ?? 'Walk-in')">
    <x-slot:actions>
        <a href="{{ route('business.sales.index') }}" class="text-sm font-semibold text-navy-800 hover:text-brand-600">Back</a>
        @if ($sale->invoice)
            <a href="{{ route('business.invoices.pdf', $sale->invoice) }}" class="rounded-lg bg-navy-900 px-4 py-2 text-sm font-semibold text-white">Invoice PDF</a>
            <a href="{{ route('business.invoices.receipt', $sale->invoice) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-navy-900">Receipt PDF</a>
        @endif
        @if ($sale->status->value === 'completed')
            <a href="{{ route('business.returns.create', $sale) }}" class="rounded-lg border border-brand-500 px-4 py-2 text-sm font-semibold text-brand-600">Return items</a>
        @endif
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Status</p><p class="mt-1 font-semibold">{{ $sale->status->label() }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Total</p><p class="mt-1 font-semibold">{{ number_format($sale->total, 2) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Paid</p><p class="mt-1 font-semibold">{{ number_format($sale->amount_paid, 2) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Due</p><p class="mt-1 font-semibold">{{ number_format($sale->amount_due, 2) }}</p></div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="border-b text-slate-500">
                <tr>
                    <th class="py-2 text-left">Product</th>
                    <th class="py-2 text-left">Qty</th>
                    <th class="py-2 text-left">Unit price</th>
                    <th class="py-2 text-left">Unit cost</th>
                    <th class="py-2 text-left">Line total</th>
                    <th class="py-2 text-left">Returned</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr class="border-b border-slate-100">
                        <td class="py-3">{{ $item->product->name }}</td>
                        <td class="py-3">{{ number_format($item->quantity, 3) }}</td>
                        <td class="py-3">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="py-3">{{ number_format($item->unit_cost, 2) }}</td>
                        <td class="py-3">{{ number_format($item->line_total, 2) }}</td>
                        <td class="py-3">{{ number_format($item->returned_quantity, 3) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($sale->amount_due > 0 && $sale->status->value === 'completed')
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-navy-900">Record partial payment</h2>
            <form method="POST" action="{{ route('business.sales.pay', $sale) }}" class="mt-4 grid gap-3 sm:grid-cols-4">
                @csrf
                <input type="number" step="0.01" name="amount" max="{{ $sale->amount_due }}" required placeholder="Amount"
                       class="rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <select name="method" class="rounded-lg px-3 py-2 ring-1 ring-slate-300">
                    <option value="cash">Cash</option>
                    <option value="bank">Bank</option>
                    <option value="easypaisa">Easypaisa</option>
                    <option value="jazzcash">JazzCash</option>
                </select>
                <input type="text" name="reference" placeholder="Reference" class="rounded-lg px-3 py-2 ring-1 ring-slate-300">
                <button class="rounded-lg bg-brand-500 px-4 py-2 font-semibold text-white hover:bg-brand-600">Add payment</button>
            </form>
        </div>
    @endif

    @if ($sale->payments->isNotEmpty())
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-navy-900">Payments</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($sale->payments as $payment)
                    <li class="flex justify-between gap-3 border-b border-slate-100 py-2">
                        <span>{{ $payment->paid_at->toDayDateTimeString() }} · {{ strtoupper($payment->method) }}</span>
                        <span class="font-semibold">{{ number_format($payment->amount, 2) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-business-section>
@endsection
