<div>
    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[1fr_280px]">
        <div class="space-y-4">
            <div class="rounded-xl border border-brand-200 bg-brand-50/50 p-3">
                <label class="block text-sm font-semibold text-navy-900">Scan barcode</label>
                <input
                    type="text"
                    wire:model="barcodeScan"
                    wire:keydown.enter.prevent="scanBarcode"
                    autofocus
                    autocomplete="off"
                    placeholder="Scan barcode / SKU then Enter"
                    class="mt-1 w-full rounded-lg px-3 py-3 text-base ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
                <p class="mt-1 text-xs text-slate-500">USB scanner = keyboard. Sale complete pe chhoti bill print screen khulegi.</p>
                @error('barcodeScan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium">Customer</label>
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
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Discount %</label>
                        <select wire:model.live="discountPercent" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            @foreach ($discountOptions as $option)
                                <option value="{{ $option }}">{{ $option }}%</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium">Payment now</label>
                    <div class="mt-1 flex gap-2">
                        <input type="number" step="0.01" wire:model.live="paymentAmount" class="w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <button type="button" wire:click="payFullAmount" class="whitespace-nowrap rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-navy-900 hover:bg-slate-50">Pay full</button>
                    </div>
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
        </div>

        <aside class="lg:sticky lg:top-4 h-fit rounded-2xl border border-navy-900/10 bg-navy-950 p-4 text-white shadow-lg">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-400">Bill preview</p>
            <p class="mt-1 font-mono text-[11px] text-slate-400">80mm thermal style</p>
            <div class="mt-4 space-y-2 font-mono text-sm">
                <div class="flex justify-between gap-3"><span class="text-slate-300">Subtotal</span><span>{{ number_format((float) $this->subtotal, 2) }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-300">Tax {{ $taxPercent }}%</span><span>{{ number_format((float) $this->taxAmount, 2) }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-300">Discount {{ $discountPercent }}%</span><span>{{ number_format((float) $this->discountAmount, 2) }}</span></div>
                <div class="flex justify-between gap-3 border-t border-white/10 pt-3 text-lg font-bold">
                    <span>TOTAL</span>
                    <span class="text-brand-400">{{ number_format((float) $this->grandTotal, 2) }}</span>
                </div>
                <div class="flex justify-between gap-3 text-xs text-slate-400">
                    <span>Paying now</span>
                    <span>{{ number_format((float) $paymentAmount, 2) }}</span>
                </div>
            </div>
            <button type="submit" class="mt-5 w-full rounded-lg bg-brand-500 px-4 py-3 font-semibold text-white hover:bg-brand-600">
                Complete &amp; print bill
            </button>
            <p class="mt-2 text-center text-[11px] text-slate-400">Invoice auto-save + small bill print dialog</p>
        </aside>
    </form>
</div>
