<div class="space-y-4">
    <p class="text-sm text-slate-600">
        Current package: <strong>{{ $currentPlanName ?? '—' }}</strong>.
        Select another package to upgrade or change.
    </p>

    <div class="space-y-3">
        @foreach ($plans as $plan)
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border px-3 py-3 transition {{ (int) $planId === (int) $plan->id ? 'border-brand-500 bg-brand-50/50' : 'border-slate-200 hover:border-slate-300' }}">
                <input type="radio" wire:model.live="planId" value="{{ $plan->id }}" class="mt-1">
                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-center justify-between gap-2">
                        <span class="font-semibold text-navy-900">
                            {{ $plan->name }}
                            @if ((int) $plan->id === (int) $currentPlanId)
                                <span class="ml-1 rounded-full bg-navy-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Current</span>
                            @endif
                        </span>
                        <span class="text-sm font-semibold text-brand-600">
                            {{ $plan->currency }} {{ number_format((float) $plan->price_monthly, 0) }}/mo
                        </span>
                    </span>
                    @if ($plan->description)
                        <span class="mt-1 block text-xs text-slate-500">{{ $plan->description }}</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>
    @error('planId') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

    <p class="text-xs text-slate-500">Billing is monthly. After updating, submit payment proof for admin approval when needed.</p>

    <button
        type="button"
        wire:click="update"
        wire:loading.attr="disabled"
        class="rounded-lg bg-brand-500 px-4 py-2.5 font-semibold text-white hover:bg-brand-600 disabled:opacity-60"
    >
        <span wire:loading.remove wire:target="update">Update package</span>
        <span wire:loading wire:target="update">Updating…</span>
    </button>
</div>
