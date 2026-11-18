@extends('layouts.earning')

@section('title', 'Create campaign — '.config('earning.brand'))

@section('content')
    @php
        $minQuantity = (int) config('earning.campaign.min_quantity', 100);
        $maxQuantity = (int) config('earning.campaign.max_quantity', 100000);
        $minSeconds = (int) config('earning.campaign.video_min_seconds', 60);
        $maxSeconds = (int) config('earning.campaign.video_max_seconds', 3600);
        $stepSeconds = max(1, (int) config('earning.campaign.video_step_seconds', 60));

        $durations = [];
        for ($seconds = $minSeconds; $seconds <= $maxSeconds; $seconds += $stepSeconds) {
            $durations[] = $seconds;
        }

        $selectedType = old('task_type', array_key_first($types) ?? '');
        $watchMap = collect($types)
            ->mapWithKeys(fn ($label, $value) => [$value => \App\Enums\TaskType::from($value)->isWatchTask()])
            ->all();
    @endphp

    <a href="{{ route('campaigns.mine') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white">← My campaigns</a>

    <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-mint-300">Create campaign</p>
            <h1 class="mt-1.5 font-display text-3xl font-bold text-white sm:text-4xl">Publish your task</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">
                The full campaign budget is held from your wallet immediately and the campaign goes live at once — no admin approval needed. If you cancel, the unused budget returns to your wallet.
            </p>
        </div>
        <div class="rounded-2xl border border-mint-400/25 bg-mint-500/10 px-4 py-3 text-right">
            <p class="text-[10px] font-bold uppercase tracking-wider text-mint-200/80">Wallet balance</p>
            <p class="font-display text-lg font-bold text-white">{{ \App\Support\Currency::coins($walletBalance) }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <form id="campaign-form" method="POST" action="{{ route('campaigns.store') }}" class="space-y-5 rounded-2xl border border-white/10 bg-white/5 p-5">
                @csrf

                <div>
                    <label for="task_type" class="mb-1.5 block text-xs font-semibold text-slate-300">Platform &amp; task</label>
                    <select name="task_type" id="task_type" required
                            class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($campaignAdminFeeVisible)
                        <p class="mt-1.5 text-[11px] text-slate-500">Each action (subscribe, follow, like, or video view) pays the earner 2 Coins and the admin 1 Coin — 3 Coins total per action. You set the quantity — an order for 2 subscribers or 1 view is allowed too.</p>
                    @else
                        <p class="mt-1.5 text-[11px] text-slate-500">Each action (subscribe, follow, like, or video view) pays the earner 2 Coins. You set the quantity — an order for 2 subscribers or 1 view is allowed too.</p>
                    @endif
                </div>

                <div>
                    <label for="title" class="mb-1.5 block text-xs font-semibold text-slate-300">Campaign title</label>
                    <input type="text" name="title" id="title" required maxlength="255" value="{{ old('title') }}"
                           placeholder="e.g. New vlog release — grow views"
                           class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                </div>

                <div>
                    <label for="target_url" class="mb-1.5 block text-xs font-semibold text-slate-300">Video / page / channel URL</label>
                    <input type="url" name="target_url" id="target_url" required maxlength="2048" value="{{ old('target_url') }}"
                           placeholder="https://..."
                           class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                    <p class="mt-1.5 text-[11px] text-slate-500">YouTube/Facebook video watch tasks run in the official player on this locked page; subscribe, follow, and like links open in a new tab.</p>
                    <p class="mt-1.5 text-[11px] leading-relaxed text-gold-200/90">⚠️ For monetization, both monetization and embedding must be enabled for the video in YouTube Studio. YouTube runs ads according to its own rules and may treat paid or incentivized views as invalid traffic, so ads are not guaranteed.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="target_quantity" class="mb-1.5 block text-xs font-semibold text-slate-300">Required quantity</label>
                        <input type="number" name="target_quantity" id="target_quantity" required
                               min="{{ $minQuantity }}" max="{{ $maxQuantity }}" step="1"
                               value="{{ old('target_quantity', $minQuantity) }}"
                               class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                        <p class="mt-1.5 text-[11px] text-slate-500">Between {{ number_format($minQuantity) }} and {{ number_format($maxQuantity) }} actions.</p>
                    </div>

                    <div id="duration-field">
                        <label for="duration_seconds" class="mb-1.5 block text-xs font-semibold text-slate-300">Video duration</label>
                        <select name="duration_seconds" id="duration_seconds"
                                class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                            @foreach ($durations as $seconds)
                                <option value="{{ $seconds }}" @selected((int) old('duration_seconds', $minSeconds) === $seconds)>
                                    {{ $seconds }} sec
                                </option>
                            @endforeach
                        </select>
                        @if ($campaignAdminFeeVisible)
                            <p class="mt-1.5 text-[11px] text-slate-500">Duration is only the minimum watch time. A video view also pays 2 Coins to the earner plus a 1 Coin admin fee.</p>
                        @else
                            <p class="mt-1.5 text-[11px] text-slate-500">Duration is only the minimum watch time. A video view pays the earner 2 Coins.</p>
                        @endif
                    </div>
                </div>

                <div>
                    <label for="instructions" class="mb-1.5 block text-xs font-semibold text-slate-300">Instructions <span class="font-normal text-slate-500">(optional)</span></label>
                    <textarea name="instructions" id="instructions" rows="3" maxlength="1000"
                              placeholder="Tell earners what they need to do."
                              class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">{{ old('instructions') }}</textarea>
                </div>

                <div class="flex flex-col gap-3 border-t border-white/10 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p id="quote-hint" class="text-[11px] text-slate-500">Enter the quantity — the total cost will appear here.</p>
                    <button type="submit" id="campaign-submit"
                            class="rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-5 py-2.5 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20 disabled:cursor-not-allowed disabled:opacity-50">
                        Publish campaign
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-mint-400/25 bg-gradient-to-br from-mint-500/15 via-ink-850 to-ink-900 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-mint-200/80">Live estimate</p>
                <div class="mt-3 flex items-center justify-between text-sm">
                    <span class="text-slate-300">Reward per action</span>
                    <span id="quote-reward" class="font-bold text-white">Coins 0.00</span>
                </div>
                @if ($campaignAdminFeeVisible)
                    <div class="mt-2 flex items-center justify-between text-sm">
                        <span class="text-slate-300">Admin fee per action</span>
                        <span id="quote-fee" class="font-bold text-white">Coins 0.00</span>
                    </div>
                @endif
                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="text-slate-300">Total campaign cost</span>
                    <span id="quote-total" class="font-display text-xl font-bold text-mint-300">Coins 0.00</span>
                </div>
                @if ($campaignAdminFeeVisible)
                    <p class="mt-3 text-[11px] leading-relaxed text-slate-400">The full amount (3 Coins per action) is held from your wallet. Each completed action pays 2 Coins to the earner and 1 Coin to the admin. If you cancel the campaign, the full 3 Coins for unfinished actions is refunded.</p>
                @else
                    <p class="mt-3 text-[11px] leading-relaxed text-slate-400">The full campaign budget is held from your wallet. If you cancel the campaign, the unused budget for unfinished actions is refunded.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 text-xs leading-relaxed text-slate-400">
                <h2 class="text-sm font-bold text-white">Campaign rules</h2>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    <li>The campaign goes live immediately after payment — no approval needed.</li>
                    @if ($campaignAdminFeeVisible)
                        <li>Each action costs 2 Coins for the earner plus a 1 Coin admin fee (3 Coins total).</li>
                    @else
                        <li>Each completed action pays the earner 2 Coins.</li>
                    @endif
                    <li>The campaign hides itself once the required quantity is complete.</li>
                    <li>Each earner can complete your campaign only once.</li>
                    <li>Cancelling returns the unused budget to your wallet.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var form = document.getElementById('campaign-form');

            if (!form) {
                return;
            }

            var typeSelect = document.getElementById('task_type');
            var quantityInput = document.getElementById('target_quantity');
            var durationField = document.getElementById('duration-field');
            var durationSelect = document.getElementById('duration_seconds');
            var rewardOut = document.getElementById('quote-reward');
            var feeOut = document.getElementById('quote-fee');
            var totalOut = document.getElementById('quote-total');
            var hintOut = document.getElementById('quote-hint');
            var submit = document.getElementById('campaign-submit');

            var watchMap = @js($watchMap);
            var reward = {{ (float) $campaignConfig['action_reward'] }};
            var fee = {{ $campaignAdminFeeVisible ? (float) $campaignConfig['action_admin_fee'] : 0 }};
            var wallet = {{ (float) $walletBalance }};

            function money(value) {
                return 'Coins ' + value.toFixed(2);
            }

            function update() {
                var isWatch = watchMap[typeSelect.value] === true;

                if (durationField) {
                    durationField.classList.toggle('hidden', !isWatch);
                }

                var quantity = parseInt(quantityInput.value, 10);

                if (isNaN(quantity) || quantity < 0) {
                    quantity = 0;
                }

                var total = (reward + fee) * quantity;

                rewardOut.textContent = money(reward);

                if (feeOut) {
                    feeOut.textContent = money(fee);
                }

                totalOut.textContent = money(total);

                if (total <= 0) {
                    hintOut.textContent = 'Enter the quantity — the total cost will appear here.';
                    hintOut.className = 'text-[11px] text-slate-500';
                    submit.disabled = true;
                } else if (total > wallet + 0.001) {
                    hintOut.textContent = 'Insufficient wallet balance — ' + money(total) + ' required, available ' + money(wallet) + '.';
                    hintOut.className = 'text-[11px] text-rose-300';
                    submit.disabled = true;
                } else {
                    hintOut.textContent = 'The payment will be held from your wallet immediately and the campaign will go live at once.';
                    hintOut.className = 'text-[11px] text-mint-300';
                    submit.disabled = false;
                }
            }

            [typeSelect, quantityInput, durationSelect].forEach(function (element) {
                if (!element) {
                    return;
                }

                element.addEventListener('input', update);
                element.addEventListener('change', update);
            });

            update();
        })();
    </script>
@endpush
