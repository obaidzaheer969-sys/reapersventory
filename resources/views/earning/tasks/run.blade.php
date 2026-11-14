@extends('layouts.earning')

@section('title', 'Running task — '.config('earning.brand'))

@section('content')
    @php
        $totalSeconds = max(1, (int) $task->duration_seconds);
        $remaining = max(0, (int) $remainingSeconds);
        $ready = $remaining === 0;
    @endphp

    <div class="mx-auto max-w-3xl">
        <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white">← Back to tasks</a>

        <div class="mt-4 overflow-hidden rounded-3xl border border-white/10 bg-white/5">
            <div class="border-b border-white/10 bg-gradient-to-r from-ink-850 via-ink-900 to-ink-850 px-6 py-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl font-black"
                          style="background: {{ $task->task_type->color() }}22; color: {{ $task->task_type->color() }}">{{ $task->task_type->icon() }}</span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $task->platform() }} · {{ $task->task_type->label() }}</p>
                        <h1 class="truncate font-display text-xl font-bold text-white">{{ $task->title }}</h1>
                    </div>
                    <span class="ml-auto shrink-0 rounded-xl bg-mint-500/15 px-3 py-1.5 text-sm font-bold text-mint-300">{{ \App\Support\Currency::format($expectedReward) }}</span>
                </div>
            </div>

            <div class="px-6 py-8 text-center">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">{{ $ready ? 'Timer complete' : 'Stay on the task' }}</p>

                <div class="mx-auto mt-4 flex h-48 w-48 items-center justify-center rounded-full border border-white/10 bg-ink-950/60"
                     data-timer-ring style="--progress: {{ $ready ? 100 : round((($totalSeconds - $remaining) / $totalSeconds) * 100) }}%">
                    <div>
                        <p class="font-display text-5xl font-bold tabular-nums text-white" data-timer-display>{{ gmdate('i:s', $remaining) }}</p>
                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $ready ? 'Ready to claim' : 'seconds remaining' }}</p>
                    </div>
                </div>

                <div class="mx-auto mt-6 h-2 w-full max-w-sm overflow-hidden rounded-full bg-white/10">
                    <div class="h-full rounded-full bg-gradient-to-r from-mint-400 to-mint-500 transition-[width] duration-1000 ease-linear"
                         data-timer-bar style="width: {{ $ready ? 100 : round((($totalSeconds - $remaining) / $totalSeconds) * 100) }}%"></div>
                </div>

                <div class="mt-6 flex flex-col items-center justify-center gap-2 sm:flex-row">
                    <a href="{{ $task->target_url }}" target="_blank" rel="noopener noreferrer"
                       class="w-full rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10 sm:w-auto">
                        Open task link again ↗
                    </a>
                    <form method="POST" action="{{ route('tasks.complete', $task) }}" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" data-claim-button
                                class="w-full rounded-xl px-5 py-2.5 text-sm font-bold transition {{ $ready ? 'bg-gradient-to-r from-mint-400 to-mint-500 text-ink-950 shadow-lg shadow-mint-500/20' : 'cursor-not-allowed bg-white/5 text-slate-500' }}"
                                @disabled(! $ready)>
                            {{ $ready ? 'Claim '.config('earning.currency').' '.number_format((float) $expectedReward, 2) : 'Please wait…' }}
                        </button>
                    </form>
                </div>

                <p class="mx-auto mt-5 max-w-md text-xs leading-relaxed text-slate-500">
                    Task ko naye tab me poora karein aur is tab ko band na karein. Timer server par bhi check hota hai — jaldi claim karne ki koshish kaam nahi karegi.
                </p>
            </div>

            @if (filled($task->instructions))
                <div class="border-t border-white/10 bg-ink-900/40 px-6 py-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Instructions</p>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-300">{{ $task->instructions }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var remaining = {{ $remaining }};
            var total = {{ $totalSeconds }};
            var display = document.querySelector('[data-timer-display]');
            var bar = document.querySelector('[data-timer-bar]');
            var ring = document.querySelector('[data-timer-ring]');
            var button = document.querySelector('[data-claim-button]');
            var label = 'seconds remaining';

            function render() {
                if (!display) {
                    return;
                }

                var minutes = Math.floor(remaining / 60);
                var seconds = remaining % 60;
                display.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

                var done = total - remaining;
                var percent = Math.min(100, Math.round((done / total) * 100));
                if (bar) {
                    bar.style.width = percent + '%';
                }
                if (ring) {
                    ring.style.setProperty('--progress', percent + '%');
                }
            }

            function unlock() {
                if (button) {
                    button.disabled = false;
                    button.classList.remove('cursor-not-allowed', 'bg-white/5', 'text-slate-500');
                    button.classList.add('bg-gradient-to-r', 'from-mint-400', 'to-mint-500', 'text-ink-950', 'shadow-lg', 'shadow-mint-500/20');
                    button.textContent = 'Claim {{ config('earning.currency') }} {{ number_format((float) $expectedReward, 2) }}';
                }
                var status = display?.parentElement?.querySelector('p:last-child');
                if (status) {
                    status.textContent = 'Ready to claim';
                }
            }

            render();

            if (remaining <= 0) {
                unlock();
                return;
            }

            var timer = setInterval(function () {
                remaining -= 1;

                if (remaining <= 0) {
                    remaining = 0;
                    clearInterval(timer);
                    render();
                    unlock();
                    return;
                }

                render();
            }, 1000);
        })();
    </script>
@endpush
