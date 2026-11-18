@extends('layouts.earning')

@section('title', 'Wallet & payouts — '.config('earning.brand'))

@section('content')
    @php
        $tz = config('earning.timezone');
        $user = auth()->user();
        $balance = (float) $user->wallet_balance;
        $defaultAccount = $accounts->firstWhere('is_default', true) ?? $accounts->first();
        $providers = \App\Enums\PayoutProvider::cases();
        $cooldownHours = max(0, (int) $payoutCooldownHours);
        $nextPayoutAt = $cooldownHours > 0 && $latestPayout?->created_at
            ? $latestPayout->created_at->copy()->addHours($cooldownHours)
            : null;
        $payoutLocked = $nextPayoutAt?->isFuture() ?? false;
    @endphp

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">Wallet</p>
            <h1 class="mt-1.5 font-display text-3xl font-bold text-white sm:text-4xl">Wallet &amp; payouts</h1>
            <p class="mt-2 text-sm text-slate-400">Submit a withdrawal request to Easypaisa, JazzCash, UPaisa, or NayaPay.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('deposits.index') }}" class="rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20">Deposit money</a>
            <a href="{{ route('deposits.index') }}" class="rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10">Deposit history</a>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="relative overflow-hidden rounded-2xl border border-mint-400/25 bg-gradient-to-br from-mint-500/20 via-ink-850 to-ink-900 p-5">
            <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-mint-400/20 blur-2xl"></div>
            <p class="text-xs font-semibold uppercase tracking-wider text-mint-200/80">Available balance</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ \App\Support\Currency::coins($balance) }}</p>
            <p class="mt-2 text-xs text-slate-400">Withdrawal {{ \App\Support\Currency::coins($minimumPayout, false) }} – {{ \App\Support\Currency::coins($maximumPayout, false) }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pending payout</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ \App\Support\Currency::coins($pendingPayout) }}</p>
            <p class="mt-2 text-xs text-slate-400">Held until admin review</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total withdrawn</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ \App\Support\Currency::coins($totalWithdrawn) }}</p>
            <p class="mt-2 text-xs text-slate-400">Paid to your wallets</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Saved wallets</p>
            <p class="mt-2 font-display text-3xl font-bold text-white">{{ $accounts->count() }}<span class="text-lg text-slate-500">/10</span></p>
            <p class="mt-2 text-xs text-slate-400">{{ $defaultAccount?->displayLabel() ?? 'No wallet saved yet' }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-5">
        <div class="space-y-6 xl:col-span-2">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <h2 class="text-base font-bold text-white">Request a payout</h2>
                <p class="mt-1 text-xs text-slate-400">The amount will be held from your balance immediately. If rejected, it will be added back.</p>

                @if ($accounts->isEmpty())
                    <div class="mt-4 rounded-xl border border-gold-400/30 bg-gold-400/10 px-4 py-3 text-xs text-gold-200">
                        Save a wallet account below first, then you can submit a payout request.
                    </div>
                @else
                    <form method="POST" action="{{ route('wallet.payouts.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="payout_account_id" class="mb-1.5 block text-xs font-semibold text-slate-300">Send to wallet</label>
                            <select name="payout_account_id" id="payout_account_id" required
                                    class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('payout_account_id', $defaultAccount?->id) == $account->id)>
                                        {{ $account->provider->label() }} — {{ $account->account_number }} ({{ $account->account_name }}){{ $account->is_default ? ' · default' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="amount" class="mb-1.5 block text-xs font-semibold text-slate-300">Amount ({{ config('earning.currency') }})</label>
                            <input type="number" step="0.01" name="amount" id="amount" required
                                   min="{{ $minimumPayout }}" max="{{ $maximumPayout }}"
                                   value="{{ old('amount', number_format(min(max($minimumPayout, $balance), $maximumPayout), 2, '.', '')) }}"
                                   class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                            <p class="mt-1.5 text-[11px] text-slate-500">Available: {{ \App\Support\Currency::coins($balance) }} · Minimum: {{ \App\Support\Currency::coins($minimumPayout, false) }} · Maximum: {{ \App\Support\Currency::coins($maximumPayout, false) }}</p>
                        </div>

                        <div class="space-y-1.5 rounded-xl border border-gold-400/20 bg-gold-400/5 px-4 py-3 text-[11px]">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-400">Deducted from your wallet</span>
                                <span id="payout-gross" class="font-bold text-white">{{ \App\Support\Currency::coins($payoutQuote['gross']) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-400">Tax - fee ({{ number_format($payoutFeePercent, 0) }}%)</span>
                                <span id="payout-fee" class="font-bold text-gold-300">{{ \App\Support\Currency::coins($payoutQuote['fee']) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-t border-white/10 pt-1.5">
                                <span class="text-slate-300">Sent to your wallet account</span>
                                <span id="payout-net" class="font-bold text-mint-300">{{ \App\Support\Currency::coins($payoutQuote['net']) }}</span>
                            </div>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20 disabled:cursor-not-allowed disabled:opacity-50"
                                @disabled($balance < $minimumPayout || $payoutLocked)>
                            Request payout
                        </button>
                    </form>

                    @if ($balance < $minimumPayout)
                        <p class="mt-3 text-[11px] text-slate-500">You need at least {{ \App\Support\Currency::coins($minimumPayout, false) }} balance for a payout.</p>
                    @endif
                @endif

                <p class="mt-3 rounded-xl border border-mint-400/20 bg-mint-500/5 px-3 py-2.5 text-[11px] leading-relaxed text-slate-300">
                    <span class="font-bold text-mint-300">Withdrawal period: {{ $cooldownHours }} hours.</span>
                    After submitting one payout request, the next withdrawal {{ $cooldownHours }} hours later. Each withdrawal has a {{ number_format($payoutFeePercent, 0) }}% tax - fee deducted — the full amount is debited from your wallet, the fee goes to the admin, and the remaining amount is sent to your wallet account.
                </p>

                @if ($payoutLocked)
                    <p class="mt-2 rounded-xl border border-gold-400/30 bg-gold-400/10 px-3 py-2.5 text-[11px] leading-relaxed text-gold-200">
                        Your last request was {{ $cooldownHours }} hours ago, and that period is not complete yet. The next withdrawal {{ $nextPayoutAt->timezone($tz)->format('d M, h:i A') }} will be allowed after that.
                    </p>
                @endif
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-white">Add money</h2>
                        <p class="mt-1 text-xs text-slate-400">Deposit into your wallet via Cashmaal.</p>
                    </div>
                    <span class="rounded-lg bg-mint-500/15 px-2.5 py-1 text-[11px] font-bold text-mint-300">{{ \App\Support\Currency::coins($totalDeposited) }}</span>
                </div>

                <a href="{{ route('deposits.index') }}" class="mt-4 block rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-4 py-2.5 text-center text-sm font-bold text-ink-950">Deposit via Cashmaal</a>

                <div class="mt-4 space-y-2">
                    @forelse ($recentDeposits as $deposit)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-white/5 bg-ink-900/40 px-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate font-mono text-[11px] text-slate-300">{{ $deposit->order_id }}</p>
                                <p class="text-[10px] text-slate-500">{{ $deposit->created_at?->timezone($tz)->format('d M, h:i A') }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-xs font-bold text-white">{{ \App\Support\Currency::coins($deposit->credit_amount) }}</p>
                                <p class="text-[10px] font-semibold" style="color: {{ $deposit->status->hex() }}">{{ $deposit->status->label() }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-white/15 px-3 py-4 text-center text-[11px] text-slate-400">No deposits yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-white">Saved wallet accounts</h2>
                        <p class="mt-1 text-xs text-slate-400">Account name, number, and provider.</p>
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($accounts as $account)
                        <div class="rounded-xl border border-white/10 bg-ink-900/50 p-3.5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                              style="background: {{ $account->provider->color() }}22; color: {{ $account->provider->color() }}">{{ $account->provider->label() }}</span>
                                        @if ($account->is_default)
                                            <span class="rounded-md bg-mint-500/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-mint-300">Default</span>
                                        @endif
                                    </div>
                                    <p class="mt-1.5 truncate text-sm font-semibold text-white">{{ $account->account_name }}</p>
                                    <p class="text-xs text-slate-400">{{ $account->account_number }}</p>
                                </div>
                                <form method="POST" action="{{ route('wallet.accounts.destroy', $account) }}" onsubmit="return confirm('Delete this wallet account?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-400/25 bg-rose-500/10 px-2.5 py-1 text-[11px] font-semibold text-rose-300 hover:bg-rose-500/20">Delete</button>
                                </form>
                            </div>

                            <details class="group mt-2">
                                <summary class="cursor-pointer list-none text-[11px] font-semibold text-mint-300 hover:text-mint-200">Edit account ▾</summary>
                                <form method="POST" action="{{ route('wallet.accounts.update', $account) }}" class="mt-3 space-y-2.5 border-t border-white/10 pt-3">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid gap-2.5 sm:grid-cols-2">
                                        <select name="provider" required class="w-full rounded-lg border border-white/10 bg-ink-900 px-3 py-2 text-xs text-white focus:border-mint-400 focus:outline-none">
                                            @foreach ($providers as $provider)
                                                <option value="{{ $provider->value }}" @selected($account->provider === $provider)>{{ $provider->label() }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="account_name" required maxlength="120" value="{{ $account->account_name }}" placeholder="Account holder name"
                                               class="w-full rounded-lg border border-white/10 bg-ink-900 px-3 py-2 text-xs text-white focus:border-mint-400 focus:outline-none">
                                    </div>
                                    <input type="text" name="account_number" required maxlength="30" value="{{ $account->account_number }}" placeholder="03XX-XXXXXXX"
                                           class="w-full rounded-lg border border-white/10 bg-ink-900 px-3 py-2 text-xs text-white focus:border-mint-400 focus:outline-none">
                                    <label class="flex items-center gap-2 text-[11px] text-slate-300">
                                        <input type="checkbox" name="is_default" value="1" @checked($account->is_default) class="rounded border-white/20 bg-ink-900 text-mint-400 focus:ring-mint-400">
                                        Make default wallet
                                    </label>
                                    <button type="submit" class="rounded-lg bg-white/10 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-white/15">Update account</button>
                                </form>
                            </details>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-white/15 px-4 py-6 text-center text-xs text-slate-400">No wallet account saved yet.</p>
                    @endforelse
                </div>

                @if ($accounts->count() < 10)
                    <details class="mt-4 rounded-xl border border-white/10 bg-ink-900/40 p-3.5">
                        <summary class="cursor-pointer list-none text-sm font-bold text-mint-300 hover:text-mint-200">+ Add wallet account</summary>
                        <form method="POST" action="{{ route('wallet.accounts.store') }}" class="mt-3 space-y-2.5">
                            @csrf
                            <div class="grid gap-2.5 sm:grid-cols-2">
                                <select name="provider" required class="w-full rounded-lg border border-white/10 bg-ink-900 px-3 py-2 text-xs text-white focus:border-mint-400 focus:outline-none">
                                    <option value="" disabled @selected(! old('provider'))>Select wallet</option>
                                    @foreach ($providers as $provider)
                                        <option value="{{ $provider->value }}" @selected(old('provider') === $provider->value)>{{ $provider->label() }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="account_name" required maxlength="120" value="{{ old('account_name') }}" placeholder="Account holder name"
                                       class="w-full rounded-lg border border-white/10 bg-ink-900 px-3 py-2 text-xs text-white focus:border-mint-400 focus:outline-none">
                            </div>
                            <input type="text" name="account_number" required maxlength="30" value="{{ old('account_number') }}" placeholder="03XX-XXXXXXX"
                                   class="w-full rounded-lg border border-white/10 bg-ink-900 px-3 py-2 text-xs text-white focus:border-mint-400 focus:outline-none">
                            <label class="flex items-center gap-2 text-[11px] text-slate-300">
                                <input type="checkbox" name="is_default" value="1" class="rounded border-white/20 bg-ink-900 text-mint-400 focus:ring-mint-400">
                                Make default wallet
                            </label>
                            <button type="submit" class="rounded-lg bg-gradient-to-r from-mint-400 to-mint-500 px-3.5 py-2 text-[11px] font-bold text-ink-950">Save wallet</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>

        <div class="space-y-6 xl:col-span-3">
            <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                <div class="border-b border-white/10 px-5 py-4">
                    <h2 class="text-base font-bold text-white">Payout requests</h2>
                    <p class="text-xs text-slate-400">Status and admin notes</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-white/10 bg-white/[.03] text-[10px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-5 py-3 font-semibold">#</th>
                                <th class="px-5 py-3 font-semibold">Wallet</th>
                                <th class="px-5 py-3 text-right font-semibold">Payout</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 font-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($payouts as $payout)
                                <tr class="transition hover:bg-white/[.03]">
                                    <td class="px-5 py-3.5 text-xs text-slate-400">#{{ $payout->id }}</td>
                                    <td class="px-5 py-3.5">
                                        <p class="text-sm font-semibold text-white">{{ $payout->provider->label() }}</p>
                                        <p class="text-[11px] text-slate-500">{{ $payout->account_number }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                        <p class="font-bold text-white">{{ \App\Support\Currency::coins($payout->amount) }}</p>
                                        <p class="text-[10px] text-slate-500">Wallet cut {{ \App\Support\Currency::coins($payout->grossAmount()) }}</p>
                                        @if ($payout->feeAmount() > 0)
                                            <p class="text-[10px] text-gold-300/80">Tax - fee {{ \App\Support\Currency::coins($payout->feeAmount()) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5">
                                        @php
                                            $statusClasses = match ($payout->status) {
                                                \App\Enums\PayoutStatus::Paid => 'border-mint-400/30 bg-mint-500/15 text-mint-300',
                                                \App\Enums\PayoutStatus::Rejected => 'border-rose-400/30 bg-rose-500/15 text-rose-300',
                                                default => 'border-gold-400/30 bg-gold-400/15 text-gold-300',
                                            };
                                        @endphp
                                        <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $statusClasses }}">{{ $payout->status->label() }}</span>
                                        @if (filled($payout->admin_notes))
                                            <p class="mt-1 max-w-[180px] text-[10px] leading-snug text-slate-500">{{ $payout->admin_notes }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-400">{{ $payout->created_at?->timezone($tz)->format('d M, h:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No payout request submitted yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($payouts->hasPages())
                    <div class="border-t border-white/10 px-5 py-3">{{ $payouts->links() }}</div>
                @endif
            </div>

            <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                    <div>
                        <h2 class="text-base font-bold text-white">Recent wallet activity</h2>
                        <p class="text-xs text-slate-400">Latest 12 transactions</p>
                    </div>
                    <a href="{{ route('earnings.index') }}" class="text-xs font-semibold text-mint-300 hover:text-mint-200">Full history →</a>
                </div>
                <div class="divide-y divide-white/5">
                    @forelse ($transactions as $transaction)
                        <div class="flex items-center gap-3 px-5 py-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-black {{ $transaction->isCredit() ? 'bg-mint-500/15 text-mint-300' : 'bg-rose-500/15 text-rose-300' }}">
                                {{ $transaction->isCredit() ? '+' : '−' }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-white">{{ $transaction->type->label() }}</p>
                                <p class="truncate text-[11px] text-slate-500">{{ $transaction->description }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-bold {{ $transaction->isCredit() ? 'text-mint-300' : 'text-rose-300' }}">{{ \App\Support\Currency::signedCoins($transaction->amount, $transaction->isCredit()) }}</p>
                                <p class="text-[10px] text-slate-500">{{ $transaction->created_at?->timezone($tz)->format('d M, h:i A') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-slate-400">No wallet transactions.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var amountInput = document.getElementById('amount');
            var grossOut = document.getElementById('payout-gross');
            var feeOut = document.getElementById('payout-fee');
            var netOut = document.getElementById('payout-net');

            if (!amountInput || !grossOut || !feeOut || !netOut) {
                return;
            }

            var feePercent = {{ (float) $payoutFeePercent }};

            function money(value) {
                return 'Coins ' + value.toFixed(2);
            }

            function update() {
                var gross = Math.max(0, parseFloat(amountInput.value) || 0);
                var fee = Math.round(gross * feePercent) / 100;

                grossOut.textContent = money(gross);
                feeOut.textContent = money(fee);
                netOut.textContent = money(Math.max(0, gross - fee));
            }

            amountInput.addEventListener('input', update);
            update();
        })();
    </script>
@endpush
