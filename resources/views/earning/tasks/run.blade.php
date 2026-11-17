@extends('layouts.earning')

@section('title', 'Running task — '.config('earning.brand'))

@section('content')
    @php
        $totalSeconds = max(1, (int) $task->duration_seconds);
        $remaining = max(0, (int) $remainingSeconds);
        $ready = $remaining === 0;
        $isEmbedded = (bool) ($embedded ?? false);
        $watchRequired = $isEmbedded ? max(1, (int) $requiredSeconds) : 0;
        $watched = $isEmbedded ? min($watchRequired, (int) $watchedSeconds) : 0;
        $watchReady = ! $isEmbedded || $watched >= $watchRequired;
        $claimable = $ready && $watchReady && ($isEmbedded || $realPageOpened);
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
                                    {{ $ready && $watchReady ? 'Timer complete — ab claim karein' : 'Video play karein — watch time shuru karein' }}
                                @else
                                    {{ $ready ? 'Timer complete — ab claim karein' : 'Real page kholein — timer chalu hai' }}
                                @endif
                            </span>
                        </p>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300">
                            @if ($isEmbedded)
                                Video neeche official {{ $task->platform() }} player me chal rahi hai. Play button dabaein — watch time isi page par count hota hai. Timer poora hone aur required watch time mukammal hone par reward claim karein.
                            @else
                                Task ka asli page naye tab me khulta hai. Wahan video play karein, like ya subscribe karein — YouTube/Facebook par views, watch time, likes aur subscribes real time count hote hain.
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
                            Video play button dabaein. Play na karne par watch time count nahi hoga aur claim band rahega.
                        </p>
                    </div>

                    @if ($embedPlatform === 'youtube')
                        <p class="mt-3 rounded-xl border border-gold-400/25 bg-gold-400/5 px-3 py-2 text-[11px] leading-relaxed text-gold-200/90">
                            💡 Monetized videos me ads YouTube apne rules ke mutabiq chalata hai. Creator ke liye zaroori hai ke video monetized ho aur embedding allowed ho; viewer YouTube Premium ya ad-blocker use na kare. Hum ads force nahi kar sakte aur paid/incentivized views ko YouTube invalid traffic mana sakta hai.
                        </p>
                    @endif

                    <p class="mt-3 rounded-xl border border-gold-400/25 bg-gold-400/5 px-3 py-2 text-[11px] leading-relaxed text-gold-200/90">
                        🔒 Timer poora hone tak tab band karna, reload karna aur back jana band hai. Video pause karne par timer chalta rahega lekin watch time nahi badhega.
                    </p>
                @else
                    <p class="mt-3 rounded-xl border border-gold-400/25 bg-gold-400/5 px-3 py-2 text-[11px] leading-relaxed text-gold-200/90" data-real-hint>
                        🔒 Real page ka tab timer poora hone se pehle band kiya to task <span class="font-bold">FAIL</span> ho jayega aur reward nahi milega. Us tab ko khula rakhein.
                    </p>
                @endif

                <div data-fail-banner class="mt-3 hidden rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-3">
                    <p class="text-sm font-bold text-rose-200">❌ Task fail ho gaya</p>
                    <p class="mt-1 text-[11px] leading-relaxed text-rose-100/80">
                        @if ($isEmbedded)
                            Timer poora hone se pehle yeh tab band kar diya gaya. Is task ka reward nahi milega — dobara start karein aur video required waqt tak play rakhein.
                        @else
                            Real page ka tab timer poora hone se pehle band kar diya gaya. Is task ka reward nahi milega — dobara start karein.
                        @endif
                    </p>
                    <a href="{{ route('tasks.index') }}" class="mt-2.5 inline-flex rounded-lg bg-rose-500 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-rose-400">Dobara start karein →</a>
                </div>
            </div>

            <div class="border-t border-white/10 px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-mint-300" data-timer-status>{{ $ready ? 'Ready to claim' : 'Seconds remaining' }}</p>
                        <p class="mt-1 font-display text-4xl font-bold tabular-nums text-white {{ $ready ? '' : 'hidden' }}" data-timer-display>{{ gmdate('i:s', $remaining) }}</p>
                        @if (! $ready)
                            <p class="mt-1 text-sm font-semibold text-gold-300" data-timer-lock-note>Timer neeche lock bar me chal raha hai</p>
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
                            {{ $claimable ? 'Claim '.config('earning.currency').' '.number_format((float) $expectedReward, 2) : ($ready ? ($isEmbedded ? 'Video '.(max(0, $watchRequired - $watched)).'s aur dekhein' : 'Pehle real page kholein') : 'Please wait…') }}
                        </button>
                    </form>
                </div>

                <p class="mt-4 text-xs leading-relaxed text-slate-500">
                    @if ($isEmbedded)
                        Is tab me timer chal raha hai aur isi tab ka title bar seconds dikhata hai. Timer poora hone tak yeh tab band karna aur back jana band rahega. Video play karte waqt hi watch time count hota hai — pause karne par watch time ruk jata hai.
                    @else
                        Is tab me timer chal raha hai aur isi tab ka title bar seconds dikhata hai. Timer poora hone tak yeh tab band karna aur back jana band rahega — real page alag tab me hai, use bhi band na karein.
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
            var popup = null;
            var deadline = Date.now() + remaining * 1000;
            var ticker = null;
            var watcher = null;
            var heartbeat = null;
            var videoPlaying = false;

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
            var lockCountdown = document.querySelector('[data-lock-countdown]');
            var lockFill = document.querySelector('[data-lock-bar]');
            var lockHint = document.querySelector('[data-lock-hint]');
            var inlineBack = document.querySelector('[data-lock-inline-back]');

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

            function progress() {
                return Math.min(100, Math.round(((total - remaining) / total) * 100));
            }

            function post(url, payload) {
                try {
                    fetch(url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        keepalive: true,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload || {})
                    }).catch(function () {});
                } catch (error) {}
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
                        body: JSON.stringify({ playing: !!playing })
                    }).then(function (response) {
                        return response.ok ? response.json() : null;
                    }).then(function (data) {
                        if (!data || !data.ok) {
                            return;
                        }

                        if (typeof data.required_seconds === 'number' && data.required_seconds > 0) {
                            required = data.required_seconds;
                        }

                        if (typeof data.watched_seconds === 'number') {
                            watched = Math.max(watched, Math.min(required, data.watched_seconds));
                        }

                        renderWatch();
                        updateClaim();
                        updateEmbedStatus();
                    }).catch(function () {});
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
                    setRealStatus('Timer complete — ab claim karein', 'mint');

                    return;
                }

                if (finished) {
                    setRealStatus('Timer complete — video ' + Math.max(0, required - watched) + 's aur play karein', 'gold');

                    return;
                }

                if (videoPlaying) {
                    setRealStatus('Video chal rahi hai — watch time count ho raha hai', 'mint');

                    return;
                }

                if (opened) {
                    setRealStatus('Video paused — play karein, watch time ruk gaya hai', 'gold');

                    return;
                }

                setRealStatus('Video play button dabaein — watch time shuru karein', 'gold');
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
                        playHint.textContent = 'Timer poora ho gaya, lekin video abhi ' + Math.max(0, required - watched) + ' seconds aur play karni hai.';
                    } else if (videoPlaying) {
                        playHint.textContent = 'Video play ho rahi hai — watch time count ho raha hai. Yeh tab khula rakhein.';
                    } else if (opened) {
                        playHint.textContent = 'Video paused hai — play dabaein, watch time dobara shuru hoga.';
                    } else {
                        playHint.textContent = 'Video play button dabaein. Play na karne par watch time count nahi hoga.';
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
                } else if (!embedded && finished && !opened) {
                    button.textContent = 'Pehle real page kholein';
                } else if (embedded && finished && !watchReady) {
                    button.textContent = 'Video ' + Math.max(0, required - watched) + 's aur dekhein';
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
                    openButton.textContent = popup ? 'Real page khula hai ↗' : 'Real page dobara kholein ↗';
                }

                if (realHint) {
                    realHint.textContent = '🔒 Real page ka tab band mat karein — timer poora hone se pehle band karne par task FAIL ho jayega.';
                }

                setRealStatus(
                    finished ? 'Timer complete — ab claim karein' : 'Real page khula hai — timer chal raha hai',
                    'mint'
                );

                updateClaim();
            }

            function openReal() {
                if (failed) {
                    return;
                }

                if (popup && popup.closed === false) {
                    try {
                        popup.focus();
                    } catch (error) {}

                    return;
                }

                var win = window.open(targetUrl, '_blank');

                if (!win) {
                    setRealStatus('Popup block ho gaya — "Open real page" dobara dabaein', 'gold');

                    if (realHint) {
                        realHint.textContent = '⚠️ Browser ne naya tab block kiya. Address bar ke popup icon se allow karein, phir "Open real page" dobara dabaein.';
                    }

                    return;
                }

                popup = win;

                try {
                    win.opener = null;
                } catch (error) {}

                opened = true;
                post(openedUrl);
                setOpenUi();
                startWatcher();
            }

            function checkPopup() {
                if (!popup) {
                    return;
                }

                if (!popup.closed) {
                    return;
                }

                popup = null;
                stopWatcher();

                if (!finished && !failed) {
                    failTask();

                    return;
                }

                setRealStatus('Real page band ho gaya — task mukammal', 'mint');
                if (openButton) {
                    openButton.textContent = 'Open real page ↗';
                }
            }

            function startWatcher() {
                if (watcher) {
                    return;
                }

                watcher = window.setInterval(checkPopup, 1500);
            }

            function stopWatcher() {
                if (!watcher) {
                    return;
                }

                window.clearInterval(watcher);
                watcher = null;
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

                stopTicker();
                syncTimerDisplay();

                stopWatcher();

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
                    embedded ? 'Task fail — timer se pehle tab band ho gaya' : 'Task fail — real page tab timer se pehle band hua',
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
                window.clearTimeout(warnTimeout);
                stopTicker();
                syncTimerDisplay();
                stopWatcher();

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
                        showUnlockedToast('⏳ Timer poora — video abhi ' + Math.max(0, required - watched) + ' seconds aur play karein.');
                    } else {
                        showUnlockedToast('🔓 Timer poora — ab aap reward claim kar sakte hain.');
                    }
                } else if (opened) {
                    setRealStatus('Timer complete — ab claim karein', 'mint');
                    showUnlockedToast('🔓 Timer poora — ab aap tab band kar sakte hain ya back ja sakte hain.');
                } else {
                    setRealStatus('Timer complete — claim se pehle real page kholein', 'gold');
                    showUnlockedToast('🔓 Timer poora — claim se pehle real page kholein.');
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
                    lockHint.textContent = '🔒 Pehle timer poora karein, phir tab band ya back allowed hoga.';
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

                    playHint.textContent = 'Facebook player load nahi hua. Ad-blocker band karke page reload karein.';
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

                        checkPopup();

                        if (failed) {
                            event.preventDefault();
                        }
                    });
                }
            }

            document.addEventListener('visibilitychange', function () {
                if (!embedded) {
                    if (!document.hidden && popup) {
                        checkPopup();
                    }

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
                    setRealStatus('Timer complete — ab claim karein', 'mint');
                } else {
                    setRealStatus('Timer complete — claim se pehle real page kholein', 'gold');
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
