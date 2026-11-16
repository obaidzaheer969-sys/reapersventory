@php
    $stats = [
        ['label' => 'Wallet balance', 'value' => \App\Support\Currency::format($user->wallet_balance)],
        ['label' => 'Total earned', 'value' => \App\Support\Currency::format($user->totalEarned())],
        ['label' => 'Total withdrawn', 'value' => \App\Support\Currency::format($user->totalWithdrawn())],
        ['label' => 'Pending payout', 'value' => \App\Support\Currency::format($user->pendingPayoutTotal())],
        ['label' => 'Tasks completed', 'value' => number_format($user->completedTaskAttempts()->count())],
        ['label' => 'Seconds watched', 'value' => number_format($user->totalWatchedSeconds())],
        ['label' => 'Campaigns', 'value' => number_format($user->campaigns()->count())],
        ['label' => 'Campaign spend', 'value' => \App\Support\Currency::format($user->totalCampaignSpend())],
        ['label' => 'Total deposited', 'value' => \App\Support\Currency::format($user->totalDeposited())],
    ];

    $recentAttempts = $user->completedTaskAttempts()->with('task')->latest('completed_at')->limit(5)->get();
    $recentCampaigns = $user->campaigns()->latest()->limit(5)->get();
    $recentDeposits = $user->deposits()->latest()->limit(5)->get();
@endphp

<div style="display: grid; gap: 1rem;">
    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem;">
        @foreach ($stats as $stat)
            <div style="border: 1px solid var(--gray-200, #e5e7eb); border-radius: .75rem; padding: .75rem .9rem;">
                <p style="margin: 0; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-500, #6b7280);">{{ $stat['label'] }}</p>
                <p style="margin: .25rem 0 0; font-size: 1.05rem; font-weight: 700; color: var(--gray-950, #030712);">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div>
        <p style="margin: 0 0 .5rem; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-500, #6b7280);">Saved wallets</p>
        @forelse ($user->payoutAccounts()->orderByDesc('is_default')->get() as $account)
            <div style="display: flex; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--gray-100, #f3f4f6); padding: .45rem 0; font-size: .85rem;">
                <span style="color: var(--gray-950, #030712);">{{ $account->provider->label() }} — {{ $account->account_number }}</span>
                <span style="color: var(--gray-500, #6b7280);">{{ $account->account_name }}{{ $account->is_default ? ' · default' : '' }}</span>
            </div>
        @empty
            <p style="margin: 0; font-size: .85rem; color: var(--gray-500, #6b7280);">No wallet account saved.</p>
        @endforelse
    </div>

    <div>
        <p style="margin: 0 0 .5rem; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-500, #6b7280);">Recent campaigns</p>
        @forelse ($recentCampaigns as $campaign)
            <div style="display: flex; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--gray-100, #f3f4f6); padding: .45rem 0; font-size: .85rem;">
                <span style="color: var(--gray-950, #030712);">{{ $campaign->title }}</span>
                <span style="color: var(--gray-500, #6b7280);">{{ number_format($campaign->completed_quantity) }}/{{ number_format($campaign->target_quantity) }} · {{ $campaign->campaign_status->label() }}</span>
            </div>
        @empty
            <p style="margin: 0; font-size: .85rem; color: var(--gray-500, #6b7280);">No campaign created yet.</p>
        @endforelse
    </div>

    <div>
        <p style="margin: 0 0 .5rem; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-500, #6b7280);">Recent deposits</p>
        @forelse ($recentDeposits as $deposit)
            <div style="display: flex; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--gray-100, #f3f4f6); padding: .45rem 0; font-size: .85rem;">
                <span style="font-family: monospace; color: var(--gray-950, #030712);">{{ $deposit->order_id }}</span>
                <span style="color: var(--gray-500, #6b7280);">{{ \App\Support\Currency::format($deposit->credit_amount) }} · {{ $deposit->status->label() }}</span>
            </div>
        @empty
            <p style="margin: 0; font-size: .85rem; color: var(--gray-500, #6b7280);">No deposit yet.</p>
        @endforelse
    </div>

    <div>
        <p style="margin: 0 0 .5rem; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-500, #6b7280);">Recent completed tasks</p>
        @forelse ($recentAttempts as $attempt)
            <div style="display: flex; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--gray-100, #f3f4f6); padding: .45rem 0; font-size: .85rem;">
                <span style="color: var(--gray-950, #030712);">{{ $attempt->task?->title ?? 'Deleted task' }}</span>
                <span style="color: var(--gray-500, #6b7280);">{{ $attempt->watched_seconds }}s · {{ \App\Support\Currency::format($attempt->reward_amount) }}</span>
            </div>
        @empty
            <p style="margin: 0; font-size: .85rem; color: var(--gray-500, #6b7280);">No tasks completed yet.</p>
        @endforelse
    </div>
</div>
