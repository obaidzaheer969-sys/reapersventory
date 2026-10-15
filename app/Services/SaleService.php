<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PlanFeatureKey;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly PlanLimitService $planLimits,
        private readonly AuditLogger $auditLogger,
        private readonly BusinessNotifier $notifier,
    ) {}

    /**
     * @param  array{
     *   customer_id?: int|null,
     *   warehouse_id: int,
     *   sale_date: string,
     *   tax?: float|string,
     *   tax_percent?: float|string|null,
     *   discount?: float|string,
     *   discount_percent?: float|string|null,
     *   notes?: string|null,
     *   payment_amount?: float|string|null,
     *   payment_method?: string|null,
     *   items: list<array{product_id: int, quantity: float|string, unit_price?: float|string|null}>
     * }  $data
     */
    public function createCompleted(Business $business, User $actor, array $data): Sale
    {
        $this->planLimits->requireAccessibleSubscription($business);
        $this->assertMonthlyInvoiceLimit($business);

        $sale = DB::transaction(function () use ($business, $actor, $data) {
            $warehouse = Warehouse::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->findOrFail($data['warehouse_id']);

            $customer = null;
            if (! empty($data['customer_id'])) {
                $customer = Customer::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->findOrFail($data['customer_id']);
            }

            if (empty($data['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'At least one sale item is required.',
                ]);
            }

            [$subtotal, $normalizedItems] = $this->normalizeItems($business->id, $data['items']);

            if (array_key_exists('discount_percent', $data) && $data['discount_percent'] !== null && $data['discount_percent'] !== '') {
                $discountPercent = number_format((float) $data['discount_percent'], 2, '.', '');
                $discount = bcmul($subtotal, bcdiv($discountPercent, '100', 6), 2);
            } else {
                $discount = number_format((float) ($data['discount'] ?? 0), 2, '.', '');
            }

            if (array_key_exists('tax_percent', $data) && $data['tax_percent'] !== null && $data['tax_percent'] !== '') {
                $percent = number_format((float) $data['tax_percent'], 2, '.', '');
                $tax = bcmul($subtotal, bcdiv($percent, '100', 6), 2);
            } else {
                $tax = number_format((float) ($data['tax'] ?? 0), 2, '.', '');
            }

            $total = bcsub(bcadd($subtotal, $tax, 2), $discount, 2);

            if (bccomp($total, '0', 2) < 0) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount cannot exceed subtotal plus tax.',
                ]);
            }

            $paymentAmount = number_format((float) ($data['payment_amount'] ?? 0), 2, '.', '');
            if (bccomp($paymentAmount, $total, 2) > 0) {
                throw ValidationException::withMessages([
                    'payment_amount' => 'Payment cannot exceed the sale total. Bill total is '.$total.'.',
                ]);
            }

            $amountDue = bcsub($total, $paymentAmount, 2);

            $sale = Sale::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'customer_id' => $customer?->id,
                'warehouse_id' => $warehouse->id,
                'sale_number' => $this->nextNumber($business->id, 'sale'),
                'status' => SaleStatus::Completed,
                'sale_date' => $data['sale_date'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'amount_paid' => $paymentAmount,
                'amount_due' => $amountDue,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($normalizedItems as $item) {
                SaleItem::withoutGlobalScopes()->create([
                    'business_id' => $business->id,
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $item['line_total'],
                    'returned_quantity' => 0,
                ]);

                $product = Product::withoutGlobalScopes()->findOrFail($item['product_id']);
                if ($product->track_inventory) {
                    $this->inventory->adjust(
                        product: $product,
                        warehouse: $warehouse,
                        type: StockMovementType::SaleOut,
                        quantity: $item['quantity'],
                        actor: $actor,
                        reference: $sale,
                        notes: "Sale {$sale->sale_number}",
                    );
                }
            }

            $invoiceStatus = match (true) {
                bccomp($amountDue, '0', 2) === 0 => InvoiceStatus::Paid,
                bccomp($paymentAmount, '0', 2) > 0 => InvoiceStatus::Partial,
                default => InvoiceStatus::Unpaid,
            };

            $invoice = Invoice::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'sale_id' => $sale->id,
                'customer_id' => $customer?->id,
                'invoice_number' => $this->nextNumber($business->id, 'invoice'),
                'status' => $invoiceStatus,
                'issued_at' => $data['sale_date'],
                'due_at' => $data['sale_date'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'amount_paid' => $paymentAmount,
                'amount_due' => $amountDue,
            ]);

            if (bccomp($paymentAmount, '0', 2) > 0) {
                SalePayment::withoutGlobalScopes()->create([
                    'business_id' => $business->id,
                    'sale_id' => $sale->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $paymentAmount,
                    'method' => $data['payment_method'] ?? 'cash',
                    'paid_at' => now(),
                    'created_by' => $actor->id,
                ]);
            }

            $this->auditLogger->log(
                action: 'sale.completed',
                auditable: $sale,
                newValues: [
                    'sale_number' => $sale->sale_number,
                    'invoice_number' => $invoice->invoice_number,
                    'total' => $sale->total,
                ],
                user: $actor,
            );

            return $sale->load(['items.product', 'customer', 'warehouse', 'invoice', 'payments']);
        });

        try {
            $this->notifier->saleCompleted($sale);
        } catch (\Throwable) {
            // Never fail the sale because notifications are unavailable.
        }

        return $sale;
    }

    /**
     * @param  array{amount: float|string, method?: string, reference?: string|null, notes?: string|null, paid_at?: string|null}  $data
     */
    public function recordPayment(Sale $sale, User $actor, array $data): SalePayment
    {
        $this->planLimits->requireAccessibleSubscription($sale->business);

        return DB::transaction(function () use ($sale, $actor, $data) {
            $sale = Sale::withoutGlobalScopes()
                ->with('invoice')
                ->whereKey($sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->status !== SaleStatus::Completed) {
                throw ValidationException::withMessages([
                    'status' => 'Payments can only be recorded for completed sales.',
                ]);
            }

            $amount = number_format((float) $data['amount'], 2, '.', '');
            if (bccomp($amount, '0', 2) <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            if (bccomp($amount, (string) $sale->amount_due, 2) > 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot exceed the outstanding balance.',
                ]);
            }

            $payment = SalePayment::withoutGlobalScopes()->create([
                'business_id' => $sale->business_id,
                'sale_id' => $sale->id,
                'invoice_id' => $sale->invoice?->id,
                'amount' => $amount,
                'method' => $data['method'] ?? 'cash',
                'paid_at' => $data['paid_at'] ?? now(),
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $paid = bcadd((string) $sale->amount_paid, $amount, 2);
            $due = bcsub((string) $sale->total, $paid, 2);

            $sale->update([
                'amount_paid' => $paid,
                'amount_due' => $due,
            ]);

            if ($sale->invoice) {
                $status = match (true) {
                    bccomp($due, '0', 2) === 0 => InvoiceStatus::Paid,
                    bccomp($paid, '0', 2) > 0 => InvoiceStatus::Partial,
                    default => InvoiceStatus::Unpaid,
                };

                $sale->invoice->update([
                    'amount_paid' => $paid,
                    'amount_due' => $due,
                    'status' => $status,
                ]);
            }

            $this->auditLogger->log(
                action: 'sale.payment_recorded',
                auditable: $payment,
                newValues: ['amount' => $amount, 'sale_id' => $sale->id],
                user: $actor,
            );

            return $payment;
        });
    }

    private function assertMonthlyInvoiceLimit(Business $business): void
    {
        $count = Invoice::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereBetween('issued_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        $this->planLimits->assertWithinLimit($business, PlanFeatureKey::InvoicesLimit, $count);
    }

    /**
     * @param  list<array{product_id: int, quantity: float|string, unit_price?: float|string|null}>  $items
     * @return array{0: string, 1: list<array{product_id: int, quantity: string, unit_price: string, unit_cost: string, line_total: string}>}
     */
    private function normalizeItems(int $businessId, array $items): array
    {
        $normalized = [];
        $subtotal = '0.00';

        foreach ($items as $index => $item) {
            $product = Product::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->find($item['product_id'] ?? null);

            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => 'Invalid product for this business.',
                ]);
            }

            $quantity = number_format((float) $item['quantity'], 3, '.', '');
            $unitPrice = number_format((float) ($item['unit_price'] ?? $product->selling_price), 2, '.', '');
            $unitCost = number_format((float) $product->cost_price, 2, '.', '');

            if (bccomp($quantity, '0', 3) <= 0) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => 'Quantity must be greater than zero.',
                ]);
            }

            $lineTotal = bcmul($quantity, $unitPrice, 2);
            $subtotal = bcadd($subtotal, $lineTotal, 2);

            $normalized[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
            ];
        }

        return [$subtotal, $normalized];
    }

    private function nextNumber(int $businessId, string $type): string
    {
        if ($type === 'invoice') {
            $count = Invoice::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->withTrashed()
                ->count() + 1;

            return 'INV-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT);
        }

        $count = Sale::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->withTrashed()
            ->count() + 1;

        return 'SL-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
