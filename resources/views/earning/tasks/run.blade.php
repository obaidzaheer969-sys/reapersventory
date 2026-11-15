@extends('layouts.earning')

@section('title', 'Running task — '.config('earning.brand'))

@section('content')
    @php
        $totalSeconds = max(1, (int) $task->duration_seconds);
        $remaining = max(0, (int) $remainingSeconds);
        $ready = $remaining === 0;
        $embedUrl = $task->embedUrl();
    @endphp

    <div class="mx-auto max-w-5xl {{ $ready ? '' : 'pb-40' }}">
        <a href="{{ route('tasks.index') }}" data-lock-inline-back
           class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $ready ? 'text-slate-400 hover:text-white' : 'cursor-not-allowed text-slate-600' }}"
           @if (! $ready) aria-disabled="true" @endif>← Back to tasks</a>

        <div class="mt-4 overflow-hidden rounded-3xl border border-white/10 bg-white/5">
            <div class="border-b border-white/10 bg-gradient-to-r from-ink-850 via-ink-900 to-ink-850 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-lg font-black"
                          style="background: {{ $task->task_type->color() }}22; color: {{ $task->task_type->color() }}">{{ $task->task_type->icon() }}</span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $task->platform() }} · {{ $task->task_type->label() }}</p>
                        <h1 class="truncate font-display text-lg font-bold text-white sm:text-xl">{{ $task->title }}</h1>
                    </div>
                    <span class="ml-auto shrink-0 rounded-xl bg-mint-500/15 px-3 py-1.5 text-sm font-bold text-mint-300">{{ \App\Support\Currency::format($expectedReward) }}</span>
                </div>
            </div>

            <div class="bg-ink-950/70">
                <div class="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-2">
                    <span class="inline-flex items-center gap-2 text-[11px] font-semibold text-slate-400">
                        <span class="h-2 w-2 rounded-full {{ $ready ? 'bg-mint-400' : 'animate-pulse bg-gold-400' }}"></span>
                        {{ $ready ? 'Timer complete — claim karein' : 'Task content — timer chal raha hai' }}
                    </span>
                    <a href="{{ $task->target_url }}" target="_blank" rel="noopener noreferrer" class="text-[11px] font-bold text-mint-300 hover:text-mint-200">Open in new tab ↗</a>
                </div>

                <div class="relative w-full" style="height: clamp(300px, 58vh, 640px);">
                    <iframe src="{{ $embedUrl }}" title="{{ $task->title }}" loading="eager" allowfullscreen
                            class="absolute inset-0 h-full w-full border-0"
                            referrerpolicy="no-referrer-when-downgrade"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; fullscreen; gyroscope; picture-in-picture; web-share"></iframe>
                </div>

                <p class="border-t border-white/10 px-4 py-2 text-[11px] leading-relaxed text-slate-500">
                    Agar content load na ho (kuch sites embedding block karti hain) to neeche <span class="font-semibold text-slate-300">Open link ↗</span> dabaein — yeh tab lock rahega.
                </p>
            </div>

            <div class="px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-mint-300" data-timer-status>{{ $ready ? 'Ready to claim' : 'Seconds remaining' }}</p>
                        <p class="mt-1 font-display text-4xl font-bold tabular-nums text-white" data-timer-display>{{ gmdate('i:s', $remaining) }}</p>
                        <div class="mt-3 h-2 w-full max-w-xs overflow-hidden rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-gradient-to-r from-mint-400 to-mint-500 transition-[width] duration-1000 ease-linear"
                                 data-timer-bar style="width: {{ $ready ? 100 : round((($totalSeconds - $remaining) / $totalSeconds) * 100) }}%"></div>
                        </div>
                    </div>

                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:min-w-[220px]">
                        <a href="{{ $task->target_url }}" target="_blank" rel="noopener noreferrer"
                           class="w-full rounded-xl px-4 py-2.5 text-center text-sm font-bold {{ $ready ? 'border border-white/15 bg-white/5 text-white hover:bg-white/10' : 'bg-white text-ink-950 hover:bg-slate-200' }}">
                            Open link ↗
                        </a>
                        <form method="POST" action="{{ route('tasks.complete', $task) }}" class="w-full">
                            @csrf
                            <button type="submit" data-claim-button
                                    class="w-full rounded-xl px-5 py-2.5 text-sm font-bold transition {{ $ready ? 'bg-gradient-to-r from-mint-400 to-mint-500 text-ink-950 shadow-lg shadow-mint-500/20' : 'cursor-not-allowed bg-white/5 text-slate-500' }}"
                                    @disabled(! $ready)>
                                {{ $ready ? 'Claim '.config('earning.currency').' '.number_format((float) $expectedReward, 2) : 'Please wait…' }}
                            </button>
                        </form>
                    </div>
                </div>

                <p class="mt-4 text-xs leading-relaxed text-slate-500">
                    Task ka link isi tab me neeche khud load hota hai. Timer poora hone tak yeh tab band karna aur back jana band rahega — time poora hone par khud unlock ho jayega.
                </p>
            </div>

            @if (filled($task->instructions))
                <div class="border-t border-white/10 bg-ink-900/40 px-5 py-4 sm:px-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Instructions</p>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-300">{{ $task->instructions }}</p>
                </div>
            @endif
        </div>
    </div>

    @if (! $ready)
        <div class="fixed inset-x-0 bottom-0 z-50 border-t border-gold-400/30 bg-ink-950/95 shadow-[0_-16px_40px_rgba(0,0,0,.5)] backdrop-blur-xl"
             data-task-lock role="region" aria-label="Task lock countdown">
            <div class="mx-auto max-w-3xl px-4 py-3 sm:px-6" data-lock-inner>
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gold-400/15 text-lg" data-lock-icon aria-hidden="true">🔒</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-gold-300" data-lock-label>Task locked</p>
                        <p class="truncate text-sm font-semibold text-white" data-lock-message>
                            Timer poora hone tak tab band karna aur back jana band hai.
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="font-display text-2xl font-bold tabular-nums text-white" data-lock-countdown>{{ gmdate('i:s', $remaining) }}</p>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gold-300/80">seconds left</p>
                    </div>
                </div>

                <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-white/10">
                    <div class="h-full rounded-full bg-gradient-to-r from-gold-400 to-gold-500 transition-[width] duration-1000 ease-linear"
                         data-lock-bar style="width: {{ round((($totalSeconds - $remaining) / $totalSeconds) * 100) }}%"></div>
                </div>

                <div class="mt-2.5 flex items-center justify-between gap-3">
                    <p class="text-[11px] leading-snug text-slate-500" data-lock-hint>
                        Back button, tab close aur page reload timer ke baad hi chalenge.
                    </p>
                    <a href="{{ route('tasks.index') }}" data-lock-back aria-disabled="true" tabindex="-1"
                       class="shrink-0 cursor-not-allowed rounded-lg bg-white/5 px-3 py-1.5 text-xs font-bold text-slate-500">
                        Back to tasks
                    </a>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('styles')
    <style>
        @keyframes task-lock-shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }

        .task-lock-shake { animation: task-lock-shake .4s ease-in-out; }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            var remaining = {{ $remaining }};
            var total = {{ $totalSeconds }};
            var currency = @json(config('earning.currency'));
            var reward = @json(number_format((float) $expectedReward, 2));
            var locked = remaining > 0;

            var display = document.querySelector('[data-timer-display]');
            var bar = document.querySelector('[data-timer-bar]');
            var status = document.querySelector('[data-timer-status]');
            var button = document.querySelector('[data-claim-button]');

            var lockBar = document.querySelector('[data-task-lock]');
            var lockInner = document.querySelector('[data-lock-inner]');
            var lockCountdown = document.querySelector('[data-lock-countdown]');
            var lockFill = document.querySelector('[data-lock-bar]');
            var lockHint = document.querySelector('[data-lock-hint]');
            var inlineBack = document.querySelector('[data-lock-inline-back]');

            var originalHint = lockHint ? lockHint.textContent.trim() : '';
            var warnTimeout = null;

            function pad(value) {
                return String(value).padStart(2, '0');
            }

            function clock(seconds) {
                return pad(Math.floor(seconds / 60)) + ':' + pad(seconds % 60);
            }

            function progress() {
                return Math.min(100, Math.round(((total - remaining) / total) * 100));
            }

            function render() {
                if (display) {
                    display.textContent = clock(remaining);
                }

                var percent = progress();

                if (bar) {
                    bar.style.width = percent + '%';
                }

                if (lockCountdown) {
                    lockCountdown.textContent = clock(remaining);
                }

                if (lockFill) {
                    lockFill.style.width = percent + '%';
                }
            }

            function showUnlockedToast() {
                var toast = document.createElement('div');
                toast.textContent = '🔓 Timer poora — ab aap tab band kar sakte hain ya back ja sakte hain.';
                toast.setAttribute('style', 'position:fixed;left:50%;bottom:1.5rem;transform:translateX(-50%);z-index:60;width:max-content;max-width:calc(100vw - 2rem);border-radius:0.85rem;background:linear-gradient(90deg,#34d399,#10b981);color:#060a12;font-weight:700;font-size:0.8rem;line-height:1.3;padding:0.7rem 1rem;box-shadow:0 14px 34px rgba(16,185,129,.4);text-align:center;');
                document.body.appendChild(toast);

                window.setTimeout(function () {
                    toast.remove();
                }, 8000);
            }

            function unlock() {
                locked = false;
                window.clearTimeout(warnTimeout);

                if (button) {
                    button.disabled = false;
                    button.classList.remove('cursor-not-allowed', 'bg-white/5', 'text-slate-500');
                    button.classList.add('bg-gradient-to-r', 'from-mint-400', 'to-mint-500', 'text-ink-950', 'shadow-lg', 'shadow-mint-500/20');
                    button.textContent = 'Claim ' + currency + ' ' + reward;
                }

                if (status) {
                    status.textContent = 'Ready to claim';
                }

                if (lockBar) {
                    lockBar.hidden = true;
                }

                if (inlineBack) {
                    inlineBack.removeAttribute('aria-disabled');
                    inlineBack.removeAttribute('tabindex');
                    inlineBack.style.cursor = '';
                    inlineBack.style.opacity = '1';
                }

                showUnlockedToast();
            }

            function warnLocked() {
                if (!locked || !lockBar) {
                    return;
                }

                if (lockInner) {
                    lockInner.classList.remove('task-lock-shake');
                    void lockInner.offsetWidth;
                    lockInner.classList.add('task-lock-shake');
                }

                if (lockHint && originalHint) {
                    lockHint.textContent = '🔒 Pehle timer poora karein, phir tab band ya back allowed hoga.';
                    window.clearTimeout(warnTimeout);
                    warnTimeout = window.setTimeout(function () {
                        lockHint.textContent = originalHint;
                    }, 3500);
                }
            }

            document.addEventListener('click', function (event) {
                if (!locked) {
                    return;
                }

                var target = event.target instanceof Element ? event.target : null;
                var link = target ? target.closest('a[href]') : null;

                if (!link || link.hasAttribute('data-lock-allow') || link.target === '_blank') {
                    return;
                }

                var href = link.getAttribute('href') || '';
                if (href.charAt(0) === '#') {
                    return;
                }

                var url;
                try {
                    url = new URL(link.href, window.location.href);
                } catch (error) {
                    return;
                }

                if (url.origin !== window.location.origin) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                warnLocked();
            }, true);

            window.addEventListener('popstate', function () {
                if (!locked) {
                    return;
                }

                try {
                    history.pushState({ earningTaskLock: true }, '', window.location.href);
                } catch (error) {}

                warnLocked();
            });

            window.addEventListener('beforeunload', function (event) {
                if (!locked) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
                return '';
            });

            render();

            if (!locked) {
                return;
            }

            try {
                history.pushState({ earningTaskLock: true }, '', window.location.href);
            } catch (error) {}

            var timer = window.setInterval(function () {
                remaining -= 1;

                if (remaining <= 0) {
                    remaining = 0;
                    window.clearInterval(timer);
                    render();
                    unlock();
                    return;
                }

                render();
            }, 1000);
        })();
    </script>
@endpush
