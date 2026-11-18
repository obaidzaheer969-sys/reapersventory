@extends('layouts.earning')

@section('title', 'Earnings — '.config('earning.brand'))

@section('content')
    @php
        $tz = config('earning.timezone');
    @endphp

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">My earnings</p>
            <h1 class="mt-1.5 font-display text-3xl font-bold text-white sm:text-4xl">Earnings history</h1>
            <p class="mt-2 text-sm text-slate-400">Every task reward, wallet activity, and withdrawal record — all in PKR.</p>
        </div>
        <a href="{{ route('wallet.index') }}" class="rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950">Withdraw earnings</a>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-mint-400/25 bg-gradient-to-br from-mint-500/20 via-ink-850 to-ink-900 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-mint-200/80">Total earned</p>
            <p class="mt-2 font-display text-2xl font-bold text-white">{{ \App\Support\Currency::format($totalEarned) }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $attempts->total() }} completed tasks</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Withdrawn</p>
            <p class="mt-2 font-display text-2xl font-bold text-white">{{ \App\Support\Currency::format($totalWithdrawn) }}</p>
            <p class="mt-1 text-xs text-slate-400">Paid payouts</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pending payout</p>
            <p class="mt-2 font-display text-2xl font-bold text-white">{{ \App\Support\Currency::format($pendingPayout) }}</p>
            <p class="mt-1 text-xs text-slate-400">Admin review in progress</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Time watched</p>
            <p class="mt-2 font-display text-2xl font-bold text-white">{{ number_format(intdiv($watchedSeconds, 60)) }}<span class="text-base text-slate-500"> min</span></p>
            <p class="mt-1 text-xs text-slate-400">{{ number_format($watchedSeconds) }} seconds total</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-5">
        <div class="xl:col-span-3">
            <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                    <div>
                        <h2 class="text-base font-bold text-white">Completed tasks</h2>
                        <p class="text-xs text-slate-400">Task-wise reward history</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-white/10 bg-white/[.03] text-[10px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Task</th>
                                <th class="px-5 py-3 font-semibold">Completed</th>
                                <th class="px-5 py-3 text-right font-semibold">Watched</th>
                                <th class="px-5 py-3 text-right font-semibold">Reward</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($attempts as $attempt)
                                <tr class="transition hover:bg-white/[.03]">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-2.5">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-black"
                                                  style="background: {{ $attempt->task?->task_type?->color() ?? '#64748b' }}22; color: {{ $attempt->task?->task_type?->color() ?? '#94a3b8' }}">{{ $attempt->task?->task_type?->icon() ?? '✓' }}</span>
                                            <div class="min-w-0">
                                                <p class="truncate font-semibold text-white">{{ $attempt->task?->title ?? 'Deleted task' }}</p>
                                                <p class="text-[11px] text-slate-500">{{ $attempt->task?->platform() ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-300">{{ $attempt->completed_at?->timezone($tz)->format('d M Y, h:i A') ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-right text-xs text-slate-300">{{ $attempt->watched_seconds }}s</td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-right font-bold text-mint-300">+{{ \App\Support\Currency::format($attempt->reward_amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-400">No tasks completed yet. <a href="{{ route('tasks.index') }}" class="font-semibold text-mint-300">Start tasks →</a></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($attempts->hasPages())
                    <div class="border-t border-white/10 px-5 py-3">{{ $attempts->links() }}</div>
                @endif
            </div>
        </div>

        <div class="xl:col-span-2">
            <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                <div class="border-b border-white/10 px-5 py-4">
                    <h2 class="text-base font-bold text-white">Wallet ledger</h2>
                    <p class="text-xs text-slate-400">Credits, withdrawals, and refunds</p>
                </div>

                <div class="divide-y divide-white/5">
                    @forelse ($transactions as $transaction)
                        <div class="flex items-start gap-3 px-5 py-3.5">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-black {{ $transaction->isCredit() ? 'bg-mint-500/15 text-mint-300' : 'bg-rose-500/15 text-rose-300' }}">
                                {{ $transaction->isCredit() ? '+' : '−' }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-white">{{ $transaction->type->label() }}</p>
                                <p class="truncate text-[11px] text-slate-500">{{ $transaction->description }}</p>
                                <p class="mt-0.5 text-[10px] text-slate-500">{{ $transaction->created_at?->timezone($tz)->format('d M Y, h:i A') }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-bold {{ $transaction->isCredit() ? 'text-mint-300' : 'text-rose-300' }}">{{ \App\Support\Currency::signed($transaction->amount, $transaction->isCredit()) }}</p>
                                <p class="text-[10px] text-slate-500">Bal {{ \App\Support\Currency::format($transaction->balance_after) }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center text-sm text-slate-400">No wallet activity.</div>
                    @endforelse
                </div>

                @if ($transactions->hasPages())
                    <div class="border-t border-white/10 px-5 py-3">{{ $transactions->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
