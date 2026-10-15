@extends('layouts.app')

@section('title', 'Billing — ReapersVentory')

@section('content')
<x-business-section title="Billing counter" subtitle="Scan items, complete sale, then print on a small thermal roll printer.">
    <x-slot:actions>
        <a href="{{ route('business.sales.index') }}" class="text-sm font-semibold text-navy-800 hover:text-brand-600">All sales</a>
    </x-slot:actions>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <livewire:business.sale-form />
    </div>
</x-business-section>
@endsection
