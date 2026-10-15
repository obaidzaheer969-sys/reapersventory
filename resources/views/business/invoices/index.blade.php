@extends('layouts.app')

@section('title', 'Invoices — ReapersVentory')

@section('content')
<x-business-section title="Invoices" subtitle="Billing documents generated from completed sales.">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="border-b text-slate-500">
                <tr>
                    <th class="py-2 text-left">Invoice</th>
                    <th class="py-2 text-left">Customer</th>
                    <th class="py-2 text-left">Total</th>
                    <th class="py-2 text-left">Due</th>
                    <th class="py-2 text-left">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr class="border-b border-slate-100">
                        <td class="py-3">
                            <a href="{{ route('business.invoices.show', $invoice) }}" class="font-semibold text-brand-600 hover:text-brand-500">{{ $invoice->invoice_number }}</a>
                        </td>
                        <td class="py-3">{{ $invoice->sale?->customerLabel() ?? ($invoice->customer?->name ?? 'Walk-in customer') }}</td>
                        <td class="py-3">{{ number_format($invoice->total, 2) }}</td>
                        <td class="py-3">{{ number_format($invoice->amount_due, 2) }}</td>
                        <td class="py-3">{{ $invoice->status->label() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-slate-500">No invoices yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $invoices->links() }}</div>
    </div>
</x-business-section>
@endsection
