@extends('layouts.app')

@section('title', 'Edit product — ReapersVentory')

@section('content')
<x-business-section title="Edit product" subtitle="Update SKU, pricing, and catalog details.">
    <x-slot:actions>
        <a href="{{ route('business.products.index') }}" class="rounded-lg bg-navy-900 px-3 py-2 text-sm font-semibold text-white hover:bg-navy-800">Back to products</a>
    </x-slot:actions>

    <div class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <livewire:business.product-form :product="$product" :key="'product-'.$product->id" />
    </div>
</x-business-section>
@endsection
