<?php

namespace App\Livewire\Business;

use App\Enums\SaleCustomerType;
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

    /** walk_in | online | customer:{id} */
    public string $customerSelection = 'walk_in';

    public ?int $customerId = null;

    public ?int $warehouseId = null;

    public string $saleDate = '';

    public string $taxPercent = '0';

    public string $discountPercent = '0';

    public string $paymentAmount = '0';

    public string $paymentMethod = 'cash';

    /** @var list<string> */
    public array $taxOptions = ['0', '5', '10', '15', '20', '30', '40', '50', '60', '70', '80'];

    /** @var list<string> */
    public array $discountOptions = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];

    public ?string $notes = null;

    /** USB / keyboard barcode scanners type into this field then send Enter. */
    public string $barcodeScan = '';

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

    public function scanBarcode(): void
    {
        $code = trim($this->barcodeScan);
        $this->barcodeScan = '';
        $this->resetErrorBag('barcodeScan');

        if ($code === '') {
            return;
        }

        $user = Auth::user();
        $product = Product::query()
            ->where('business_id', $user->business_id)
            ->where('is_active', true)
            ->where(function ($query) use ($code) {
                $query->where('barcode', $code)->orWhere('sku', $code);
            })
            ->first();

        if (! $product) {
            $this->addError('barcodeScan', 'No product found for barcode/SKU: '.$code);

            return;
        }

        foreach ($this->items as $index => $item) {
            if ((int) ($item['product_id'] ?? 0) === (int) $product->id) {
                $qty = bcadd(number_format((float) ($item['quantity'] ?? 0), 3, '.', ''), '1', 3);
                $this->items[$index]['quantity'] = $qty;
                $this->items[$index]['unit_price'] = (string) $product->selling_price;

                return;
            }
        }

        foreach ($this->items as $index => $item) {
            if (empty($item['product_id'])) {
                $this->items[$index]['product_id'] = $product->id;
                $this->items[$index]['quantity'] = '1';
                $this->items[$index]['unit_price'] = (string) $product->selling_price;

                return;
            }
        }

        $this->items[] = [
            'product_id' => $product->id,
            'quantity' => '1',
            'unit_price' => (string) $product->selling_price,
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

    public function updatedCustomerSelection(string $value): void
    {
        if (str_starts_with($value, 'customer:')) {
            $this->customerId = (int) substr($value, strlen('customer:'));

            return;
        }

        $this->customerId = null;
    }

    public function payFullAmount(): void
    {
        $this->paymentAmount = $this->grandTotal;
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

        $customerType = match (true) {
            $this->customerSelection === 'online' => SaleCustomerType::Online->value,
            str_starts_with($this->customerSelection, 'customer:') => SaleCustomerType::Customer->value,
            default => SaleCustomerType::WalkIn->value,
        };

        if ($customerType === SaleCustomerType::Customer->value) {
            $this->customerId = (int) substr($this->customerSelection, strlen('customer:'));
        } else {
            $this->customerId = null;
        }

        $this->validate([
            'customerSelection' => ['required', 'string'],
            'customerId' => [
                Rule::requiredIf($customerType === SaleCustomerType::Customer->value),
                'nullable',
                Rule::exists('customers', 'id')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'warehouseId' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'saleDate' => ['required', 'date'],
            'taxPercent' => ['required', Rule::in($this->taxOptions)],
            'discountPercent' => ['required', Rule::in($this->discountOptions)],
            'paymentAmount' => ['required', 'numeric', 'min:0'],
            'paymentMethod' => ['required', 'string', Rule::in(['cash', 'bank', 'easypaisa', 'jazzcash', 'card'])],
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
                'customer_type' => $customerType,
                'warehouse_id' => $this->warehouseId,
                'sale_date' => $this->saleDate,
                'tax_percent' => $this->taxPercent,
                'discount_percent' => $this->discountPercent,
                'payment_amount' => $this->paymentAmount,
                'payment_method' => $this->paymentMethod,
                'notes' => $this->notes,
                'items' => $this->items,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->addError('items', $message);
                }
            }

            return;
        } catch (\Throwable $e) {
            report($e);

            $message = 'Sale could not be completed. Check stock/warehouse and try again.';
            if (str_contains($e->getMessage(), 'customer_type')) {
                $message = 'Server update pending: run rv-update, then try this sale again.';
            } elseif (config('app.debug')) {
                $message = $e->getMessage();
            }

            $this->addError('items', $message);

            return;
        }

        session()->flash('status', 'Sale completed. Print the small bill or start a new sale.');

        if ($sale->invoice) {
            $this->redirectAndRefresh(route('business.invoices.print', [
                'invoice' => $sale->invoice,
                'autoprint' => 1,
            ]));

            return;
        }

        $this->redirectAndRefresh(route('business.sales.show', $sale));
    }

    public function getSubtotalProperty(): string
    {
        $subtotal = '0.00';

        foreach ($this->items as $item) {
            $line = bcmul(
                number_format((float) ($item['quantity'] ?? 0), 3, '.', ''),
                number_format((float) ($item['unit_price'] ?? 0), 2, '.', ''),
                2
            );
            $subtotal = bcadd($subtotal, $line, 2);
        }

        return $subtotal;
    }

    public function getTaxAmountProperty(): string
    {
        return bcmul($this->subtotal, bcdiv(number_format((float) $this->taxPercent, 2, '.', ''), '100', 6), 2);
    }

    public function getDiscountAmountProperty(): string
    {
        return bcmul($this->subtotal, bcdiv(number_format((float) $this->discountPercent, 2, '.', ''), '100', 6), 2);
    }

    public function getGrandTotalProperty(): string
    {
        return bcsub(bcadd($this->subtotal, $this->taxAmount, 2), $this->discountAmount, 2);
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
