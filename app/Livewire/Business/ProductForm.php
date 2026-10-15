<?php

namespace App\Livewire\Business;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\ProductService;
use App\Support\ProductUnits;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProductForm extends Component
{
    public ?int $productId = null;

    public string $name = '';

    public string $sku = '';

    public ?string $barcode = null;

    public ?int $categoryId = null;

    public string $unit = 'pcs';

    public string $costPrice = '0';

    public string $sellingPrice = '0';

    public string $reorderLevel = '0';

    public ?string $description = null;

    public bool $trackInventory = true;

    public bool $isActive = true;

    public string $openingStock = '';

    public ?int $warehouseId = null;

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            abort_unless(Auth::user()->can('update', $product), 403);
            $this->productId = $product->id;
            $this->fill([
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'categoryId' => $product->category_id,
                'unit' => $product->unit,
                'costPrice' => (string) $product->cost_price,
                'sellingPrice' => (string) $product->selling_price,
                'reorderLevel' => (string) $product->reorder_level,
                'description' => $product->description,
                'trackInventory' => $product->track_inventory,
                'isActive' => $product->is_active,
            ]);
        }
    }

    public function save(ProductService $products, InventoryService $inventory): void
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->where(fn ($q) => $q->where('business_id', $businessId)->whereNull('deleted_at'))
                    ->ignore($this->productId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')
                    ->where(fn ($q) => $q->where('business_id', $businessId)->whereNull('deleted_at'))
                    ->ignore($this->productId),
            ],
            'categoryId' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'unit' => ['required', 'string', Rule::in(array_values(array_unique([...ProductUnits::all(), $this->unit])))],
            'costPrice' => ['required', 'numeric', 'min:0'],
            'sellingPrice' => ['required', 'numeric', 'min:0'],
            'reorderLevel' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'trackInventory' => ['boolean'],
            'isActive' => ['boolean'],
            'openingStock' => ['nullable', 'numeric', 'min:0'],
            'warehouseId' => [
                Rule::requiredIf(fn () => filled($this->openingStock) && (float) $this->openingStock > 0),
                'nullable',
                Rule::exists('warehouses', 'id')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
        ]);

        $payload = [
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'barcode' => $validated['barcode'] ?: null,
            'category_id' => $validated['categoryId'],
            'unit' => $validated['unit'],
            'cost_price' => $validated['costPrice'],
            'selling_price' => $validated['sellingPrice'],
            'reorder_level' => $validated['reorderLevel'],
            'description' => $validated['description'],
            'track_inventory' => $validated['trackInventory'],
            'is_active' => $validated['isActive'],
        ];

        if ($this->productId) {
            $product = Product::query()->findOrFail($this->productId);
            abort_unless($user->can('update', $product), 403);
            $products->update($product, $user, $payload);
        } else {
            abort_unless($user->can('create', Product::class), 403);
            $product = $products->create($user->business, $user, $payload);

            $opening = (float) ($validated['openingStock'] ?? 0);
            if ($opening > 0 && $validated['trackInventory'] && ! empty($validated['warehouseId'])) {
                $warehouse = Warehouse::query()->findOrFail($validated['warehouseId']);
                $inventory->adjust(
                    product: $product,
                    warehouse: $warehouse,
                    type: StockMovementType::AdjustmentIn,
                    quantity: $opening,
                    actor: $user,
                    notes: 'Opening stock',
                );
            }
        }

        session()->flash('status', 'Product saved.');
        $this->redirect(route('business.products.index'), navigate: true);
    }

    public function render(): View
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();

        if ($this->warehouseId === null && $warehouses->count() === 1) {
            $this->warehouseId = $warehouses->first()->id;
        }

        return view('livewire.business.product-form', [
            'categories' => Category::query()->orderBy('name')->get(),
            'units' => ProductUnits::all(),
            'warehouses' => $warehouses,
        ]);
    }
}
