@extends('layouts.earning')

@section('title', 'My campaigns — '.config('earning.brand'))

@section('content')
    @php
        $tz = config('earning.timezone');
    @endphp

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">My campaigns</p>
            <h1 class="mt-1.5 font-display text-3xl font-bold text-white sm:text-4xl">Campaign dashboard</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">
                Aap ki publish ki hui campaigns, unki quantity, spending aur completion status — sab yahan. Nayi campaign payment ke foran baad live ho jati hai.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('campaigns.create') }}" class="rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20">Create campaign</a>
            <a href="{{ route('campaigns.index') }}" class="rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10">Community tasks</a>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="relative overflow-hidden rounded-2xl border border-mint-400/25 bg-gradient-to-br from-mint-500/20 via-ink-850 to-ink-900 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-mint-200/80">Wallet balance</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ \App\Support\Currency::format($walletBalance) }}</p>
            <p class="mt-2 text-xs text-slate-400">New campaigns isi balance se pay hoti hain</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total campaign spend</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ \App\Support\Currency::format($totalSpend) }}</p>
            <p class="mt-2 text-xs text-slate-400">2 PKR earner + 1 PKR admin fee per action</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Live campaigns</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ $liveCount }}</p>
            <p class="mt-2 text-xs text-slate-400">Other users can complete</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Delivered actions</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ number_format($deliveredTotal) }}</p>
            <p class="mt-2 text-xs text-slate-400">Total completed quantity</p>
        </div>
    </div>

    @if ($campaigns->isEmpty())
        <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 px-6 py-16 text-center">
            <p class="text-4xl">📣</p>
            <h2 class="mt-3 text-lg font-bold text-white">Abhi koi campaign nahi</h2>
            <p class="mt-1 text-sm text-slate-400">Apni pehli campaign banayen — YouTube, Facebook, Instagram ya WhatsApp ke liye.</p>
            <a href="{{ route('campaigns.create') }}" class="mt-4 inline-flex rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950">Create campaign</a>
        </div>
    @else
        <div class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-white/5">
            <div class="border-b border-white/10 px-5 py-4">
                <h2 class="text-base font-bold text-white">All campaigns</h2>
                <p class="text-xs text-slate-400">Status, quantity, spending aur refunds</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-white/10 bg-white/[.03] text-[10px] uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Campaign</th>
                            <th class="px-5 py-3 font-semibold">Quantity</th>
                            <th class="px-5 py-3 text-right font-semibold">Reward</th>
                            <th class="px-5 py-3 text-right font-semibold">Budget</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Date</th>
                            <th class="px-5 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($campaigns as $campaign)
                            @php
                                $status = $campaign->campaign_status;
                                $cancellable = in_array($status, [\App\Enums\CampaignStatus::Pending, \App\Enums\CampaignStatus::Active], true);
                            @endphp
                            <tr class="transition hover:bg-white/[.03]">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-black"
                                              style="background: {{ $campaign->task_type->color() }}22; color: {{ $campaign->task_type->color() }}">{{ $campaign->task_type->icon() }}</span>
                                        <div class="min-w-0">
                                            <a href="{{ route('campaigns.show', $campaign) }}" class="block max-w-[220px] truncate text-sm font-semibold text-white hover:text-mint-300">{{ $campaign->title }}</a>
                                            <p class="text-[11px] text-slate-500">{{ $campaign->platform() }} · {{ $campaign->task_type->label() }} · {{ $campaign->durationLabel() }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="text-xs font-semibold text-white">{{ number_format($campaign->completed_quantity) }} / {{ number_format($campaign->target_quantity) }}</p>
                                    <div class="mt-1 h-1.5 w-24 overflow-hidden rounded-full bg-white/10">
                                        <div class="h-full rounded-full bg-mint-400" style="width: {{ $campaign->progressPercent() }}%"></div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right text-xs font-semibold text-mint-300">{{ \App\Support\Currency::format($campaign->reward) }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                    <p class="text-xs font-bold text-white">{{ \App\Support\Currency::format($campaign->total_cost) }}</p>
                                    <p class="text-[10px] text-slate-500">Spent {{ \App\Support\Currency::format($campaign->budgetSpent()) }} · left {{ \App\Support\Currency::format($campaign->budgetRemaining()) }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                          style="border-color: {{ $status->hex() }}55; background: {{ $status->hex() }}18; color: {{ $status->hex() }}">{{ $status->label() }}</span>
                                    @if (filled($campaign->admin_notes))
                                        <p class="mt-1 max-w-[170px] text-[10px] leading-snug text-slate-500">{{ $campaign->admin_notes }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-400">{{ $campaign->created_at?->timezone($tz)->format('d M, h:i A') }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                    <a href="{{ route('campaigns.show', $campaign) }}" class="rounded-lg border border-white/15 bg-white/5 px-2.5 py-1 text-[11px] font-semibold text-slate-200 hover:bg-white/10">Details</a>
                                    @if ($cancellable)
                                        <form method="POST" action="{{ route('campaigns.cancel', $campaign) }}" class="mt-1 inline" onsubmit="return confirm('Campaign cancel karni hai? Unused budget wallet me wapas aa jayega.')">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-rose-400/25 bg-rose-500/10 px-2.5 py-1 text-[11px] font-semibold text-rose-300 hover:bg-rose-500/20">Cancel</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($campaigns->hasPages())
                <div class="border-t border-white/10 px-5 py-3">{{ $campaigns->links() }}</div>
            @endif
        </div>
    @endif
@endsection
