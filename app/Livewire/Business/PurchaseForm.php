<?php

namespace App\Livewire\Business;

use App\Livewire\Concerns\RefreshesAfterSave;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PurchaseForm extends Component
{
    use RefreshesAfterSave;

    public ?int $supplierId = null;

    public ?int $warehouseId = null;

    public string $purchaseDate = '';

    public string $tax = '0';

    public string $discount = '0';

    public ?string $notes = null;

    /** @var list<array{product_id: ?int, quantity: string, unit_cost: string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->purchaseDate = now()->toDateString();
        $this->supplierId = Supplier::query()->where('is_active', true)->value('id');
        $this->warehouseId = Warehouse::query()->where('is_default', true)->value('id')
            ?? Warehouse::query()->value('id');
        $this->items = [
            ['product_id' => null, 'quantity' => '1', 'unit_cost' => '0'],
        ];
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => null, 'quantity' => '1', 'unit_cost' => '0'];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(PurchaseService $purchases): void
    {
        $user = Auth::user();
        abort_unless($user->can('create', Purchase::class), 403);
        $businessId = $user->business_id;

        $this->validate([
            'supplierId' => [
                'required',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'warehouseId' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'purchaseDate' => ['required', 'date'],
            'tax' => ['required', 'numeric', 'min:0'],
            'discount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $purchase = $purchases->create($user->business, $user, [
                'supplier_id' => $this->supplierId,
                'warehouse_id' => $this->warehouseId,
                'purchase_date' => $this->purchaseDate,
                'tax' => $this->tax,
                'discount' => $this->discount,
                'notes' => $this->notes,
                'items' => $this->items,
            ]);
        } catch (ValidationException $e) {
            $this->addValidationException($e, 'items');

            return;
        }

        session()->flash('status', 'Purchase created.');
        $this->redirectAndRefresh(route('business.purchases.show', $purchase));
    }

    public function render(): View
    {
        return view('livewire.business.purchase-form', [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
