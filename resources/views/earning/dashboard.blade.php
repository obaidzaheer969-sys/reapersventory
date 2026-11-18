@extends('layouts.earning')

@section('title', 'Dashboard — '.config('earning.brand'))

@section('content')
    @php
        $tz = config('earning.timezone');
        $now = \Illuminate\Support\Carbon::now($tz);
        $greeting = $now->hour < 12 ? 'Good morning' : ($now->hour < 17 ? 'Good afternoon' : 'Good evening');
        $availableCount = max(0, $activeTasks->count() - $completedTodayCount);
    @endphp

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">{{ $greeting }}</p>
            <h1 class="mt-1.5 font-display text-3xl font-bold text-white sm:text-4xl">Welcome, {{ $user->name }}</h1>
            <p class="mt-2 text-sm text-slate-400">{{ $now->format('l, d M Y') }} · Complete admin tasks and community campaigns to grow your wallet.</p>
            <p class="mt-1 text-xs text-slate-500">Account: {{ $user->email }} · Task progress is linked to this account. Use this same email on all devices.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tasks.index') }}" class="rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20">Admin Tasks</a>
            <a href="{{ route('campaigns.index') }}" class="rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10">Community</a>
            <a href="{{ route('deposits.index') }}" class="rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10">Deposit</a>
            @if ($withdrawalsEnabled)
                <a href="{{ route('wallet.index') }}" class="rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10">Withdraw</a>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="relative overflow-hidden rounded-2xl border border-mint-400/25 bg-gradient-to-br from-mint-500/20 via-ink-850 to-ink-900 p-5">
            <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-mint-400/20 blur-2xl"></div>
            <p class="text-xs font-semibold uppercase tracking-wider text-mint-200/80">Wallet balance</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ \App\Support\Currency::format($user->wallet_balance) }}</p>
            <p class="mt-2 text-xs text-slate-400">Pending payout: {{ \App\Support\Currency::format($user->pendingPayoutTotal()) }}</p>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Admin tasks today</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ $completedTodayCount }}<span class="text-lg text-slate-500">/{{ $activeTasks->count() }}</span></p>
            <p class="mt-2 text-xs text-slate-400">Completed today · 2 PKR each</p>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Remaining admin tasks</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ $remainingTodayCount }}</p>
            <p class="mt-2 text-xs text-slate-400">Unlock: {{ $nextResetAt->format('h:i A') }} (12:00 midnight)</p>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total earned</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ \App\Support\Currency::format($user->totalEarned()) }}</p>
            <p class="mt-2 text-xs text-slate-400">Withdrawn: {{ \App\Support\Currency::format($user->totalWithdrawn()) }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="rounded-2xl border border-white/10 bg-white/5">
                <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4">
                    <div>
                        <h2 class="text-base font-bold text-white">Admin Tasks</h2>
                        <p class="text-xs text-slate-400">{{ $availableCount }} available · {{ $remainingTodayCount }} remaining · 2 PKR each</p>
                    </div>
                    <a href="{{ route('tasks.index') }}" class="text-xs font-semibold text-mint-300 hover:text-mint-200">View all →</a>
                </div>

                <div class="divide-y divide-white/5">
                    @forelse ($activeTasks->take(6) as $task)
                        @php
                            $isDone = $completedIds->contains($task->id);
                        @endphp
                        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-lg font-black"
                                  style="background: {{ $task->task_type->color() }}22; color: {{ $task->task_type->color() }}">{{ $task->task_type->icon() }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-white">{{ $task->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $task->platform() }} · {{ $task->actionLabel() }} · {{ $task->durationLabel() }}</p>
                            </div>
                            <div class="flex items-center justify-between gap-3 sm:justify-end">
                                <span class="text-sm font-bold text-mint-300">{{ \App\Support\Currency::format($task->reward) }}</span>
                                @if ($isDone)
                                    <span class="rounded-lg border border-mint-400/25 bg-mint-500/10 px-3 py-1.5 text-[11px] font-semibold text-mint-200">
                                        ✓ Complete · Unlock {{ $nextResetAt->format('h:i A') }}
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('tasks.start', $task) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-gradient-to-r from-mint-400 to-mint-500 px-3.5 py-1.5 text-[11px] font-bold text-ink-950">Start</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <p class="text-sm text-slate-400">No admin tasks are available right now. The admin will add new tasks soon.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5">
                <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4">
                    <div>
                        <h2 class="text-base font-bold text-white">Other Users Tasks</h2>
                        <p class="text-xs text-slate-400">{{ $communityTasks->count() }} live campaigns from other users</p>
                    </div>
                    <a href="{{ route('campaigns.index') }}" class="text-xs font-semibold text-mint-300 hover:text-mint-200">View all →</a>
                </div>

                <div class="divide-y divide-white/5">
                    @forelse ($communityTasks as $campaign)
                        @php
                            $isMine = $campaign->user_id === $user->id;
                            $isCampaignDone = $completedCampaignIds->contains($campaign->id);
                        @endphp
                        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-lg font-black"
                                  style="background: {{ $campaign->task_type->color() }}22; color: {{ $campaign->task_type->color() }}">{{ $campaign->task_type->icon() }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-white">{{ $campaign->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $campaign->platform() }} · {{ $campaign->actionLabel() }} · {{ number_format($campaign->remainingQuantity()) }} left</p>
                                <div class="mt-1.5 h-1.5 w-full max-w-xs overflow-hidden rounded-full bg-white/10">
                                    <div class="h-full rounded-full bg-gradient-to-r from-mint-400 to-mint-500" style="width: {{ $campaign->progressPercent() }}%"></div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-3 sm:justify-end">
                                <span class="text-sm font-bold text-mint-300">{{ \App\Support\Currency::format($campaign->reward) }}</span>
                                @if ($isMine)
                                    <a href="{{ route('campaigns.mine') }}" class="rounded-lg border border-gold-400/30 bg-gold-400/10 px-3.5 py-1.5 text-[11px] font-bold text-gold-300">Manage</a>
                                @elseif ($isCampaignDone)
                                    <span class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] font-semibold text-slate-400">✓ Done</span>
                                @else
                                    <a href="{{ route('campaigns.show', $campaign) }}" class="rounded-lg bg-gradient-to-r from-mint-400 to-mint-500 px-3.5 py-1.5 text-[11px] font-bold text-ink-950">Start</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <p class="text-sm text-slate-400">No community campaigns are live right now. You can publish the first campaign.</p>
                            <a href="{{ route('campaigns.create') }}" class="mt-3 inline-flex rounded-lg bg-gradient-to-r from-mint-400 to-mint-500 px-3.5 py-1.5 text-[11px] font-bold text-ink-950">Create campaign</a>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5">
                <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4">
                    <h2 class="text-base font-bold text-white">Recent earnings</h2>
                    <a href="{{ route('earnings.index') }}" class="text-xs font-semibold text-mint-300 hover:text-mint-200">Full history →</a>
                </div>
                <div class="divide-y divide-white/5">
                    @forelse ($recentAttempts as $attempt)
                        <div class="flex items-center gap-3 px-5 py-3.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg text-sm font-black"
                                  style="background: {{ $attempt->task?->task_type?->color() ?? '#64748b' }}22; color: {{ $attempt->task?->task_type?->color() ?? '#94a3b8' }}">{{ $attempt->task?->task_type?->icon() ?? '✓' }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-white">{{ $attempt->task?->title ?? 'Task' }}</p>
                                <p class="text-xs text-slate-400">{{ $attempt->completed_at?->timezone($tz)->format('d M · h:i A') }} · {{ $attempt->watched_seconds }} sec watched</p>
                            </div>
                            <span class="text-sm font-bold text-mint-300">+{{ \App\Support\Currency::format($attempt->reward_amount) }}</span>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-slate-400">No earnings yet. Complete your first task.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-gold-400/20 bg-gold-400/5 p-5">
                <h2 class="text-base font-bold text-white">Daily reset timer</h2>
                <p class="mt-1 text-xs text-slate-400">Tasks automatically unlock at midnight (Pakistan time).</p>
                <p class="mt-4 font-display text-2xl font-bold text-gold-300" data-countdown-to="{{ $nextResetAt->getTimestamp() * 1000 }}">{{ $nextResetAt->diffForHumans() }}</p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-white">My campaigns</h2>
                        <p class="mt-1 text-xs text-slate-400">{{ $myCampaignsCount }} live · {{ \App\Support\Currency::format($totalCampaignSpend) }} spent</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-mint-500/15 text-lg text-mint-300">★</span>
                </div>
                <p class="mt-3 text-xs text-slate-400">Deposited so far: <span class="font-semibold text-white">{{ \App\Support\Currency::format($totalDeposited) }}</span></p>
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('campaigns.create') }}" class="flex-1 rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-3 py-2 text-center text-[11px] font-bold text-ink-950">Create campaign</a>
                    <a href="{{ route('deposits.index') }}" class="flex-1 rounded-xl border border-white/15 bg-white/5 px-3 py-2 text-center text-[11px] font-bold text-white hover:bg-white/10">Add money</a>
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5">
                <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                    <h2 class="text-base font-bold text-white">Announcements</h2>
                    <a href="{{ route('announcements.index') }}" class="text-xs font-semibold text-mint-300 hover:text-mint-200">All →</a>
                </div>
                <div class="divide-y divide-white/5">
                    @forelse ($announcements as $announcement)
                        <div class="px-5 py-4">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($unreadAnnouncementIds->contains($announcement->id))
                                    <span class="rounded-full bg-mint-400/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-mint-300">New</span>
                                @endif
                                @if ($announcement->is_pinned)
                                    <span class="rounded-full bg-gold-400/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-gold-300">Pinned</span>
                                @endif
                            </div>
                            <h3 class="mt-1 text-sm font-semibold text-white">{{ $announcement->title }}</h3>
                            <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-400">{{ $announcement->body }}</p>
                            <p class="mt-2 text-[10px] text-slate-500">{{ ($announcement->published_at ?? $announcement->created_at)?->format('d M Y') }}</p>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-slate-400">No announcements.</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <h2 class="text-base font-bold text-white">Wallet activity</h2>
                <div class="mt-3 space-y-2.5">
                    @forelse ($recentTransactions as $transaction)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="truncate text-slate-300">{{ $transaction->type->label() }}</span>
                            <span class="shrink-0 font-semibold {{ $transaction->isCredit() ? 'text-mint-300' : 'text-rose-300' }}">
                                {{ \App\Support\Currency::signed($transaction->amount, $transaction->isCredit()) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">No transactions.</p>
                    @endforelse
                </div>
                @if ($withdrawalsEnabled)
                    <a href="{{ route('wallet.index') }}" class="mt-4 block text-xs font-semibold text-mint-300 hover:text-mint-200">Open wallet →</a>
                @endif
            </div>
        </div>
    </div>
@endsection
