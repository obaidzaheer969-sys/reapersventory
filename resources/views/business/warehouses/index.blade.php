@extends('layouts.app')

@section('title', 'Warehouses — ReapersVentory')

@section('content')
<x-business-section title="Warehouses" subtitle="Stock locations for purchases and sales.">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-navy-900">Add warehouse</h2>
            <div class="mt-4"><livewire:business.warehouse-form /></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b text-slate-500"><tr><th class="py-2 text-left">Name</th><th class="py-2 text-left">Code</th><th class="py-2 text-left">Default</th><th class="py-2 text-left">Status</th></tr></thead>
                <tbody>
                    @forelse ($warehouses as $warehouse)
                        <tr class="border-b border-slate-100">
                            <td class="py-3">{{ $warehouse->name }}</td>
                            <td class="py-3">{{ $warehouse->code }}</td>
                            <td class="py-3">{{ $warehouse->is_default ? 'Yes' : 'No' }}</td>
                            <td class="py-3">{{ $warehouse->is_active ? 'Active' : 'Inactive' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-slate-500">No warehouses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $warehouses->links() }}</div>
        </div>
    </div>
</x-business-section>
@endsection
