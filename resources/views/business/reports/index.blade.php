@extends('layouts.app')

@section('title', 'Reports — ReapersVentory')

@section('content')
<x-business-section title="Financial reports" subtitle="Sales, COGS, expenses, and profit for the selected period.">
    <form method="GET" class="mb-6 flex flex-wrap gap-3">
        <input type="date" name="from" value="{{ $from }}" class="rounded-lg px-3 py-2 ring-1 ring-slate-300">
        <input type="date" name="to" value="{{ $to }}" class="rounded-lg px-3 py-2 ring-1 ring-slate-300">
        <button class="rounded-lg bg-navy-900 px-4 py-2 text-sm font-semibold text-white">Update</button>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Sales total</p><p class="mt-2 text-xl font-bold">{{ number_format($summary['sales_total'], 2) }}</p><p class="text-xs text-slate-500">{{ $summary['sales_count'] }} sales</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Collected</p><p class="mt-2 text-xl font-bold">{{ number_format($summary['payments_collected'], 2) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Amount due</p><p class="mt-2 text-xl font-bold">{{ number_format($summary['amount_due'], 2) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Open invoices</p><p class="mt-2 text-xl font-bold">{{ $summary['open_invoices'] }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">COGS</p><p class="mt-2 text-xl font-bold">{{ number_format($summary['cogs'], 2) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Expenses</p><p class="mt-2 text-xl font-bold">{{ number_format($summary['expenses_total'], 2) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Gross profit</p><p class="mt-2 text-xl font-bold text-brand-600">{{ number_format($summary['gross_profit'], 2) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Net profit</p><p class="mt-2 text-xl font-bold text-navy-900">{{ number_format($summary['net_profit'], 2) }}</p></div>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="font-semibold text-navy-900">Top products</h2>
        <table class="mt-3 min-w-full text-sm">
            <thead class="border-b text-slate-500"><tr><th class="py-2 text-left">Product</th><th class="py-2 text-left">Qty</th><th class="py-2 text-left">Revenue</th></tr></thead>
            <tbody>
                @forelse ($summary['top_products'] as $row)
                    <tr class="border-b border-slate-100">
                        <td class="py-3">{{ $row['name'] }}</td>
                        <td class="py-3">{{ number_format($row['qty'], 3) }}</td>
                        <td class="py-3">{{ number_format($row['revenue'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-6 text-slate-500">No sales in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-business-section>
@endsection
