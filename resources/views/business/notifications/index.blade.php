@extends('layouts.app')

@section('title', 'Notifications — ReapersVentory')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-3xl font-bold text-navy-900">Notifications</h1>
            <p class="mt-1 text-slate-600">
                {{ $unreadCount }} unread · sales, billing, inventory, and subscription alerts
            </p>
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('business.notifications.read-all') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-navy-900 hover:bg-slate-50">
                    Mark all read
                </button>
            </form>
        @endif
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <div class="mt-8 space-y-3">
        @forelse ($notifications as $notification)
            @php
                $data = $notification->data;
                $level = $data['level'] ?? 'info';
                $border = match ($level) {
                    'success' => 'border-l-brand-500',
                    'warning' => 'border-l-amber-500',
                    'danger' => 'border-l-red-500',
                    default => 'border-l-navy-700',
                };
            @endphp
            <a
                href="{{ route('business.notifications.read', $notification->id) }}"
                class="block rounded-xl border border-slate-200 border-l-4 {{ $border }} bg-white p-4 shadow-sm hover:bg-slate-50 {{ $notification->read_at ? 'opacity-70' : '' }}"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-navy-900">{{ $data['title'] ?? 'Notification' }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $data['body'] ?? '' }}</p>
                    </div>
                    @unless ($notification->read_at)
                        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-brand-500"></span>
                    @endunless
                </div>
                <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
            </a>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
                No notifications yet.
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
