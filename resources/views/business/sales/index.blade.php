@extends('layouts.app')

@section('title', 'Sales — ReapersVentory')

@section('content')
<x-business-section title="Sales" subtitle="Create sales, collect partial payments, and generate invoices.">
    <x-slot:actions>
        <a href="{{ route('business.sales.create') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">New sale</a>
    </x-slot:actions>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="border-b text-slate-500">
                <tr>
                    <th class="py-2 text-left">Sale</th>
                    <th class="py-2 text-left">Customer</th>
                    <th class="py-2 text-left">Total</th>
                    <th class="py-2 text-left">Due</th>
                    <th class="py-2 text-left">Invoice</th>
                    <th class="py-2 text-left">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr class="border-b border-slate-100">
                        <td class="py-3">
                            <a href="{{ route('business.sales.show', $sale) }}" class="font-semibold text-brand-600 hover:text-brand-500">{{ $sale->sale_number }}</a>
                            <div class="text-xs text-slate-500">{{ $sale->sale_date->toFormattedDateString() }}</div>
                        </td>
                        <td class="py-3">{{ $sale->customerLabel() }}</td>
                        <td class="py-3">{{ number_format($sale->total, 2) }}</td>
                        <td class="py-3">{{ number_format($sale->amount_due, 2) }}</td>
                        <td class="py-3">
                            @if ($sale->invoice)
                                <a href="{{ route('business.invoices.show', $sale->invoice) }}" class="text-brand-600 hover:text-brand-500">{{ $sale->invoice->invoice_number }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="py-3">{{ $sale->status->label() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-slate-500">No sales yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $sales->links() }}</div>
    </div>
</x-business-section>
@endsection
