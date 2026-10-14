@extends('layouts.app')

@section('title', 'Returns — ReapersVentory')

@section('content')
<x-business-section title="Returns" subtitle="Completed returns that restored inventory.">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="border-b text-slate-500">
                <tr>
                    <th class="py-2 text-left">Return</th>
                    <th class="py-2 text-left">Sale</th>
                    <th class="py-2 text-left">Refund</th>
                    <th class="py-2 text-left">Date</th>
                    <th class="py-2 text-left">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($returns as $return)
                    <tr class="border-b border-slate-100">
                        <td class="py-3">{{ $return->return_number }}</td>
                        <td class="py-3">
                            <a href="{{ route('business.sales.show', $return->sale) }}" class="text-brand-600 hover:text-brand-500">{{ $return->sale->sale_number }}</a>
                        </td>
                        <td class="py-3">{{ number_format($return->refund_amount, 2) }}</td>
                        <td class="py-3">{{ $return->return_date->toFormattedDateString() }}</td>
                        <td class="py-3">{{ $return->status->label() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-slate-500">No returns yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $returns->links() }}</div>
    </div>
</x-business-section>
@endsection
