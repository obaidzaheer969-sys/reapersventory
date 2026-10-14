<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium" for="planId">Plan</label>
        <select id="planId" wire:model="planId" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}">
                    {{ $plan->name }} — {{ $plan->currency }} {{ number_format($plan->price_monthly, 0) }}/mo
                </option>
            @endforeach
        </select>
        @error('planId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium" for="billingInterval">Billing interval</label>
        <select id="billingInterval" wire:model="billingInterval" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
        </select>
        @error('billingInterval') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <button type="button" wire:click="start" class="rounded-lg bg-brand-500 px-4 py-2.5 font-semibold text-white hover:bg-brand-600" wire:loading.attr="disabled">
        Start subscription
    </button>
</div>
