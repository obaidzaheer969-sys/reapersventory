@extends('layouts.app')

@section('title', 'Customers — ReapersVentory')

@section('content')
<x-business-section title="Customers" subtitle="People and businesses you sell to.">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-navy-900">Add customer</h2>
            <div class="mt-4"><livewire:business.customer-form /></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b text-slate-500"><tr><th class="py-2 text-left">Name</th><th class="py-2 text-left">Email</th><th class="py-2 text-left">Phone</th></tr></thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr class="border-b border-slate-100">
                            <td class="py-3">{{ $customer->name }}</td>
                            <td class="py-3">{{ $customer->email ?? '—' }}</td>
                            <td class="py-3">{{ $customer->phone ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-slate-500">No customers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $customers->links() }}</div>
        </div>
    </div>
</x-business-section>
@endsection
