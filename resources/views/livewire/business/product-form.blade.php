<div>
    <form wire:submit="save" class="space-y-3">
        <div>
            <label class="block text-sm font-medium">Name</label>
            <input type="text" wire:model="name" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium">SKU</label>
                <input type="text" wire:model="sku" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                @error('sku') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium">Barcode</label>
                <input type="text" wire:model="barcode" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                @error('barcode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium">Category</label>
            <select wire:model="categoryId" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Uncategorized</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium">Cost</label>
                <input type="number" step="0.01" wire:model="costPrice" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-sm font-medium">Selling</label>
                <input type="number" step="0.01" wire:model="sellingPrice" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium">Unit</label>
                <select wire:model="unit" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @foreach ($units as $unitOption)
                        <option value="{{ $unitOption }}">{{ \App\Support\ProductUnits::label($unitOption) }}</option>
                    @endforeach
                </select>
                @error('unit') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium">Reorder level</label>
                <input type="number" step="0.001" wire:model="reorderLevel" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <p class="mt-1 text-xs text-slate-500">Alert when stock reaches this amount (in selected unit).</p>
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="trackInventory" class="rounded text-brand-500"> Track inventory</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isActive" class="rounded text-brand-500"> Active</label>

        @if (! $productId && $trackInventory)
            <div class="rounded-lg border border-dashed border-brand-500/40 bg-brand-500/5 p-3 space-y-3">
                <p class="text-sm font-semibold text-navy-900">Opening stock (optional)</p>
                <div>
                    <label class="block text-sm font-medium">Quantity ({{ $unit }})</label>
                    <input type="number" step="0.001" wire:model="openingStock" placeholder="e.g. 10" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @error('openingStock') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Warehouse</label>
                    <select wire:model="warehouseId" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">Select warehouse</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    @error('warehouseId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if ($warehouses->isEmpty())
                        <p class="mt-1 text-xs text-amber-700">Create a warehouse first, then you can add stock.</p>
                    @endif
                </div>
            </div>
        @endif

        <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-2.5 font-semibold text-white hover:bg-brand-600">
            {{ $productId ? 'Update product' : 'Save product' }}
        </button>
    </form>
</div>
