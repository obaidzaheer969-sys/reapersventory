<div>
    <form wire:submit="save" class="space-y-3">
        <div>
            <label class="block text-sm font-medium">Product</label>
            <select wire:model.live="productId" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Select product</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">
                        {{ $product->name }} ({{ $product->sku }}) — stock {{ number_format($product->stock_on_hand ?? 0, 3) }} {{ $product->unit }}
                    </option>
                @endforeach
            </select>
            @error('productId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @if ($productId && $selectedStock !== null)
                <p class="mt-1 text-xs text-slate-600">Current stock: <strong>{{ number_format($selectedStock, 3) }} {{ $selectedUnit }}</strong></p>
            @endif
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
                <p class="mt-1 text-xs text-amber-700">Create a warehouse first under Warehouses.</p>
            @endif
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium">Action</label>
                <select wire:model="direction" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="in">Add stock</option>
                    <option value="out">Remove stock</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">Quantity ({{ $selectedUnit }})</label>
                <input type="number" step="0.001" wire:model="quantity" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
                @error('quantity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium">Notes</label>
            <input type="text" wire:model="notes" placeholder="Optional" class="mt-1 w-full rounded-lg px-3 py-2 ring-1 ring-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-2.5 font-semibold text-white hover:bg-brand-600">
            {{ $direction === 'out' ? 'Remove stock' : 'Add stock' }}
        </button>
    </form>
</div>
