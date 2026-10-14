@extends('layouts.app')

@section('title', 'New sale — ReapersVentory')

@section('content')
<x-business-section title="New sale" subtitle="Stock is deducted and an invoice is created when the sale is completed.">
    <x-slot:actions>
        <a href="{{ route('business.sales.index') }}" class="text-sm font-semibold text-navy-800 hover:text-brand-600">Back</a>
    </x-slot:actions>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <livewire:business.sale-form />
    </div>
</x-business-section>
@endsection
