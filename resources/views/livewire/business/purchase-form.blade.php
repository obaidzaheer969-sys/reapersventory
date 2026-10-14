<div>
    <form wire:submit="save" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium">Supplier</label>
                <select wire:model="supplierId" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('supplierId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                <label class="block text-sm font-medium">Purchase date</label>
                <input type="date" wire:model="purchaseDate" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium">Tax</label>
                    <input type="number" step="0.01" wire:model="tax" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium">Discount</label>
                    <input type="number" step="0.01" wire:model="discount" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-navy-900">Items</h3>
                <button type="button" wire:click="addItem" class="text-sm font-semibold text-brand-600">Add line</button>
            </div>
            @foreach ($items as $index => $item)
                <div class="grid gap-3 rounded-xl border border-slate-200 p-3 sm:grid-cols-4" wire:key="item-{{ $index }}">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium">Product</label>
                        <select wire:model="items.{{ $index }}.product_id" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300">
                            <option value="">Select</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                            @endforeach
                        </select>
                        @error("items.$index.product_id") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium">Qty</label>
                        <input type="number" step="0.001" wire:model="items.{{ $index }}.quantity" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium">Unit cost</label>
                        <div class="mt-1 flex gap-2">
                            <input type="number" step="0.01" wire:model="items.{{ $index }}.unit_cost" class="w-full rounded-lg px-3 py-2 ring-1 ring-slate-300">
                            @if (count($items) > 1)
                                <button type="button" wire:click="removeItem({{ $index }})" class="text-sm text-red-600">✕</button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            @error('items') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 font-semibold text-white hover:bg-brand-600">Create purchase</button>
    </form>
</div>
