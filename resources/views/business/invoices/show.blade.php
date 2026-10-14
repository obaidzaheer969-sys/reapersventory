@extends('layouts.app')

@section('title', $invoice->invoice_number.' — ReapersVentory')

@section('content')
<x-business-section :title="$invoice->invoice_number" subtitle="Invoice details and PDF downloads.">
    <x-slot:actions>
        <a href="{{ route('business.invoices.pdf', $invoice) }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white">Download PDF</a>
        <a href="{{ route('business.invoices.receipt', $invoice) }}" class="rounded-lg bg-navy-900 px-4 py-2 text-sm font-semibold text-white">Receipt PDF</a>
        <a href="{{ route('business.sales.show', $invoice->sale) }}" class="text-sm font-semibold text-navy-800 hover:text-brand-600">View sale</a>
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Status</p><p class="mt-1 font-semibold">{{ $invoice->status->label() }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Issued</p><p class="mt-1 font-semibold">{{ $invoice->issued_at->toFormattedDateString() }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Total</p><p class="mt-1 font-semibold">{{ number_format($invoice->total, 2) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Due</p><p class="mt-1 font-semibold">{{ number_format($invoice->amount_due, 2) }}</p></div>
    </div>
</x-business-section>
@endsection
