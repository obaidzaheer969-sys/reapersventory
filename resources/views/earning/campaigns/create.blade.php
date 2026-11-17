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
                Campaign ka poora budget wallet se foran hold hoga aur campaign turant live ho jayegi — kisi admin approval ka intezar nahi. Cancel karne par unused budget wallet me wapas aa jata hai.
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
                    <p class="mt-1.5 text-[11px] text-slate-500">Har action (subscribe, follow, like ya video view) par earner ko 2 PKR aur admin ko 1 PKR — total 3 PKR per action. Quantity aap khud set karein — 2 subscribers ya 1 view ka order bhi allowed hai.</p>
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
                    <p class="mt-1.5 text-[11px] text-slate-500">YouTube/Facebook video watch tasks isi lock page ke official player me chalti hain; subscribe, follow aur like links naye tab me khulte hain.</p>
                    <p class="mt-1.5 text-[11px] leading-relaxed text-gold-200/90">⚠️ Monetization ke liye YouTube Studio me video ka monetization aur embedding dono on hona chahiye. Ads sirf YouTube apne rules me chalata hai; paid/incentivized views ko YouTube invalid traffic mana sakta hai, isliye ad chalne ka guarantee nahi hai.</p>
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
                                    {{ $seconds }} sec
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-[11px] text-slate-500">Duration sirf minimum watch time hai. Video view par bhi 2 PKR earner + 1 PKR admin fee hai.</p>
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
                    <span id="quote-reward" class="font-bold text-white">PKR 0.00</span>
                </div>
                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="text-slate-300">Admin fee per action</span>
                    <span id="quote-fee" class="font-bold text-white">PKR 0.00</span>
                </div>
                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="text-slate-300">Total campaign cost</span>
                    <span id="quote-total" class="font-display text-xl font-bold text-mint-300">PKR 0.00</span>
                </div>
                <p class="mt-3 text-[11px] leading-relaxed text-slate-400">Poora amount (3 PKR per action) wallet se hold hota hai. Har action complete hone par earner ko 2 PKR aur admin ko 1 PKR milte hain. Campaign cancel karne par adhoori actions ka poora 3 PKR wapis ho jata hai.</p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 text-xs leading-relaxed text-slate-400">
                <h2 class="text-sm font-bold text-white">Campaign rules</h2>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    <li>Payment ke foran baad campaign live ho jati hai — approval ki zaroorat nahi.</li>
                    <li>Har action par 2 PKR earner + 1 PKR admin fee (total 3 PKR) kharch hota hai.</li>
                    <li>Required quantity poori hone par campaign khud hide ho jati hai.</li>
                    <li>Ek earner aap ki campaign sirf ek dafa complete kar sakta hai.</li>
                    <li>Cancel karne par unused budget wallet me wapas aa jata hai.</li>
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
            var fee = {{ (float) $campaignConfig['action_admin_fee'] }};
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

                var total = (reward + fee) * quantity;

                rewardOut.textContent = money(reward);
                feeOut.textContent = money(fee);
                totalOut.textContent = money(total);

                if (total <= 0) {
                    hintOut.textContent = 'Quantity daalein — total cost yahan dikhega.';
                    hintOut.className = 'text-[11px] text-slate-500';
                    submit.disabled = true;
                } else if (total > wallet + 0.001) {
                    hintOut.textContent = 'Wallet balance kaafi nahi — ' + money(total) + ' chahiye, available ' + money(wallet) + '.';
                    hintOut.className = 'text-[11px] text-rose-300';
                    submit.disabled = true;
                } else {
                    hintOut.textContent = 'Payment wallet se foran hold hogi aur campaign turant live ho jayegi.';
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
