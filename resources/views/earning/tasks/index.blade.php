@extends('layouts.earning')

@section('title', 'Admin Tasks — '.config('earning.brand'))

@section('content')
    @php
        $tz = config('earning.timezone');
        $totalTasks = $tasks->count();
        $completedCount = $completedIds->intersect($tasks->pluck('id'))->count();
        $availableCount = max(0, $totalTasks - $completedCount);
    @endphp

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">Daily admin tasks</p>
            <h1 class="mt-1.5 font-display text-3xl font-bold text-white sm:text-4xl">Admin Tasks</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">
                Admin ki taraf se diye gaye tasks — har task ka reward exactly 2 PKR hai. Video watch tasks isi page ke official player me chalti hain; subscribe, follow aur like tasks ka asli page naye tab me khulta hai. Timer aur required watch time poora hone par reward milta hai.
            </p>
        </div>
        <div class="rounded-2xl border border-gold-400/25 bg-gold-400/5 px-4 py-3 text-right">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gold-300/80">Next unlock</p>
            <p class="font-display text-lg font-bold text-gold-300" data-countdown-to="{{ $nextResetAt->getTimestamp() * 1000 }}" data-countdown-reload>—</p>
            <p class="text-[10px] text-slate-400">{{ $nextResetAt->format('d M, h:i A') }} (raat 12 baje)</p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total admin tasks</p>
            <p class="mt-1.5 font-display text-2xl font-bold text-white">{{ $totalTasks }}</p>
        </div>
        <div class="rounded-2xl border border-mint-400/25 bg-mint-500/10 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-mint-200/80">Available</p>
            <p class="mt-1.5 font-display text-2xl font-bold text-white">{{ $availableCount }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Completed today</p>
            <p class="mt-1.5 font-display text-2xl font-bold text-white">{{ $completedCount }}<span class="text-base text-slate-500">/{{ $totalTasks }}</span></p>
        </div>
    </div>

    @if ($tasks->isEmpty())
        <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 px-6 py-16 text-center">
            <p class="text-4xl">📭</p>
            <h2 class="mt-3 text-lg font-bold text-white">Abhi koi admin task available nahi</h2>
            <p class="mt-1 text-sm text-slate-400">Admin jald naye tasks add karega. Neeche doosre users ke community tasks check karein.</p>
        </div>
    @else
        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($tasks as $task)
                @php
                    $isDone = $completedIds->contains($task->id);
                    $attempt = $inProgress->get($task->id);
                @endphp
                <article class="flex flex-col rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:border-white/20">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg font-black"
                              style="background: {{ $task->task_type->color() }}22; color: {{ $task->task_type->color() }}">{{ $task->task_type->icon() }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $task->platform() }} · {{ $task->task_type->label() }}</p>
                            <h2 class="mt-0.5 truncate text-base font-bold text-white" title="{{ $task->title }}">{{ $task->title }}</h2>
                        </div>
                        <span class="shrink-0 rounded-lg bg-mint-500/15 px-2.5 py-1 text-xs font-bold text-mint-300">{{ \App\Support\Currency::format($task->reward) }}</span>
                    </div>

                    @if (filled($task->instructions))
                        <p class="mt-3 line-clamp-3 text-xs leading-relaxed text-slate-400">{{ $task->instructions }}</p>
                    @endif

                    <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold">
                        <span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-slate-300">{{ $task->actionLabel() }}</span>
                        <span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-slate-300">⏱ {{ $task->durationLabel() }}</span>
                    </div>

                    <div class="mt-auto pt-4">
                        @if ($isDone)
                            <div class="flex items-center justify-between rounded-xl border border-white/10 bg-ink-900/60 px-3 py-2.5">
                                <span class="text-xs font-semibold text-slate-400">🔒 Aaj complete ho gaya</span>
                                <span class="text-[11px] text-slate-500" data-countdown-to="{{ $nextResetAt->getTimestamp() * 1000 }}">Unlock {{ $nextResetAt->format('h:i A') }}</span>
                            </div>
                        @elseif ($attempt)
                            @if ($attempt->canClaim($task))
                                <a href="{{ route('tasks.run', $task) }}" class="block rounded-xl bg-gradient-to-r from-gold-400 to-gold-500 px-4 py-2.5 text-center text-sm font-bold text-ink-950">Claim reward →</a>
                            @else
                                <a href="{{ route('tasks.run', $task) }}" class="block rounded-xl border border-gold-400/40 bg-gold-400/10 px-4 py-2.5 text-center text-sm font-bold text-gold-300">
                                    @if ($attempt->isReadyToClaim())
                                        Resume · watch time left
                                    @else
                                        Resume · {{ $attempt->remainingSeconds() }}s left
                                    @endif
                                </a>
                            @endif
                        @else
                            <form method="POST" action="{{ route('tasks.start', $task) }}">
                                @csrf
                                <button type="submit" class="block w-full rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-center text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/10">
                                    {{ $task->hasEmbeddedPlayer() ? 'Watch video →' : 'Watch task → real page' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-gold-300">Community</p>
            <h2 class="mt-1.5 font-display text-2xl font-bold text-white sm:text-3xl">Other Users Tasks</h2>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">
                Doosre users ki live campaigns — har action (subscribe, follow, like ya video view) par exactly 2 PKR. Ek campaign aap sirf ek dafa complete kar sakte hain.
            </p>
        </div>
        <a href="{{ route('campaigns.index') }}" class="shrink-0 rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10">View all community tasks →</a>
    </div>

    @if ($campaigns->isEmpty())
        <div class="mt-5 rounded-2xl border border-white/10 bg-white/5 px-6 py-12 text-center">
            <p class="text-4xl">🌱</p>
            <h3 class="mt-3 text-lg font-bold text-white">Abhi koi community task live nahi</h3>
            <p class="mt-1 text-sm text-slate-400">Aap khud campaign publish karke doosre users ko task de sakte hain.</p>
            <a href="{{ route('campaigns.create') }}" class="mt-4 inline-flex rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950">Create campaign</a>
        </div>
    @else
        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($campaigns as $campaign)
                @php
                    $isDone = $completedCampaignIds->contains($campaign->id);
                    $attempt = $inProgress->get($campaign->id);
                @endphp
                <article class="flex flex-col rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:border-white/20">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg font-black"
                              style="background: {{ $campaign->task_type->color() }}22; color: {{ $campaign->task_type->color() }}">{{ $campaign->task_type->icon() }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $campaign->platform() }} · {{ $campaign->task_type->label() }}</p>
                            <h3 class="mt-0.5 truncate text-base font-bold text-white" title="{{ $campaign->title }}">
                                <a href="{{ route('campaigns.show', $campaign) }}" class="hover:text-mint-300">{{ $campaign->title }}</a>
                            </h3>
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
                        @if ($isDone)
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
    @endif

    <p class="mt-8 text-center text-xs text-slate-500">Rewards har task complete hone ke foran baad wallet me add hote hain. Balance: {{ \App\Support\Currency::format($walletBalance) }}</p>
@endsection
