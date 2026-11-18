@extends('layouts.earning')

@section('title', 'Deposit status — '.config('earning.brand'))

@section('content')
    @php
        $tz = config('earning.timezone');
    @endphp

    <div class="mx-auto max-w-lg">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6 text-center sm:p-8">
            @if ($isSuccess)
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-mint-500/20 text-2xl text-mint-300">✓</div>
                <h1 class="mt-4 font-display text-2xl font-bold text-white">Deposit successful</h1>
                <p class="mt-2 text-sm text-slate-400">Your payment was confirmed and added to your wallet.</p>
            @elseif ($isCancelled)
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gold-400/15 text-2xl text-gold-300">!</div>
                <h1 class="mt-4 font-display text-2xl font-bold text-white">Payment cancelled</h1>
                <p class="mt-2 text-sm text-slate-400">The Cashmaal payment was cancelled. No money was deducted from your wallet.</p>
            @else
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gold-400/15 text-2xl text-gold-300">⏳</div>
                <h1 class="mt-4 font-display text-2xl font-bold text-white">Checking payment</h1>
                <p class="mt-2 text-sm text-slate-400">
                    Cashmaal has not sent confirmation yet. If the money was deducted, your wallet will update shortly — keep refreshing the page.
                </p>
            @endif

            @if ($deposit)
                <dl class="mt-5 space-y-2 rounded-2xl border border-white/10 bg-ink-900/50 p-4 text-left text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Order</dt>
                        <dd class="font-mono text-xs text-slate-200">{{ $deposit->order_id }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</dt>
                        <dd class="font-bold text-white">{{ \App\Support\Currency::format($deposit->amount) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status</dt>
                        <dd class="font-semibold" style="color: {{ $deposit->status->hex() }}">{{ $deposit->status->label() }}</dd>
                    </div>
                    @if ($deposit->cm_tid)
                        <div class="flex items-center justify-between">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Cashmaal TID</dt>
                            <dd class="font-mono text-xs text-slate-200">{{ $deposit->cm_tid }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Date</dt>
                        <dd class="text-xs text-slate-400">{{ $deposit->created_at?->timezone($tz)->format('d M Y, h:i A') }}</dd>
                    </div>
                </dl>
            @endif

            <div class="mt-6 flex flex-col gap-2 sm:flex-row">
                @if ($withdrawalsEnabled)
                    <a href="{{ route('wallet.index') }}" class="flex-1 rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950">Open wallet</a>
                @endif
                <a href="{{ route('deposits.index') }}" class="flex-1 rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10">Deposit again</a>
            </div>
        </div>
    </div>
@endsection
