@extends('layouts.app')

@section('title', 'Business profile — ReapersVentory')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
    <h1 class="font-display text-3xl font-bold text-navy-900">Business profile</h1>
    <p class="mt-1 text-slate-600">Update details for {{ $business->name }}. Changes are limited to your own business.</p>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <livewire:business.edit-profile />
    </div>
</div>
@endsection
