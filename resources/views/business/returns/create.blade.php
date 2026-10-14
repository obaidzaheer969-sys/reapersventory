@extends('layouts.app')

@section('title', 'Return '.$sale->sale_number.' — ReapersVentory')

@section('content')
<x-business-section title="Return items" :subtitle="'Sale '.$sale->sale_number">
    <x-slot:actions>
        <a href="{{ route('business.sales.show', $sale) }}" class="text-sm font-semibold text-navy-800 hover:text-brand-600">Back</a>
    </x-slot:actions>

    <form method="POST" action="{{ route('business.returns.store', $sale) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium">Return date</label>
                <input type="date" name="return_date" value="{{ old('return_date', now()->toDateString()) }}" required
                       class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-sm font-medium">Refund amount (optional)</label>
                <input type="number" step="0.01" name="refund_amount" value="{{ old('refund_amount') }}"
                       class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
        </div>

        <div class="space-y-3">
            <h3 class="font-semibold text-navy-900">Items to return</h3>
            @foreach ($sale->items as $index => $item)
                @php $returnable = (float) $item->returnableQuantity(); @endphp
                @if ($returnable > 0)
                    <div class="grid gap-3 rounded-xl border border-slate-200 p-3 sm:grid-cols-3">
                        <input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item->id }}">
                        <div class="sm:col-span-2">
                            <p class="font-medium">{{ $item->product->name }}</p>
                            <p class="text-xs text-slate-500">Returnable: {{ number_format($returnable, 3) }} @ {{ number_format($item->unit_price, 2) }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium">Qty</label>
                            <input type="number" step="0.001" max="{{ $returnable }}" name="items[{{ $index }}][quantity]" value="0"
                                   class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300">
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <div>
            <label class="block text-sm font-medium">Notes</label>
            <textarea name="notes" rows="2" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300"></textarea>
        </div>

        <button class="rounded-lg bg-brand-500 px-4 py-2.5 font-semibold text-white hover:bg-brand-600">Process return</button>
    </form>
</x-business-section>
@endsection
