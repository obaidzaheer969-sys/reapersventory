@extends('layouts.app')

@section('title', 'New purchase — ReapersVentory')

@section('content')
<x-business-section title="New purchase" subtitle="Totals are stored as historical values and will not change after receipt.">
    <x-slot:actions>
        <a href="{{ route('business.purchases.index') }}" class="text-sm font-semibold text-navy-800 hover:text-brand-600">Back</a>
    </x-slot:actions>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <livewire:business.purchase-form />
    </div>
</x-business-section>
@endsection
