@extends('layouts.earning')

@section('title', 'Running task — '.config('earning.brand'))

@section('content')
    @php
        $totalSeconds = max(1, (int) $task->duration_seconds);
        $remaining = max(0, (int) $remainingSeconds);
        $ready = $remaining === 0;
        $isEmbedded = (bool) ($embedded ?? false);
        $timerPaused = (bool) ($timerPaused ?? false);
        $waitingForPage = ! $isEmbedded && $timerPaused;
        $watchRequired = $isEmbedded ? max(1, (int) $requiredSeconds) : 0;
        $watched = $isEmbedded ? min($watchRequired, (int) $watchedSeconds) : 0;
        $watchReady = ! $isEmbedded || $watched >= $watchRequired;
        $claimable = $ready && $watchReady && ($isEmbedded || $realPageOpened);
        $claimLabel = $claimable
            ? 'Claim '.config('earning.currency').' '.number_format((float) $expectedReward, 2)
            : (! $isEmbedded && ! $realPageOpened
                ? 'Open the real page first'
                : ($isEmbedded && $ready ? 'Watch '.(max(0, $watchRequired - $watched)).'s more' : 'Please wait…'));
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

            <div class="bg-ink-950/70 px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-slate-300">
                            <span class="h-2 w-2 rounded-full {{ $ready && $watchReady ? 'bg-mint-400' : 'animate-pulse bg-gold-400' }}" data-real-dot></span>
                            <span data-real-status>
                                @if ($isEmbedded)
                                    {{ $ready && $watchReady ? 'Timer complete — claim now' : 'Play the video — start watch time' }}
                                @else
                                    {{ $ready ? 'Timer complete — claim now' : ($waitingForPage ? 'Open the real page — the timer has not started' : 'Open the real page — the timer is running') }}
                                @endif
                            </span>
                        </p>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300">
                            @if ($isEmbedded)
                                The video is playing in the official {{ $task->platform() }} player below. Click Play — watch time is counted on this page. Claim the reward after the timer and required watch time finish.
                            @else
                                Click "Open real page" first — the seconds will not start until the real page opens. Play the video there, like, or subscribe — views, watch time, likes, and subscribers are counted in real time on YouTube/Facebook. The timer runs in this task tab; closing this tab will fail the task.
                            @endif
                        </p>
                        <p class="mt-1.5 truncate text-[11px] text-slate-500" title="{{ $task->target_url }}">{{ $task->target_url }}</p>
                    </div>

                    @if ($isEmbedded)
                        <span class="shrink-0 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-center text-sm font-bold text-slate-200">
                            ⏱ {{ $watchRequired }}s watch
                        </span>
                    @else
                        <button type="button" data-open-real
                                class="shrink-0 rounded-xl bg-white px-4 py-2.5 text-center text-sm font-bold text-ink-950 hover:bg-slate-200">
                            Open real page ↗
                        </button>
                    @endif
                </div>

                @if ($isEmbedded)
                    <div class="mt-4 overflow-hidden rounded-2xl border border-white/10 bg-black">
                        @if ($embedPlatform === 'youtube')
                            <div class="aspect-video min-h-[240px] w-full">
                                <iframe id="task-embed-player" data-embed-player data-platform="youtube"
                                        src="{{ $embedUrl }}"
                                        title="{{ $task->title }}"
                                        class="h-full w-full"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        allowfullscreen></iframe>
                            </div>
                        @else
                            <div id="fb-root"></div>
                            <div class="flex min-h-[240px] items-center justify-center p-3">
                                <div class="fb-video w-full"
                                     data-href="{{ $task->target_url }}"
                                     data-width="640"
                                     data-show-text="false"
                                     data-autoplay="false"
                                     data-allowfullscreen="true"></div>
                            </div>
                            <noscript>
                                <iframe src="{{ $embedUrl }}"
                                        title="{{ $task->title }}"
                                        class="aspect-video w-full"
                                        frameborder="0"
                                        allow="autoplay; encrypted-media; picture-in-picture"
                                        allowfullscreen></iframe>
                            </noscript>
                        @endif
                    </div>

                    <div class="mt-3 rounded-xl border border-white/10 bg-white/5 px-3 py-3">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-slate-300">
                            <span>Watch time</span>
                            <span><span data-watch-seconds>{{ $watched }}</span> / {{ $watchRequired }} sec</span>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-gradient-to-r from-mint-400 to-mint-500 transition-[width] duration-500"
                                 data-watch-bar style="width: {{ $watchRequired > 0 ? round(($watched / $watchRequired) * 100) : 0 }}%"></div>
                        </div>
                        <p class="mt-2 text-[11px] leading-relaxed text-slate-500" data-play-hint>
                            Click the video play button. Without playback, watch time will not count and the claim stays locked.
                        </p>
                    </div>

                    @if ($embedPlatform === 'youtube')
                        <p class="mt-3 rounded-xl border border-gold-400/25 bg-gold-400/5 px-3 py-2 text-[11px] leading-relaxed text-gold-200/90">
                            💡 YouTube runs ads on monetized videos according to its own rules. The creator must have monetization and embedding enabled, and the viewer must not use YouTube Premium or an ad blocker. We cannot force ads, and YouTube may treat paid or incentivized views as invalid traffic.
                        </p>
                    @endif

                    <p class="mt-3 rounded-xl border border-gold-400/25 bg-gold-400/5 px-3 py-2 text-[11px] leading-relaxed text-gold-200/90">
                        🔒 Do not close, reload, or go back from this tab until the timer finishes. Pausing the video pauses the timer and stops watch time from increasing.
                    </p>
                @else
                    <p class="mt-3 rounded-xl border border-gold-400/25 bg-gold-400/5 px-3 py-2 text-[11px] leading-relaxed text-gold-200/90" data-real-hint>
                        🔒 Click "Open real page" first — the timer will not start until then. The seconds start as soon as the real page opens; closing this task tab before the timer finishes will <span class="font-bold">FAIL</span> the task.
                    </p>
                @endif

                <div data-fail-banner class="mt-3 hidden rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-3">
                    <p class="text-sm font-bold text-rose-200">❌ Task failed</p>
                    <p class="mt-1 text-[11px] leading-relaxed text-rose-100/80">
                        @if ($isEmbedded)
                            This tab was closed before the timer finished. You will not receive the reward — start again and keep the video playing for the required time.
                        @else
                            This task tab was closed before the timer finished. You will not receive the reward — start again.
                        @endif
                    </p>
                    <a href="{{ route('tasks.index') }}" class="mt-2.5 inline-flex rounded-lg bg-rose-500 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-rose-400">Start again →</a>
                </div>
            </div>

            <div class="border-t border-white/10 px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-mint-300" data-timer-status>{{ $ready ? 'Ready to claim' : 'Seconds remaining' }}</p>
                        <p class="mt-1 font-display text-4xl font-bold tabular-nums text-white {{ $ready ? '' : 'hidden' }}" data-timer-display>{{ gmdate('i:s', $remaining) }}</p>
                        @if (! $ready)
                            <p class="mt-1 text-sm font-semibold text-gold-300" data-timer-lock-note>{{ $waitingForPage ? 'Timer is waiting for the real page to open' : ($timerPaused ? 'Timer paused because the video is paused' : 'The timer is running in the lock bar below') }}</p>
                        @endif
                        <div class="mt-3 h-2 w-full max-w-xs overflow-hidden rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-gradient-to-r from-mint-400 to-mint-500 transition-[width] duration-1000 ease-linear"
                                 data-timer-bar style="width: {{ $ready ? 100 : round((($totalSeconds - $remaining) / $totalSeconds) * 100) }}%"></div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('tasks.complete', $task) }}" class="w-full sm:w-auto sm:min-w-[220px]">
                        @csrf
                        <button type="submit" data-claim-button
                                class="w-full rounded-xl px-5 py-2.5 text-sm font-bold transition {{ $claimable ? 'bg-gradient-to-r from-mint-400 to-mint-500 text-ink-950 shadow-lg shadow-mint-500/20' : 'cursor-not-allowed bg-white/5 text-slate-500' }}"
                                @disabled(! $claimable)>
                            {{ $claimLabel }}
                        </button>
                    </form>
                </div>

                <p class="mt-4 text-xs leading-relaxed text-slate-500">
                    @if ($isEmbedded)
                        The timer runs in this tab and the title bar shows the seconds. Do not close this tab or go back until the timer finishes. The timer and watch time run only while the video is playing — both pause when the video pauses.
                    @else
                        Click "Open real page" first — the timer starts in this tab as soon as the real page opens and the title bar shows the seconds. Do not close this task tab or go back until the timer finishes. The real page opens in a separate tab; complete the action there.
                    @endif
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
                            Do not close this tab or go back until the timer finishes.
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
                        The back button, tab close, and page reload will work after the timer finishes.
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
            var brand = @json(config('earning.brand'));
            var targetUrl = @json($task->target_url);
            var openedUrl = @json(route('tasks.opened', $task));
            var progressUrl = @json(route('tasks.progress', $task));
            var failUrl = @json(route('tasks.fail', $task));
            var csrf = @json(csrf_token());
            var embedded = @json((bool) $isEmbedded);
            var platform = @json($embedPlatform);
            var required = {{ (int) $watchRequired }};
            var watched = {{ (int) $watched }};
            var opened = @json($isEmbedded ? (bool) $embedPlayed : (bool) $realPageOpened);

            var locked = remaining > 0;
            var finished = remaining === 0;
            var failed = false;
            var deadline = Date.now() + remaining * 1000;
            var ticker = null;
            var heartbeat = null;
            var videoPlaying = false;
            var timerPaused = @json((bool) ($timerPaused ?? false));
            var pausedRemaining = remaining;
            var progressSequence = 0;
            var openSequence = 0;

            var display = document.querySelector('[data-timer-display]');
            var timerLockNote = document.querySelector('[data-timer-lock-note]');
            var bar = document.querySelector('[data-timer-bar]');
            var status = document.querySelector('[data-timer-status]');
            var button = document.querySelector('[data-claim-button]');

            var openButton = document.querySelector('[data-open-real]');
            var realStatus = document.querySelector('[data-real-status]');
            var realDot = document.querySelector('[data-real-dot]');
            var realHint = document.querySelector('[data-real-hint]');
            var failBanner = document.querySelector('[data-fail-banner]');

            var watchSecondsEl = document.querySelector('[data-watch-seconds]');
            var watchBar = document.querySelector('[data-watch-bar]');
            var playHint = document.querySelector('[data-play-hint]');

            var lockBar = document.querySelector('[data-task-lock]');
            var lockInner = document.querySelector('[data-lock-inner]');
            var lockLabel = document.querySelector('[data-lock-label]');
            var lockMessage = document.querySelector('[data-lock-message]');
            var lockCountdown = document.querySelector('[data-lock-countdown]');
            var lockFill = document.querySelector('[data-lock-bar]');
            var lockHint = document.querySelector('[data-lock-hint]');
            var inlineBack = document.querySelector('[data-lock-inline-back]');

            var originalLockLabel = lockLabel ? lockLabel.textContent.trim() : '';
            var originalLockMessage = lockMessage ? lockMessage.textContent.trim() : '';
            var originalHint = lockHint ? lockHint.textContent.trim() : '';
            var warnTimeout = null;
            var pageTitle = 'Task — ' + brand;

            function pad(value) {
                return String(value).padStart(2, '0');
            }

            function clock(seconds) {
                return pad(Math.floor(seconds / 60)) + ':' + pad(seconds % 60);
            }

            function syncTimerDisplay() {
                if (display) {
                    display.classList.toggle('hidden', locked);
                }

                if (timerLockNote) {
                    timerLockNote.classList.toggle('hidden', !locked);
                }
            }

            function stopTicker() {
                if (ticker === null) {
                    return;
                }

                window.clearInterval(ticker);

                if (window.__earningTaskTicker === ticker) {
                    window.__earningTaskTicker = null;
                }

                ticker = null;
            }

            function updateTimerPauseUi() {
                var pausedNow = timerPaused && locked && !finished && !failed;

                if (lockLabel) {
                    lockLabel.textContent = pausedNow
                        ? (embedded ? 'Timer paused' : 'Waiting for real page')
                        : originalLockLabel;
                }

                if (lockMessage) {
                    lockMessage.textContent = pausedNow
                        ? (embedded
                            ? 'Video paused — the timer will resume when you press Play.'
                            : 'The real page is not open yet — the timer will start when you click "Open real page".')
                        : originalLockMessage;
                }

                if (timerLockNote) {
                    timerLockNote.textContent = pausedNow
                        ? (embedded
                            ? 'Timer paused because the video is paused'
                            : 'Timer is waiting for the real page to open')
                        : 'The timer is running in the lock bar below';
                }
            }

            function pauseLocalTimer() {
                if (!embedded || !locked || finished || failed || timerPaused) {
                    return;
                }

                pausedRemaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
                remaining = pausedRemaining;
                timerPaused = true;
                updateTimerPauseUi();
                render();
            }

            function resumeLocalTimer() {
                if (!embedded || !locked || finished || failed || !timerPaused) {
                    return;
                }

                remaining = pausedRemaining;
                deadline = Date.now() + remaining * 1000;
                timerPaused = false;
                updateTimerPauseUi();
                render();
            }

            function progress() {
                return Math.min(100, Math.round(((total - remaining) / total) * 100));
            }

            function notifyServerFailure() {
                var payload = new URLSearchParams();
                payload.append('_token', csrf);

                try {
                    if (navigator.sendBeacon && navigator.sendBeacon(failUrl, payload)) {
                        return;
                    }
                } catch (error) {}

                try {
                    fetch(failUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        keepalive: true,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: payload
                    }).catch(function () {});
                } catch (error) {}
            }

            function sendProgress(playing) {
                if (!embedded || failed) {
                    return;
                }

                playing = !!playing;

                if (playing) {
                    resumeLocalTimer();
                } else {
                    pauseLocalTimer();
                }

                var sequence = ++progressSequence;

                try {
                    fetch(progressUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ playing: playing })
                    }).then(function (response) {
                        return response.ok ? response.json() : null;
                    }).then(function (data) {
                        if (!data || !data.ok || sequence !== progressSequence) {
                            return;
                        }

                        if (typeof data.required_seconds === 'number' && data.required_seconds > 0) {
                            required = data.required_seconds;
                        }

                        if (typeof data.watched_seconds === 'number') {
                            watched = Math.max(watched, Math.min(required, data.watched_seconds));
                        }

                        if (typeof data.timer_remaining_seconds === 'number' && locked && !finished && !failed) {
                            remaining = Math.max(0, data.timer_remaining_seconds);
                            pausedRemaining = remaining;
                            deadline = Date.now() + remaining * 1000;
                            render();
                        }

                        renderWatch();
                        updateClaim();
                        updateEmbedStatus();
                    }).catch(function () {});
                } catch (error) {}
            }

            function confirmRealPageOpened() {
                if (failed || embedded) {
                    return;
                }

                var sequence = ++openSequence;

                try {
                    fetch(openedUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: '{}'
                    }).then(function (response) {
                        return response.ok ? response.json() : null;
                    }).then(function (data) {
                        if (!data || !data.ok || sequence !== openSequence || failed) {
                            return;
                        }

                        opened = true;
                        timerPaused = !!data.timer_paused;

                        if (typeof data.timer_remaining_seconds === 'number' && locked && !finished) {
                            remaining = Math.max(0, data.timer_remaining_seconds);
                            pausedRemaining = remaining;
                            deadline = Date.now() + remaining * 1000;
                        }

                        updateTimerPauseUi();
                        render();
                        setOpenUi();
                        updateClaim();
                    }).catch(function () {
                        if (sequence !== openSequence || failed) {
                            return;
                        }

                        setRealStatus('Real page not confirmed — click "Open real page" again', 'gold');

                        if (realHint) {
                            realHint.textContent = '⚠️ The timer has not started yet. Keep the real page open and click "Open real page" again.';
                        }
                    });
                } catch (error) {}
            }

            function setRealStatus(text, tone) {
                if (realStatus) {
                    realStatus.textContent = text;
                }

                if (!realDot) {
                    return;
                }

                var dotClasses = {
                    gold: 'h-2 w-2 rounded-full animate-pulse bg-gold-400',
                    mint: 'h-2 w-2 rounded-full bg-mint-400',
                    rose: 'h-2 w-2 rounded-full bg-rose-400'
                };

                realDot.className = dotClasses[tone] || dotClasses.gold;
            }

            function updateEmbedStatus() {
                if (!embedded) {
                    return;
                }

                if (failed) {
                    setRealStatus('Task failed', 'rose');

                    return;
                }

                if (finished && watched >= required) {
                    setRealStatus('Timer complete — claim now', 'mint');

                    return;
                }

                if (finished) {
                    setRealStatus('Timer complete — watch ' + Math.max(0, required - watched) + 's more of the video', 'gold');

                    return;
                }

                if (videoPlaying) {
                    setRealStatus('Video is playing — watch time is being counted', 'mint');

                    return;
                }

                if (opened) {
                    setRealStatus('Video paused — press Play; watch time and the timer are both paused', 'gold');

                    return;
                }

                setRealStatus('Click the video play button — start watch time', 'gold');
            }

            function renderWatch() {
                if (watchSecondsEl) {
                    watchSecondsEl.textContent = String(watched);
                }

                if (watchBar) {
                    watchBar.style.width = (required > 0 ? Math.min(100, Math.round((watched / required) * 100)) : 0) + '%';
                }

                if (playHint) {
                    if (finished && watched < required) {
                        playHint.textContent = 'The timer finished, but the video still needs ' + Math.max(0, required - watched) + ' more seconds of playback.';
                    } else if (videoPlaying) {
                        playHint.textContent = 'Video is playing — watch time is being counted. Keep this tab open.';
                    } else if (opened) {
                        playHint.textContent = 'Video paused — press Play; watch time and the timer will resume.';
                    } else {
                        playHint.textContent = 'Click the video play button. Without playback, watch time will not count.';
                    }
                }
            }

            function updateClaim() {
                if (!button) {
                    return;
                }

                var watchReady = !embedded || watched >= required;
                var claimable = !failed && finished && watchReady && (embedded || opened);

                button.disabled = !claimable;
                button.classList.toggle('cursor-not-allowed', !claimable);
                button.classList.toggle('bg-white/5', !claimable);
                button.classList.toggle('text-slate-500', !claimable);
                button.classList.toggle('bg-gradient-to-r', claimable);
                button.classList.toggle('from-mint-400', claimable);
                button.classList.toggle('to-mint-500', claimable);
                button.classList.toggle('text-ink-950', claimable);
                button.classList.toggle('shadow-lg', claimable);
                button.classList.toggle('shadow-mint-500/20', claimable);

                if (failed) {
                    button.textContent = 'Task failed';
                } else if (!embedded && !opened) {
                    button.textContent = 'Open the real page first';
                } else if (embedded && finished && !watchReady) {
                    button.textContent = 'Watch ' + Math.max(0, required - watched) + 's more';
                } else if (claimable) {
                    button.textContent = 'Claim ' + currency + ' ' + reward;
                } else {
                    button.textContent = 'Please wait…';
                }
            }

            function render() {
                if (display) {
                    display.textContent = clock(remaining);
                }

                syncTimerDisplay();

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

                document.title = locked ? clock(remaining) + ' ⏳ ' + brand : pageTitle;
            }

            function setOpenUi() {
                if (openButton) {
                    openButton.textContent = opened ? 'Real page is open ↗' : 'Open real page ↗';
                }

                if (realHint) {
                    realHint.textContent = '🔒 Do not close this task tab before the timer finishes — closing it will FAIL the task. The real page is open in a separate tab; complete the action there.';
                }

                if (timerPaused) {
                    setRealStatus('Real page is open — the timer will start', 'gold');
                } else {
                    setRealStatus(
                        finished ? 'Timer complete — claim now' : 'Real page is open — the timer is running',
                        'mint'
                    );
                }

                updateClaim();
            }

            function openReal() {
                if (failed) {
                    return;
                }

                var win = window.open(targetUrl, '_blank');

                if (!win) {
                    setRealStatus('Popup blocked — click "Open real page" again', 'gold');

                    if (realHint) {
                        realHint.textContent = '⚠️ Your browser blocked the new tab. Allow pop-ups from the address bar icon, then click "Open real page" again.';
                    }

                    return;
                }

                try {
                    win.opener = null;
                } catch (error) {}

                if (openButton) {
                    openButton.textContent = 'Real page is open ↗';
                }

                setRealStatus('Real page is open — starting the timer…', 'gold');
                confirmRealPageOpened();
            }

            function startHeartbeat() {
                if (!embedded || heartbeat) {
                    return;
                }

                heartbeat = window.setInterval(function () {
                    if (!videoPlaying || document.hidden) {
                        return;
                    }

                    sendProgress(true);
                }, 5000);
            }

            function failTask() {
                if (failed) {
                    return;
                }

                failed = true;
                locked = false;
                timerPaused = false;

                stopTicker();
                syncTimerDisplay();
                updateTimerPauseUi();

                if (lockBar) {
                    lockBar.hidden = true;
                }

                if (failBanner) {
                    failBanner.classList.remove('hidden');
                }

                if (inlineBack) {
                    inlineBack.removeAttribute('aria-disabled');
                    inlineBack.removeAttribute('tabindex');
                    inlineBack.style.cursor = '';
                    inlineBack.style.opacity = '1';
                }

                if (openButton) {
                    openButton.disabled = true;
                }

                if (status) {
                    status.textContent = 'Task failed';
                }

                setRealStatus(
                    embedded ? 'Task failed — the tab was closed before the timer finished' : 'Task failed — the task tab was closed before the timer finished',
                    'rose'
                );
                document.title = '❌ Task failed — ' + brand;
                updateClaim();
                notifyServerFailure();
            }

            function showUnlockedToast(message) {
                var toast = document.createElement('div');
                toast.textContent = message;
                toast.setAttribute('style', 'position:fixed;left:50%;bottom:1.5rem;transform:translateX(-50%);z-index:60;width:max-content;max-width:calc(100vw - 2rem);border-radius:0.85rem;background:linear-gradient(90deg,#34d399,#10b981);color:#060a12;font-weight:700;font-size:0.8rem;line-height:1.3;padding:0.7rem 1rem;box-shadow:0 14px 34px rgba(16,185,129,.4);text-align:center;');
                document.body.appendChild(toast);

                window.setTimeout(function () {
                    toast.remove();
                }, 8000);
            }

            function unlock() {
                locked = false;
                finished = true;
                timerPaused = false;
                window.clearTimeout(warnTimeout);
                stopTicker();
                syncTimerDisplay();
                updateTimerPauseUi();

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

                document.title = pageTitle;

                if (embedded) {
                    updateEmbedStatus();
                    renderWatch();

                    if (watched < required) {
                        showUnlockedToast('⏳ Timer finished — watch ' + Math.max(0, required - watched) + ' more seconds of the video.');
                    } else {
                        showUnlockedToast('🔓 Timer finished — you can now claim the reward.');
                    }
                } else if (opened) {
                    setRealStatus('Timer complete — claim now', 'mint');
                    showUnlockedToast('🔓 Timer finished — you can now close this tab or go back.');
                } else {
                    setRealStatus('Timer complete — open the real page before claiming', 'gold');
                    showUnlockedToast('🔓 Timer finished — open the real page before claiming.');
                }

                updateClaim();
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
                    lockHint.textContent = '🔒 Finish the timer first; closing the tab or going back is allowed after that.';
                    window.clearTimeout(warnTimeout);
                    warnTimeout = window.setTimeout(function () {
                        lockHint.textContent = originalHint;
                    }, 3500);
                }
            }

            function setupYoutube() {
                var tag = document.createElement('script');
                tag.src = 'https://www.youtube.com/iframe_api';
                tag.async = true;
                document.head.appendChild(tag);

                window.onYouTubeIframeAPIReady = function () {
                    var frame = document.querySelector('[data-embed-player]');

                    if (!frame || !window.YT || !window.YT.Player) {
                        return;
                    }

                    new YT.Player(frame, {
                        events: {
                            onStateChange: function (event) {
                                if (event.data === YT.PlayerState.PLAYING) {
                                    if (!videoPlaying) {
                                        videoPlaying = true;
                                        opened = true;
                                        sendProgress(true);
                                    }
                                } else if (event.data === YT.PlayerState.PAUSED || event.data === YT.PlayerState.ENDED) {
                                    if (videoPlaying) {
                                        videoPlaying = false;
                                        sendProgress(false);
                                    }
                                }

                                updateEmbedStatus();
                                renderWatch();
                            }
                        }
                    });
                };
            }

            function setupFacebook() {
                window.fbAsyncInit = function () {
                    if (!window.FB) {
                        return;
                    }

                    FB.init({ xfbml: true, version: 'v27.0' });

                    FB.Event.subscribe('xfbml.ready', function (message) {
                        if (!message || message.type !== 'video' || !message.instance) {
                            return;
                        }

                        window.__taskFbReady = true;

                        var player = message.instance;

                        player.subscribe('startedPlaying', function () {
                            videoPlaying = true;
                            opened = true;
                            sendProgress(true);
                            updateEmbedStatus();
                            renderWatch();
                        });

                        player.subscribe('paused', function () {
                            if (videoPlaying) {
                                videoPlaying = false;
                                sendProgress(false);
                            }

                            updateEmbedStatus();
                            renderWatch();
                        });

                        player.subscribe('finishedPlaying', function () {
                            if (videoPlaying) {
                                videoPlaying = false;
                                sendProgress(false);
                            }

                            updateEmbedStatus();
                            renderWatch();
                        });
                    });
                };

                var tag = document.createElement('script');
                tag.async = true;
                tag.defer = true;
                tag.crossOrigin = 'anonymous';
                tag.src = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v27.0';
                document.body.appendChild(tag);

                window.setTimeout(function () {
                    if (window.__taskFbReady || !playHint) {
                        return;
                    }

                    playHint.textContent = 'The Facebook player did not load. Disable your ad blocker and reload the page.';
                }, 12000);
            }

            if (openButton) {
                openButton.addEventListener('click', openReal);
            }

            if (button) {
                var claimForm = button.closest('form');

                if (claimForm) {
                    claimForm.addEventListener('submit', function (event) {
                        if (locked || failed) {
                            event.preventDefault();

                            return;
                        }

                        if (embedded) {
                            if (watched < required) {
                                event.preventDefault();
                                renderWatch();
                                updateEmbedStatus();
                            }

                            return;
                        }
                    });
                }
            }

            document.addEventListener('visibilitychange', function () {
                if (!embedded) {
                    return;
                }

                if (document.hidden) {
                    if (videoPlaying) {
                        sendProgress(false);
                    }
                } else if (videoPlaying) {
                    sendProgress(true);
                }
            });

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

            window.addEventListener('pagehide', function () {
                if (!locked || failed) {
                    return;
                }

                failed = true;
                notifyServerFailure();
            });

            render();
            renderWatch();
            updateClaim();
            updateEmbedStatus();
            updateTimerPauseUi();

            if (embedded) {
                startHeartbeat();

                if (platform === 'youtube') {
                    setupYoutube();
                } else if (platform === 'facebook') {
                    setupFacebook();
                }
            }

            if (!locked) {
                if (embedded) {
                    updateEmbedStatus();
                } else if (opened) {
                    setOpenUi();
                    setRealStatus('Timer complete — claim now', 'mint');
                } else {
                    setRealStatus('Timer complete — open the real page before claiming', 'gold');
                }

                return;
            }

            try {
                history.pushState({ earningTaskLock: true }, '', window.location.href);
            } catch (error) {}

            if (window.__earningTaskTicker) {
                window.clearInterval(window.__earningTaskTicker);
            }

            window.__earningTaskTicker = window.setInterval(function () {
                if (timerPaused) {
                    render();

                    return;
                }

                remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
                render();

                if (remaining <= 0) {
                    stopTicker();
                    unlock();
                }
            }, 1000);

            ticker = window.__earningTaskTicker;
        })();
    </script>
@endpush
