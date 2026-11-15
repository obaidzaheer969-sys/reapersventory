@extends('layouts.earning')

@section('title', 'Tasks — '.config('earning.brand'))

@section('content')
    @php
        $tz = config('earning.timezone');
        $totalTasks = $tasks->count();
        $completedCount = $completedIds->intersect($tasks->pluck('id'))->count();
        $availableCount = max(0, $totalTasks - $completedCount);
    @endphp

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">Daily tasks</p>
            <h1 class="mt-1.5 font-display text-3xl font-bold text-white sm:text-4xl">Aaj ke tasks</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">
                Task start karein — naye tab me timer ke saath lock bar khulega. Timer poora hone tak us tab ko band karna aur back jana band rahega.
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
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total tasks</p>
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
            <h2 class="mt-3 text-lg font-bold text-white">Abhi koi task available nahi</h2>
            <p class="mt-1 text-sm text-slate-400">Admin jald naye tasks add karega. Announcements check karte rahein.</p>
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
                            @if ($attempt->isReadyToClaim())
                                <a href="{{ route('tasks.run', $task) }}" class="block rounded-xl bg-gradient-to-r from-gold-400 to-gold-500 px-4 py-2.5 text-center text-sm font-bold text-ink-950">Claim reward →</a>
                            @else
                                <a href="{{ route('tasks.run', $task) }}" class="block rounded-xl border border-gold-400/40 bg-gold-400/10 px-4 py-2.5 text-center text-sm font-bold text-gold-300">
                                    Resume · {{ $attempt->remainingSeconds() }}s left
                                </a>
                            @endif
                        @else
                            <form method="POST" action="{{ route('tasks.start', $task) }}" target="_blank">
                                @csrf
                                <button type="submit" class="block w-full rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-center text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/10">
                                    Start task → locked tab
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
