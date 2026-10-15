@extends('layouts.app')

@section('title', 'Team users — ReapersVentory')

@section('content')
<x-business-section title="Team users" subtitle="People who can sign in and manage this business.">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-navy-900">Add user</h2>
            <p class="mt-1 text-xs text-slate-500">
                Plan limit:
                @if ($usersLimit === null)
                    unlimited users
                @else
                    {{ $userCount }} / {{ $usersLimit }} user{{ $usersLimit === 1 ? '' : 's' }}
                    @if ($usersRemaining !== null)
                        ({{ $usersRemaining }} remaining)
                    @endif
                @endif
            </p>
            <div class="mt-4"><livewire:business.team-user-form /></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b text-slate-500">
                    <tr>
                        <th class="py-2 text-left">Name</th>
                        <th class="py-2 text-left">Email</th>
                        <th class="py-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $member)
                        <tr class="border-b border-slate-100">
                            <td class="py-3">
                                {{ $member->name }}
                                @if ($member->id === auth()->id())
                                    <span class="text-xs text-slate-400">(you)</span>
                                @endif
                            </td>
                            <td class="py-3">{{ $member->email }}</td>
                            <td class="py-3">{{ $member->is_active ? 'Active' : 'Inactive' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-slate-500">No users yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>
</x-business-section>
@endsection
