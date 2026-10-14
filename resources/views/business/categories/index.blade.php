@extends('layouts.app')

@section('title', 'Categories — ReapersVentory')

@section('content')
<x-business-section title="Categories" subtitle="Organize products into categories.">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-navy-900">Add category</h2>
            <div class="mt-4"><livewire:business.category-form /></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b text-slate-500"><tr><th class="py-2 text-left">Name</th><th class="py-2 text-left">Products</th><th class="py-2 text-left">Status</th></tr></thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr class="border-b border-slate-100">
                            <td class="py-3">{{ $category->name }}</td>
                            <td class="py-3">{{ $category->products_count }}</td>
                            <td class="py-3">{{ $category->is_active ? 'Active' : 'Inactive' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-slate-500">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $categories->links() }}</div>
        </div>
    </div>
</x-business-section>
@endsection
