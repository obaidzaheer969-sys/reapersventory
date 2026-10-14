<?php

namespace App\Livewire\Business;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
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

    public function save(ProductService $products): void
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
            'unit' => ['required', 'string', 'max:32'],
            'costPrice' => ['required', 'numeric', 'min:0'],
            'sellingPrice' => ['required', 'numeric', 'min:0'],
            'reorderLevel' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'trackInventory' => ['boolean'],
            'isActive' => ['boolean'],
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
            $products->create($user->business, $user, $payload);
        }

        session()->flash('status', 'Product saved.');
        $this->redirect(route('business.products.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.business.product-form', [
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }
}
