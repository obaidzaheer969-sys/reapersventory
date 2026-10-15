<div>
    <form wire:submit="submit" class="space-y-4">
        <div>
            <label class="block text-sm font-medium" for="paymentMethodId">Payment method</label>
            <select id="paymentMethodId" wire:model="paymentMethodId" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                @foreach ($methods as $method)
                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                @endforeach
            </select>
            @error('paymentMethodId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <p class="text-sm text-slate-600">Billing interval: <strong>Monthly</strong></p>

        <div>
            <label class="block text-sm font-medium" for="reference">Transaction reference</label>
            <input id="reference" type="text" wire:model="reference" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            @error('reference') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium" for="notes">Notes</label>
            <textarea id="notes" wire:model="notes" rows="2" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
            @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium" for="proof">Payment proof (JPG/PNG/PDF)</label>
            <input id="proof" type="file" wire:model="proof" class="mt-1 block w-full text-sm">
            <div wire:loading wire:target="proof" class="mt-1 text-sm text-slate-500">Uploading…</div>
            @error('proof') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded-lg bg-navy-900 px-4 py-2.5 font-semibold text-white hover:bg-navy-800" wire:loading.attr="disabled">
            Submit for review
        </button>
    </form>
</div>
