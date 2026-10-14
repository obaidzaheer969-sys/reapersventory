<?php

namespace App\Livewire\Business;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StockAdjustmentForm extends Component
{
    public ?int $productId = null;

    public ?int $warehouseId = null;

    public string $direction = 'in';

    public string $quantity = '1';

    public ?string $notes = null;

    public function save(InventoryService $inventory): void
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        $validated = $this->validate([
            'productId' => [
                'required',
                Rule::exists('products', 'id')->where(
                    fn ($q) => $q->where('business_id', $businessId)->where('track_inventory', true)
                ),
            ],
            'warehouseId' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'direction' => ['required', 'in:in,out'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $product = Product::query()->findOrFail($validated['productId']);
        $warehouse = Warehouse::query()->findOrFail($validated['warehouseId']);

        abort_unless($user->can('update', $product), 403);

        $inventory->adjust(
            product: $product,
            warehouse: $warehouse,
            type: $validated['direction'] === 'in'
                ? StockMovementType::AdjustmentIn
                : StockMovementType::AdjustmentOut,
            quantity: $validated['quantity'],
            actor: $user,
            notes: $validated['notes'],
        );

        session()->flash('status', 'Stock adjusted.');
        $this->redirect(route('business.inventory.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.business.stock-adjustment-form', [
            'products' => Product::query()->where('track_inventory', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
