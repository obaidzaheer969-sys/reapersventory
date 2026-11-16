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
            <h1 class="mt-1.5 font-display text-3xl font-bold text-white sm:text-4xl">Apna task publish karein</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">
                Campaign ka poora budget wallet se foran hold hoga. Admin approval ke baad campaign doosre users ko live dikhegi. Reject hone par payment wallet me wapas aa jayegi.
            </p>
        </div>
        <div class="rounded-2xl border border-mint-400/25 bg-mint-500/10 px-4 py-3 text-right">
            <p class="text-[10px] font-bold uppercase tracking-wider text-mint-200/80">Wallet balance</p>
            <p class="font-display text-lg font-bold text-white">{{ \App\Support\Currency::format($walletBalance) }}</p>
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
                    <p class="mt-1.5 text-[11px] text-slate-500">Follower, subscriber aur like campaigns par 100 actions ka reward 2 PKR + 1 PKR platform fee. Video campaigns duration ke hisaab se priced hain.</p>
                </div>

                <div>
                    <label for="title" class="mb-1.5 block text-xs font-semibold text-slate-300">Campaign title</label>
                    <input type="text" name="title" id="title" required maxlength="255" value="{{ old('title') }}"
                           placeholder="e.g. Naya vlog release — views barhayen"
                           class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                </div>

                <div>
                    <label for="target_url" class="mb-1.5 block text-xs font-semibold text-slate-300">Video / page / channel URL</label>
                    <input type="url" name="target_url" id="target_url" required maxlength="2048" value="{{ old('target_url') }}"
                           placeholder="https://..."
                           class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                    <p class="mt-1.5 text-[11px] text-slate-500">Yeh link earners ko naye tab me khulega jahan woh action complete karenge.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="target_quantity" class="mb-1.5 block text-xs font-semibold text-slate-300">Required quantity</label>
                        <input type="number" name="target_quantity" id="target_quantity" required
                               min="{{ $minQuantity }}" max="{{ $maxQuantity }}" step="1"
                               value="{{ old('target_quantity', $minQuantity) }}"
                               class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                        <p class="mt-1.5 text-[11px] text-slate-500">{{ number_format($minQuantity) }} se {{ number_format($maxQuantity) }} actions.</p>
                    </div>

                    <div id="duration-field">
                        <label for="duration_seconds" class="mb-1.5 block text-xs font-semibold text-slate-300">Video duration</label>
                        <select name="duration_seconds" id="duration_seconds"
                                class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">
                            @foreach ($durations as $seconds)
                                <option value="{{ $seconds }}" @selected((int) old('duration_seconds', $minSeconds) === $seconds)>
                                    {{ $seconds }} sec — {{ \App\Support\Currency::format($campaignConfig['video_base_reward'] + intdiv(max(0, $seconds - $minSeconds), $stepSeconds) * $campaignConfig['video_extra_reward']) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-[11px] text-slate-500">60s = 2 PKR, phir har extra 60s par +2 PKR.</p>
                    </div>
                </div>

                <div>
                    <label for="instructions" class="mb-1.5 block text-xs font-semibold text-slate-300">Instructions <span class="font-normal text-slate-500">(optional)</span></label>
                    <textarea name="instructions" id="instructions" rows="3" maxlength="1000"
                              placeholder="Earners ko batayen ke unhein kya karna hai."
                              class="w-full rounded-xl border border-white/10 bg-ink-900 px-3.5 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-mint-400 focus:outline-none focus:ring-2 focus:ring-mint-400/20">{{ old('instructions') }}</textarea>
                </div>

                <div class="flex flex-col gap-3 border-t border-white/10 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p id="quote-hint" class="text-[11px] text-slate-500">Quantity daalein — total cost yahan dikhega.</p>
                    <button type="submit" id="campaign-submit"
                            class="rounded-xl bg-gradient-to-r from-mint-400 to-mint-500 px-5 py-2.5 text-sm font-bold text-ink-950 shadow-lg shadow-mint-500/20 disabled:cursor-not-allowed disabled:opacity-50">
                        Submit for approval
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-mint-400/25 bg-gradient-to-br from-mint-500/15 via-ink-850 to-ink-900 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-mint-200/80">Live estimate</p>
                <div class="mt-3 flex items-center justify-between text-sm">
                    <span class="text-slate-300">Reward per action</span>
                    <span id="quote-reward" class="font-bold text-white">PKR 0.00</span>
                </div>
                <div id="quote-fee-row" class="mt-2 flex items-center justify-between text-sm">
                    <span class="text-slate-300">Platform fee</span>
                    <span id="quote-fee" class="font-bold text-white">PKR 0.00</span>
                </div>
                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="text-slate-300">Total campaign cost</span>
                    <span id="quote-total" class="font-display text-xl font-bold text-mint-300">PKR 0.00</span>
                </div>
                <p class="mt-3 text-[11px] leading-relaxed text-slate-400">Poora amount wallet se hold hota hai. Har action complete hone par earner ko reward milta hai.</p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 text-xs leading-relaxed text-slate-400">
                <h2 class="text-sm font-bold text-white">Campaign rules</h2>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    <li>Campaign admin approval ke baad live hoti hai.</li>
                    <li>Follower, subscriber aur like campaigns par 1 PKR platform fee lagti hai.</li>
                    <li>Required quantity poori hone par campaign khud hide ho jati hai.</li>
                    <li>Ek earner aap ki campaign sirf ek dafa complete kar sakta hai.</li>
                    <li>Cancel karne par unused budget wallet me wapas aa jata hai.</li>
                    <li>Reject hone par poora amount refund ho jata hai.</li>
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
            var feeRow = document.getElementById('quote-fee-row');
            var feeOut = document.getElementById('quote-fee');
            var totalOut = document.getElementById('quote-total');
            var hintOut = document.getElementById('quote-hint');
            var submit = document.getElementById('campaign-submit');

            var watchMap = @js($watchMap);
            var config = {
                actionReward: {{ (float) $campaignConfig['action_reward'] }},
                actionFee: {{ (float) $campaignConfig['action_admin_fee'] }},
                base: {{ (float) $campaignConfig['video_base_reward'] }},
                extra: {{ (float) $campaignConfig['video_extra_reward'] }},
                min: {{ $minSeconds }},
                step: {{ $stepSeconds }}
            };
            var wallet = {{ (float) $walletBalance }};

            function money(value) {
                return 'PKR ' + value.toFixed(2);
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

                var reward;

                if (isWatch) {
                    var duration = parseInt(durationSelect.value, 10) || config.min;
                    var steps = Math.max(0, Math.floor((duration - config.min) / config.step));
                    reward = config.base + (steps * config.extra);
                } else {
                    reward = config.actionReward;
                }

                var total = reward * quantity;
                var fee = isWatch ? 0 : config.actionFee;

                if (quantity > 0) {
                    total += fee;
                }

                rewardOut.textContent = money(reward);
                feeOut.textContent = money(fee);
                totalOut.textContent = money(total);

                if (feeRow) {
                    feeRow.classList.toggle('hidden', isWatch);
                }

                if (total <= 0) {
                    hintOut.textContent = 'Quantity daalein — total cost yahan dikhega.';
                    hintOut.className = 'text-[11px] text-slate-500';
                    submit.disabled = true;
                } else if (total > wallet + 0.001) {
                    hintOut.textContent = 'Wallet balance kaafi nahi — ' + money(total) + ' chahiye, available ' + money(wallet) + '.';
                    hintOut.className = 'text-[11px] text-rose-300';
                    submit.disabled = true;
                } else {
                    hintOut.textContent = 'Payment wallet se foran hold hogi. Approval ke baad campaign live hogi.';
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
