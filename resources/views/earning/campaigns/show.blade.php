@extends('layouts.earning')

@section('title', $campaign->title.' — '.config('earning.brand'))

@section('content')
    @php
        $tz = config('earning.timezone');
        $status = $campaign->campaign_status;
        $isLive = $campaign->isLiveCampaign();
        $cancellable = $isOwner && in_array($status, [\App\Enums\CampaignStatus::Pending, \App\Enums\CampaignStatus::Active], true);
        $backRoute = $isOwner ? route('campaigns.mine') : route('campaigns.index');
    @endphp

    <a href="{{ $backRoute }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white">← {{ $isOwner ? 'My campaigns' : 'Community tasks' }}</a>

    <div class="mt-4 overflow-hidden rounded-3xl border border-white/10 bg-white/5">
        <div class="border-b border-white/10 bg-gradient-to-r from-ink-850 via-ink-900 to-ink-850 px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl font-black"
                          style="background: {{ $campaign->task_type->color() }}22; color: {{ $campaign->task_type->color() }}">{{ $campaign->task_type->icon() }}</span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">{{ $campaign->platform() }} · {{ $campaign->task_type->label() }}</p>
                        <h1 class="mt-1 font-display text-2xl font-bold leading-tight text-white sm:text-3xl">{{ $campaign->title }}</h1>
                        <p class="mt-1.5 text-xs text-slate-400">
                            By {{ $isOwner ? 'you' : ($campaign->owner?->name ?? 'User') }}
                            · {{ $campaign->created_at?->timezone($tz)->format('d M Y, h:i A') }}
                        </p>
                    </div>
                </div>

                <span class="shrink-0 self-start rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wider"
                      style="border-color: {{ $status->hex() }}55; background: {{ $status->hex() }}18; color: {{ $status->hex() }}">
                    {{ $status->label() }}
                </span>
            </div>
        </div>

        <div class="grid gap-4 border-b border-white/10 px-5 py-5 sm:grid-cols-2 xl:grid-cols-4 sm:px-6">
            <div class="rounded-xl border border-white/10 bg-ink-900/50 p-4">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Completed / target</p>
                <p class="mt-1 font-display text-2xl font-bold text-white">{{ number_format($campaign->completed_quantity) }}<span class="text-base text-slate-500">/{{ number_format($campaign->target_quantity) }}</span></p>
            </div>
            <div class="rounded-xl border border-white/10 bg-ink-900/50 p-4">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Reward per action</p>
                <p class="mt-1 font-display text-2xl font-bold text-mint-300">{{ \App\Support\Currency::format($campaign->reward) }}</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-ink-900/50 p-4">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Duration</p>
                <p class="mt-1 font-display text-2xl font-bold text-white">{{ $campaign->durationLabel() }}</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-ink-900/50 p-4">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Remaining quantity</p>
                <p class="mt-1 font-display text-2xl font-bold text-white">{{ number_format($campaign->remainingQuantity()) }}</p>
            </div>
        </div>

        <div class="px-5 py-5 sm:px-6">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-400">
                <span>{{ number_format($campaign->completed_quantity) }} of {{ number_format($campaign->target_quantity) }} actions completed</span>
                <span>{{ $campaign->progressPercent() }}%</span>
            </div>
            <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-white/10">
                <div class="h-full rounded-full bg-gradient-to-r from-mint-400 to-mint-500" style="width: {{ $campaign->progressPercent() }}%"></div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <h2 class="text-base font-bold text-white">Task details</h2>

                @if (filled($campaign->instructions))
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-300">{{ $campaign->instructions }}</p>
                @endif

                <dl class="mt-4 space-y-2.5 text-sm">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Target URL</dt>
                        <dd class="min-w-0 sm:max-w-[70%]">
                            <a href="{{ $campaign->target_url }}" target="_blank" rel="noopener noreferrer"
                               class="block truncate text-xs font-semibold text-mint-300 hover:text-mint-200">{{ $campaign->target_url }}</a>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Action</dt>
                        <dd class="text-xs font-semibold text-slate-200">{{ $campaign->actionLabel() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Duration</dt>
                        <dd class="text-xs font-semibold text-slate-200">{{ $campaign->durationLabel() }}</dd>
                    </div>
                    @if ($campaign->approved_at)
                        <div class="flex items-center justify-between">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Live since</dt>
                            <dd class="text-xs font-semibold text-slate-200">{{ $campaign->approved_at->timezone($tz)->format('d M Y, h:i A') }}</dd>
                        </div>
                    @endif
                    @if ($isOwner && filled($campaign->admin_notes))
                        <div class="rounded-xl border border-rose-400/25 bg-rose-500/10 px-3.5 py-3">
                            <dt class="text-[10px] font-bold uppercase tracking-wider text-rose-300">Admin notes</dt>
                            <dd class="mt-1 text-xs leading-relaxed text-rose-200">{{ $campaign->admin_notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if ($isOwner)
                <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                    <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4">
                        <div>
                            <h2 class="text-base font-bold text-white">Completed by users</h2>
                            <p class="text-xs text-slate-400">See which user completed each action and when.</p>
                        </div>
                        <span class="rounded-lg bg-mint-500/15 px-2.5 py-1 text-xs font-bold text-mint-300">{{ number_format($campaign->completed_quantity) }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="border-b border-white/10 bg-white/[.03] text-[10px] uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-5 py-3 font-semibold">Earner</th>
                                    <th class="px-5 py-3 font-semibold">Reward</th>
                                    <th class="px-5 py-3 font-semibold">Completed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @forelse ($attempts as $attempt)
                                    <tr class="transition hover:bg-white/[.03]">
                                        <td class="px-5 py-3.5">
                                            <p class="text-sm font-semibold text-white">{{ $attempt->user?->name ?? 'User #'.$attempt->user_id }}</p>
                                            <p class="text-[11px] text-slate-500">{{ $attempt->user?->email }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3.5 text-xs font-bold text-mint-300">{{ \App\Support\Currency::format($attempt->reward_amount) }}</td>
                                        <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-400">{{ $attempt->completed_at?->timezone($tz)->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-5 py-10 text-center text-sm text-slate-400">No user has completed this campaign yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($attempts->hasPages())
                        <div class="border-t border-white/10 px-5 py-3">{{ $attempts->links() }}</div>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-4">
            @if ($isOwner)
                <div class="rounded-2xl border border-gold-400/25 bg-gold-400/5 p-5">
                    <h2 class="text-sm font-bold text-white">Budget</h2>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Total paid</span>
                            <span class="font-bold text-white">{{ \App\Support\Currency::format($campaign->total_cost) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Spent on rewards</span>
                            <span class="font-semibold text-rose-300">{{ \App\Support\Currency::format($campaign->budgetSpent()) }}</span>
                        </div>
                        @if ($campaign->platformFee() > 0)
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Admin fee</span>
                                <span class="font-semibold text-slate-300">{{ \App\Support\Currency::format($campaign->platformFee()) }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between border-t border-white/10 pt-2">
                            <span class="text-slate-400">Remaining</span>
                            <span class="font-semibold text-mint-300">{{ \App\Support\Currency::format($campaign->budgetRemaining()) }}</span>
                        </div>
                    </div>

                    @if ($cancellable)
                        <form method="POST" action="{{ route('campaigns.cancel', $campaign) }}" class="mt-4"
                              onsubmit="return confirm('Cancel this campaign? The unused budget will return to your wallet.')">
                            @csrf
                            <button type="submit" class="w-full rounded-xl border border-rose-400/30 bg-rose-500/10 px-4 py-2.5 text-sm font-bold text-rose-300 hover:bg-rose-500/20">
                                Cancel campaign &amp; refund
                            </button>
                        </form>
                    @endif
                </div>
            @else
                <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                    <h2 class="text-sm font-bold text-white">Earn from this campaign</h2>

                    @if (! $isLive)
                        <p class="mt-2 rounded-xl border border-white/10 bg-ink-900/50 px-3.5 py-3 text-xs text-slate-400">
                            This campaign is not available right now. Status: {{ $status->label() }}.
                        </p>
                    @elseif ($alreadyCompleted)
                        <p class="mt-2 rounded-xl border border-mint-400/25 bg-mint-500/10 px-3.5 py-3 text-xs text-mint-200">
                            ✓ You have already completed this campaign. Each campaign can be completed once per user.
                        </p>
                    @elseif ($inProgress)
                        @if ($inProgress->canClaim($campaign))
                            <p class="mt-2 text-xs text-slate-400">The timer and watch time are both complete — claim your reward.</p>
                            <form method="POST" action="{{ route('tasks.complete', $campaign) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-gold-400 to-gold-500 px-4 py-2.5 text-sm font-bold text-ink-950">
                                    Claim {{ \App\Support\Currency::format($campaign->reward) }}
                                </button>
                            </form>
                        @else
                            <p class="mt-2 text-xs text-slate-400">
                                @if ($inProgress->isReadyToClaim())
                                    The timer finished — complete the remaining video watch time.
                                @else
                                    Your task is already running. {{ $inProgress->remainingSeconds() }}s remaining.
                                @endif
                            </p>
                            <a href="{{ route('tasks.run', $campaign) }}" class="mt-3 block rounded-xl border border-gold-400/40 bg-gold-400/10 px-4 py-2.5 text-center text-sm font-bold text-gold-300">Resume task</a>
                        @endif
                    @else
                        <p class="mt-2 text-xs text-slate-400">
                            {{ $campaign->actionLabel() }} and earn {{ \App\Support\Currency::format($campaign->reward) }}.
                            {{ $campaign->hasEmbeddedPlayer() ? 'The video plays in the official player on this locked page.' : 'The real page opens in a new tab.' }}
                        </p>
                        <form method="POST" action="{{ route('tasks.start', $campaign) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20">
                                {{ $campaign->hasEmbeddedPlayer() ? 'Watch video →' : 'Start task → real page' }}
                            </button>
                        </form>
                    @endif
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-5 text-xs leading-relaxed text-slate-400">
                    <h2 class="text-sm font-bold text-white">How it works</h2>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @if ($campaign->hasEmbeddedPlayer())
                            <li>Start the task and play the video in the official player on this locked page.</li>
                            <li>Do not close this tab until the timer finishes, and keep the video playing for the required seconds.</li>
                            <li>The reward is added to your wallet once both the timer and watch time are complete.</li>
                        @else
                            <li>Start the task and open the real page in a new tab.</li>
                            <li>Do not close the real page tab until the timer finishes, otherwise the task will fail.</li>
                            <li>The reward is added to your wallet when the timer finishes.</li>
                        @endif
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endsection
