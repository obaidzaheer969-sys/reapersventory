@extends('layouts.earning')

@section('title', 'Community tasks — '.config('earning.brand'))

@section('content')
    @php
        $user = auth()->user();
        $available = $campaigns->getCollection()->reject(fn ($campaign) => $campaign->user_id === $user->id)->count();
    @endphp

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">Community</p>
            <h1 class="mt-1.5 font-display text-3xl font-bold text-white sm:text-4xl">Other users ke tasks</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">
                Yeh campaigns doosre users ne publish kiye hain. Har action (subscribe, follow, like ya video view) ka reward exactly 2 PKR hai. Har campaign ki quantity poori hone par woh khud ba khud hide ho jati hai. Ek campaign aap sirf ek dafa complete kar sakte hain.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('campaigns.create') }}" class="rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20">Create campaign</a>
            <a href="{{ route('campaigns.mine') }}" class="rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10">My campaigns</a>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Live campaigns</p>
            <p class="mt-1.5 font-display text-2xl font-bold text-white">{{ $campaigns->total() }}</p>
        </div>
        <div class="rounded-2xl border border-mint-400/25 bg-mint-500/10 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-mint-200/80">Available to you</p>
            <p class="mt-1.5 font-display text-2xl font-bold text-white">{{ $available }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Wallet balance</p>
            <p class="mt-1.5 font-display text-2xl font-bold text-white">{{ \App\Support\Currency::format($walletBalance) }}</p>
        </div>
    </div>

    @if ($campaigns->isEmpty())
        <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 px-6 py-16 text-center">
            <p class="text-4xl">🌱</p>
            <h2 class="mt-3 text-lg font-bold text-white">Abhi koi live campaign nahi</h2>
            <p class="mt-1 text-sm text-slate-400">Pehli campaign aap bhi publish kar sakte hain — wallet se payment karke apna task live karwayen.</p>
            <a href="{{ route('campaigns.create') }}" class="mt-4 inline-flex rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950">Create campaign</a>
        </div>
    @else
        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($campaigns as $campaign)
                @php
                    $isMine = $campaign->user_id === $user->id;
                    $isDone = $completedIds->contains($campaign->id);
                    $attempt = $inProgress->get($campaign->id);
                @endphp
                <article class="flex flex-col rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:border-white/20">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg font-black"
                              style="background: {{ $campaign->task_type->color() }}22; color: {{ $campaign->task_type->color() }}">{{ $campaign->task_type->icon() }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $campaign->platform() }} · {{ $campaign->task_type->label() }}</p>
                            <h2 class="mt-0.5 truncate text-base font-bold text-white" title="{{ $campaign->title }}">
                                <a href="{{ route('campaigns.show', $campaign) }}" class="hover:text-mint-300">{{ $campaign->title }}</a>
                            </h2>
                            <p class="mt-0.5 truncate text-[11px] text-slate-500">By {{ $campaign->owner?->name ?? 'User' }}</p>
                        </div>
                        <span class="shrink-0 rounded-lg bg-mint-500/15 px-2.5 py-1 text-xs font-bold text-mint-300">{{ \App\Support\Currency::format($campaign->reward) }}</span>
                    </div>

                    <div class="mt-3">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-slate-400">
                            <span>{{ number_format($campaign->completed_quantity) }} / {{ number_format($campaign->target_quantity) }} done</span>
                            <span>{{ $campaign->progressPercent() }}%</span>
                        </div>
                        <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-gradient-to-r from-mint-400 to-mint-500" style="width: {{ $campaign->progressPercent() }}%"></div>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold">
                        <span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-slate-300">{{ $campaign->actionLabel() }}</span>
                        <span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-slate-300">⏱ {{ $campaign->durationLabel() }}</span>
                        <span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-slate-300">{{ number_format($campaign->remainingQuantity()) }} left</span>
                    </div>

                    <div class="mt-auto pt-4">
                        @if ($isMine)
                            <a href="{{ route('campaigns.mine') }}" class="block rounded-xl border border-gold-400/30 bg-gold-400/10 px-4 py-2.5 text-center text-sm font-bold text-gold-300">Your campaign — manage</a>
                        @elseif ($isDone)
                            <div class="rounded-xl border border-white/10 bg-ink-900/60 px-3 py-2.5 text-center text-xs font-semibold text-slate-400">✓ Aap yeh campaign complete kar chuke hain</div>
                        @elseif ($attempt)
                            @if ($attempt->canClaim($campaign))
                                <a href="{{ route('tasks.run', $campaign) }}" class="block rounded-xl bg-gradient-to-r from-gold-400 to-gold-500 px-4 py-2.5 text-center text-sm font-bold text-ink-950">Claim reward →</a>
                            @else
                                <a href="{{ route('tasks.run', $campaign) }}" class="block rounded-xl border border-gold-400/40 bg-gold-400/10 px-4 py-2.5 text-center text-sm font-bold text-gold-300">
                                    @if ($attempt->isReadyToClaim())
                                        Resume · watch time left
                                    @else
                                        Resume · {{ $attempt->remainingSeconds() }}s left
                                    @endif
                                </a>
                            @endif
                        @else
                            <form method="POST" action="{{ route('tasks.start', $campaign) }}">
                                @csrf
                                <button type="submit" class="block w-full rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-center text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/10">
                                    {{ $campaign->hasEmbeddedPlayer() ? 'Watch video →' : 'Start task → real page' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if ($campaigns->hasPages())
            <div class="mt-6">{{ $campaigns->links() }}</div>
        @endif
    @endif
@endsection
