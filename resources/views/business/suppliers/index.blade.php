@extends('layouts.app')

@section('title', 'Suppliers — ReapersVentory')

@section('content')
<x-business-section title="Suppliers" subtitle="Vendors used for purchase orders.">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-navy-900">Add supplier</h2>
            <div class="mt-4"><livewire:business.supplier-form /></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b text-slate-500"><tr><th class="py-2 text-left">Name</th><th class="py-2 text-left">Email</th><th class="py-2 text-left">Phone</th></tr></thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr class="border-b border-slate-100">
                            <td class="py-3">{{ $supplier->name }}</td>
                            <td class="py-3">{{ $supplier->email ?? '—' }}</td>
                            <td class="py-3">{{ $supplier->phone ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-slate-500">No suppliers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $suppliers->links() }}</div>
        </div>
    </div>
</x-business-section>
@endsection
