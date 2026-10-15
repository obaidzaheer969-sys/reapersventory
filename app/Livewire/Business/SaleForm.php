<?php

namespace App\Livewire\Business;

use App\Livewire\Concerns\RefreshesAfterSave;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Services\SaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SaleForm extends Component
{
    use RefreshesAfterSave;

    public ?int $customerId = null;

    public ?int $warehouseId = null;

    public string $saleDate = '';

    public string $tax = '0';

    public string $discount = '0';

    public string $paymentAmount = '0';

    public string $paymentMethod = 'cash';

    public ?string $notes = null;

    /** @var list<array{product_id: ?int, quantity: string, unit_price: string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->saleDate = now()->toDateString();
        $this->warehouseId = Warehouse::query()->where('is_default', true)->value('id')
            ?? Warehouse::query()->value('id');
        $this->items = [
            ['product_id' => null, 'quantity' => '1', 'unit_price' => '0'],
        ];
    }

    public function updatedItems($value, string $key): void
    {
        if (! str_ends_with($key, 'product_id')) {
            return;
        }

        [$index] = explode('.', $key);
        $productId = $this->items[(int) $index]['product_id'] ?? null;
        if (! $productId) {
            return;
        }

        $product = Product::query()->find($productId);
        if ($product) {
            $this->items[(int) $index]['unit_price'] = (string) $product->selling_price;
        }
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => null, 'quantity' => '1', 'unit_price' => '0'];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(SaleService $sales): void
    {
        $user = Auth::user();
        abort_unless($user->can('create', Sale::class), 403);
        $businessId = $user->business_id;

        $this->validate([
            'customerId' => [
                'nullable',
                Rule::exists('customers', 'id')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'warehouseId' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'saleDate' => ['required', 'date'],
            'tax' => ['required', 'numeric', 'min:0'],
            'discount' => ['required', 'numeric', 'min:0'],
            'paymentAmount' => ['required', 'numeric', 'min:0'],
            'paymentMethod' => ['required', 'string', 'max:32'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $sale = $sales->createCompleted($user->business, $user, [
                'customer_id' => $this->customerId,
                'warehouse_id' => $this->warehouseId,
                'sale_date' => $this->saleDate,
                'tax' => $this->tax,
                'discount' => $this->discount,
                'payment_amount' => $this->paymentAmount,
                'payment_method' => $this->paymentMethod,
                'notes' => $this->notes,
                'items' => $this->items,
            ]);
        } catch (ValidationException $e) {
            $this->addValidationException($e, 'items');

            return;
        }

        session()->flash('status', 'Sale completed and invoice generated.');
        $this->redirectAndRefresh(route('business.sales.show', $sale));
    }

    public function render(): View
    {
        return view('livewire.business.sale-form', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
