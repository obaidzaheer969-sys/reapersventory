@extends('layouts.app')

@section('title', 'Expenses — ReapersVentory')

@section('content')
<x-business-section title="Expenses" subtitle="Track operating costs for financial reports.">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-navy-900">Add expense</h2>
            <form method="POST" action="{{ route('business.expenses.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium">Category</label>
                    <input name="category" required class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500" placeholder="Rent, Utilities, ...">
                </div>
                <div>
                    <label class="block text-sm font-medium">Title</label>
                    <input name="title" required class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium">Amount</label>
                    <input type="number" step="0.01" name="amount" required class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium">Date</label>
                    <input type="date" name="expense_date" value="{{ now()->toDateString() }}" required class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium">Payment method</label>
                    <select name="payment_method" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="easypaisa">Easypaisa</option>
                        <option value="jazzcash">JazzCash</option>
                    </select>
                </div>
                <button class="w-full rounded-lg bg-brand-500 px-4 py-2.5 font-semibold text-white hover:bg-brand-600">Save expense</button>
            </form>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b text-slate-500"><tr><th class="py-2 text-left">Date</th><th class="py-2 text-left">Category</th><th class="py-2 text-left">Title</th><th class="py-2 text-left">Amount</th></tr></thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr class="border-b border-slate-100">
                            <td class="py-3">{{ $expense->expense_date->toFormattedDateString() }}</td>
                            <td class="py-3">{{ $expense->category }}</td>
                            <td class="py-3">{{ $expense->title }}</td>
                            <td class="py-3">{{ number_format($expense->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-slate-500">No expenses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $expenses->links() }}</div>
        </div>
    </div>
</x-business-section>
@endsection
