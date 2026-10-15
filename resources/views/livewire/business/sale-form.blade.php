<div>
    <form wire:submit="save" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium">Customer (optional)</label>
                <select wire:model="customerId" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Walk-in customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">Warehouse</label>
                <select wire:model="warehouseId" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
                @error('warehouseId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium">Sale date</label>
                <input type="date" wire:model="saleDate" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium">Tax %</label>
                    <select wire:model.live="taxPercent" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        @foreach ($taxOptions as $option)
                            <option value="{{ $option }}">{{ $option }}%</option>
                        @endforeach
                    </select>
                    @error('taxPercent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Discount %</label>
                    <select wire:model.live="discountPercent" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        @foreach ($discountOptions as $option)
                            <option value="{{ $option }}">{{ $option }}%</option>
                        @endforeach
                    </select>
                    @error('discountPercent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium">Payment now</label>
                <input type="number" step="0.01" wire:model="paymentAmount" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <p class="mt-1 text-xs text-slate-500">Bill total: {{ number_format((float) $this->grandTotal, 2) }} — payment cannot be higher than this.</p>
                @error('paymentAmount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium">Payment method</label>
                <select wire:model="paymentMethod" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="cash">Cash</option>
                    <option value="bank">Bank transfer</option>
                    <option value="easypaisa">Easypaisa</option>
                    <option value="jazzcash">JazzCash</option>
                    <option value="card">Debit or Credit card</option>
                </select>
                @error('paymentMethod') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-navy-900">Items</h3>
                <button type="button" wire:click="addItem" class="text-sm font-semibold text-brand-600">Add line</button>
            </div>
            @foreach ($items as $index => $item)
                <div class="grid gap-3 rounded-xl border border-slate-200 p-3 sm:grid-cols-4" wire:key="sale-item-{{ $index }}">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium">Product</label>
                        <select wire:model.live="items.{{ $index }}.product_id" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300">
                            <option value="">Select</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                            @endforeach
                        </select>
                        @error("items.$index.product_id") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium">Qty</label>
                        <input type="number" step="0.001" wire:model.live="items.{{ $index }}.quantity" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium">Unit price</label>
                        <div class="mt-1 flex gap-2">
                            <input type="number" step="0.01" wire:model.live="items.{{ $index }}.unit_price" class="w-full rounded-lg px-3 py-2 ring-1 ring-slate-300">
                            @if (count($items) > 1)
                                <button type="button" wire:click="removeItem({{ $index }})" class="text-sm text-red-600">✕</button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            @error('items') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
            <div class="flex justify-between gap-3"><span class="text-slate-600">Subtotal</span><span class="font-semibold">{{ number_format((float) $this->subtotal, 2) }}</span></div>
            <div class="mt-1 flex justify-between gap-3"><span class="text-slate-600">Tax ({{ $taxPercent }}%)</span><span class="font-semibold">{{ number_format((float) $this->taxAmount, 2) }}</span></div>
            <div class="mt-1 flex justify-between gap-3"><span class="text-slate-600">Discount ({{ $discountPercent }}%)</span><span class="font-semibold">{{ number_format((float) $this->discountAmount, 2) }}</span></div>
            <div class="mt-2 flex justify-between gap-3 border-t border-slate-200 pt-2 text-base"><span class="font-semibold text-navy-900">Bill total</span><span class="font-bold text-navy-900">{{ number_format((float) $this->grandTotal, 2) }}</span></div>
        </div>

        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 font-semibold text-white hover:bg-brand-600">Complete sale</button>
    </form>
</div>
