@extends('layouts.earning')

@section('title', 'Deposit — '.config('earning.brand'))

@section('content')
    @php
        $tz = config('earning.timezone');
    @endphp

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">Deposit</p>
            <h1 class="mt-1.5 font-display text-3xl font-bold text-white sm:text-4xl">Add money to your wallet</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">
                Deposit online via Cashmaal. The admin fee is payable separately — {{ number_format($adminFeePercent, 0) }}% of the amount you pay is added to the total, and only your amount is credited to your wallet.
            </p>
        </div>
        <div class="rounded-2xl border border-mint-400/25 bg-mint-500/10 px-4 py-3 text-right">
            <p class="text-[10px] font-bold uppercase tracking-wider text-mint-200/80">Wallet balance</p>
            <p class="font-display text-lg font-bold text-white">{{ \App\Support\Currency::format($walletBalance) }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="relative overflow-hidden rounded-2xl border border-mint-400/25 bg-gradient-to-br from-mint-500/20 via-ink-850 to-ink-900 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-mint-200/80">Available balance</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ \App\Support\Currency::format($walletBalance) }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total deposited</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ \App\Support\Currency::format($totalDeposited) }}</p>
            <p class="mt-2 text-xs text-slate-400">Completed Cashmaal deposits</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Deposit limits</p>
            <p class="mt-2 font-display text-2xl font-bold text-white">{{ \App\Support\Currency::format($minimum, false) }}</p>
            <p class="mt-2 text-xs text-slate-400">Maximum {{ \App\Support\Currency::format($maximum, false) }} · Admin fee {{ number_format($adminFeePercent, 0) }}%</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-5">
        <div class="xl:col-span-2">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <h2 class="text-base font-bold text-white">New deposit</h2>
                <p class="mt-1 text-xs text-slate-400">Enter the amount and complete the payment on Cashmaal.</p>

                @if (! $cashmaalEnabled)
                    <div class="mt-4 rounded-xl border border-gold-400/30 bg-gold-400/10 px-4 py-3 text-xs text-gold-200">
                        Cashmaal deposits are not available right now. Contact the admin or try again later.
                    </div>
                @else
                    <form method="POST" action="{{ route('deposits.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="amount" class="mb-1.5 block text-xs font-semibold text-slate-300">Amount ({{ config('earning.currency') }})</label>
                            <input type="number" step="1" name="amount" id="amount" required
                                   min="{{ $minimum }}" max="{{ $maximum }}"
                                   value="{{ old('amount', number_format($minimum, 0, '.', '')) }}"
                                   class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                            <p class="mt-1.5 text-[11px] text-slate-500">Minimum {{ \App\Support\Currency::format($minimum, false) }} · Maximum {{ \App\Support\Currency::format($maximum, false) }}</p>
                        </div>

                        <div class="space-y-1.5 rounded-xl border border-gold-400/20 bg-gold-400/5 px-4 py-3 text-[11px]">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-400">Credited to your wallet</span>
                                <span id="deposit-net" class="font-bold text-white">{{ \App\Support\Currency::format($depositQuote['amount']) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-400">Admin fee ({{ number_format($adminFeePercent, 0) }}%)</span>
                                <span id="deposit-fee" class="font-bold text-gold-300">{{ \App\Support\Currency::format($depositQuote['fee']) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-t border-white/10 pt-1.5">
                                <span class="text-slate-300">Total to pay on Cashmaal</span>
                                <span id="deposit-total" class="font-bold text-mint-300">{{ \App\Support\Currency::format($depositQuote['total']) }}</span>
                            </div>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20">
                            Continue to Cashmaal →
                        </button>
                    </form>
                @endif

                <div class="mt-4 rounded-xl border border-white/10 bg-ink-900/40 px-4 py-3 text-[11px] leading-relaxed text-slate-400">
                    <p class="font-semibold text-slate-300">Note</p>
                    <p class="mt-1">Confirmation may take a few seconds. If your wallet does not update, refresh this page shortly.</p>
                </div>
            </div>
        </div>

        <div class="xl:col-span-3">
            <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                <div class="border-b border-white/10 px-5 py-4">
                    <h2 class="text-base font-bold text-white">Deposit history</h2>
                    <p class="text-xs text-slate-400">Cashmaal orders, amounts, and status</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-white/10 bg-white/[.03] text-[10px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Order</th>
                                <th class="px-5 py-3 text-right font-semibold">Amount</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 font-semibold">Date</th>
                                <th class="px-5 py-3 text-right font-semibold"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($deposits as $deposit)
                                <tr class="transition hover:bg-white/[.03]">
                                    <td class="px-5 py-3.5">
                                        <p class="font-mono text-xs font-semibold text-white">{{ $deposit->order_id }}</p>
                                        <p class="text-[10px] text-slate-500">{{ $deposit->cm_tid ? 'TID '.$deposit->cm_tid : 'Cashmaal' }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                        <p class="text-sm font-bold text-white">{{ \App\Support\Currency::format($deposit->amount) }}</p>
                                        <p class="text-[10px] text-slate-500">Credited {{ \App\Support\Currency::format($deposit->credit_amount) }}</p>
                                        @if ((float) $deposit->fee > 0)
                                            <p class="text-[10px] text-gold-300/80">Admin fee {{ \App\Support\Currency::format($deposit->fee) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                              style="border-color: {{ $deposit->status->hex() }}55; background: {{ $deposit->status->hex() }}18; color: {{ $deposit->status->hex() }}">{{ $deposit->status->label() }}</span>
                                        @if (filled($deposit->admin_notes))
                                            <p class="mt-1 max-w-[170px] text-[10px] leading-snug text-slate-500">{{ $deposit->admin_notes }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-400">{{ $deposit->created_at?->timezone($tz)->format('d M, h:i A') }}</td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                        @if ($deposit->isPending())
                                            <a href="{{ route('deposits.pay', $deposit) }}" class="rounded-lg border border-mint-400/30 bg-mint-500/10 px-2.5 py-1 text-[11px] font-semibold text-mint-300 hover:bg-mint-500/20">Pay now</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No deposits yet. Make your first deposit above.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($deposits->hasPages())
                    <div class="border-t border-white/10 px-5 py-3">{{ $deposits->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var amountInput = document.getElementById('amount');
            var netOut = document.getElementById('deposit-net');
            var feeOut = document.getElementById('deposit-fee');
            var totalOut = document.getElementById('deposit-total');

            if (!amountInput || !netOut || !feeOut || !totalOut) {
                return;
            }

            var feePercent = {{ (float) $adminFeePercent }};

            function money(value) {
                return 'PKR ' + value.toFixed(2);
            }

            function update() {
                var net = Math.max(0, parseFloat(amountInput.value) || 0);
                var fee = Math.round(net * feePercent) / 100;

                netOut.textContent = money(net);
                feeOut.textContent = money(fee);
                totalOut.textContent = money(net + fee);
            }

            amountInput.addEventListener('input', update);
            update();
        })();
    </script>
@endpush
