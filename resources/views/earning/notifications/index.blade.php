@extends('layouts.earning')

@section('title', 'Notifications — '.config('earning.brand'))

@section('content')
    @php
    $tz = config('earning.timezone');
@endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">Inbox</p>
            <h1 class="mt-1.5 font-display text-3xl font-bold text-white sm:text-4xl">Notifications</h1>
            <p class="mt-2 text-sm text-slate-400">{{ $unreadCount }} unread · payout updates and announcements.</p>
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10">Mark all as read</button>
            </form>
        @endif
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($notifications as $notification)
            @php
                $data = $notification->data;
                $isUnread = is_null($notification->read_at);
                $level = $data['level'] ?? $data['status'] ?? 'info';
                $levelClasses = match ($level) {
                    'success' => 'border-mint-400/30 bg-mint-500/10',
                    'danger' => 'border-rose-400/30 bg-rose-500/10',
                    'warning' => 'border-gold-400/30 bg-gold-400/10',
                    default => 'border-white/10 bg-white/5',
                };
                $icon = match ($level) {
                    'success' => '✓',
                    'danger' => '!',
                    'warning' => '⏳',
                    default => '🔔',
                };
                $title = $data['title'] ?? 'Notification';
                $body = $data['body'] ?? '';
            @endphp

            <div class="rounded-2xl border p-4 {{ $levelClasses }} {{ $isUnread ? '' : 'opacity-70' }}">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-base font-bold text-white">{{ $icon }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-bold text-white">{{ $title }}</h2>
                            @if ($isUnread)
                                <span class="rounded-full bg-mint-400/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-mint-200">New</span>
                            @endif
                        </div>
                        @if (filled($body))
                            <p class="mt-1 text-sm leading-relaxed text-slate-300">{{ $body }}</p>
                        @endif
                        <p class="mt-1.5 text-[11px] text-slate-500">{{ $notification->created_at?->timezone($tz)->format('d M Y, h:i A') }}</p>

                        <div class="mt-2.5 flex flex-wrap items-center gap-2">
                            @if (filled($data['url'] ?? null))
                                <a href="{{ $data['url'] }}" class="rounded-lg bg-white/10 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-white/15">Open →</a>
                            @endif
                            @if ($isUnread)
                                <a href="{{ route('notifications.read', $notification->id) }}" class="rounded-lg border border-white/15 px-3 py-1.5 text-[11px] font-semibold text-slate-300 hover:bg-white/10">Mark read</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-white/10 bg-white/5 px-6 py-16 text-center">
                <p class="text-4xl">🔔</p>
                <h2 class="mt-3 text-lg font-bold text-white">No notifications</h2>
                <p class="mt-1 text-sm text-slate-400">Payout status and new announcements will appear here.</p>
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
@endsection
