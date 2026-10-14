<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Models\Business;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly PlanLimitService $planLimits,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{
     *   supplier_id: int,
     *   warehouse_id: int,
     *   purchase_date: string,
     *   tax?: float|string,
     *   discount?: float|string,
     *   notes?: string|null,
     *   items: list<array{product_id: int, quantity: float|string, unit_cost: float|string}>
     * }  $data
     */
    public function create(Business $business, User $actor, array $data): Purchase
    {
        $this->planLimits->requireAccessibleSubscription($business);

        return DB::transaction(function () use ($business, $actor, $data) {
            $supplier = Supplier::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->findOrFail($data['supplier_id']);

            $warehouse = Warehouse::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->findOrFail($data['warehouse_id']);

            if (empty($data['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'At least one purchase item is required.',
                ]);
            }

            [$subtotal, $normalizedItems] = $this->normalizeItems($business->id, $data['items']);
            $tax = number_format((float) ($data['tax'] ?? 0), 2, '.', '');
            $discount = number_format((float) ($data['discount'] ?? 0), 2, '.', '');
            $total = bcsub(bcadd($subtotal, $tax, 2), $discount, 2);

            if (bccomp($total, '0', 2) < 0) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount cannot exceed subtotal plus tax.',
                ]);
            }

            $purchase = Purchase::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'purchase_number' => $this->nextPurchaseNumber($business->id),
                'status' => PurchaseStatus::Ordered,
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($normalizedItems as $item) {
                PurchaseItem::withoutGlobalScopes()->create([
                    'business_id' => $business->id,
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $item['line_total'],
                    'received_quantity' => 0,
                ]);
            }

            $this->auditLogger->log(
                action: 'purchase.created',
                auditable: $purchase,
                newValues: [
                    'purchase_number' => $purchase->purchase_number,
                    'total' => $purchase->total,
                    'status' => $purchase->status->value,
                ],
                user: $actor,
            );

            return $purchase->load('items.product', 'supplier', 'warehouse');
        });
    }

    public function receive(Purchase $purchase, User $actor): Purchase
    {
        $this->planLimits->requireAccessibleSubscription($purchase->business);

        return DB::transaction(function () use ($purchase, $actor) {
            $purchase = Purchase::withoutGlobalScopes()
                ->with(['items.product', 'warehouse'])
                ->whereKey($purchase->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $purchase->status->canReceive()) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft/ordered purchases can be received.',
                ]);
            }

            foreach ($purchase->items as $item) {
                $remaining = bcsub((string) $item->quantity, (string) $item->received_quantity, 3);

                if (bccomp($remaining, '0', 3) <= 0) {
                    continue;
                }

                $this->inventory->adjust(
                    product: $item->product,
                    warehouse: $purchase->warehouse,
                    type: StockMovementType::PurchaseIn,
                    quantity: $remaining,
                    actor: $actor,
                    reference: $purchase,
                    notes: "Purchase {$purchase->purchase_number}",
                );

                $item->update([
                    'received_quantity' => $item->quantity,
                ]);

                // Keep product cost snapshot updated from latest purchase cost.
                $item->product->update([
                    'cost_price' => $item->unit_cost,
                ]);
            }

            $purchase->update([
                'status' => PurchaseStatus::Received,
                'received_at' => now(),
            ]);

            $this->auditLogger->log(
                action: 'purchase.received',
                auditable: $purchase,
                newValues: [
                    'status' => PurchaseStatus::Received->value,
                    'received_at' => now()->toDateTimeString(),
                ],
                user: $actor,
            );

            return $purchase->refresh()->load('items.product', 'supplier', 'warehouse');
        });
    }

    public function cancel(Purchase $purchase, User $actor): Purchase
    {
        return DB::transaction(function () use ($purchase, $actor) {
            $purchase = Purchase::withoutGlobalScopes()
                ->whereKey($purchase->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($purchase->status === PurchaseStatus::Received) {
                throw ValidationException::withMessages([
                    'status' => 'Received purchases cannot be cancelled. Create a return instead (Milestone 4).',
                ]);
            }

            if ($purchase->status === PurchaseStatus::Cancelled) {
                return $purchase;
            }

            $purchase->update(['status' => PurchaseStatus::Cancelled]);

            $this->auditLogger->log(
                action: 'purchase.cancelled',
                auditable: $purchase,
                newValues: ['status' => PurchaseStatus::Cancelled->value],
                user: $actor,
            );

            return $purchase->refresh();
        });
    }

    /**
     * @param  list<array{product_id: int, quantity: float|string, unit_cost: float|string}>  $items
     * @return array{0: string, 1: list<array{product_id: int, quantity: string, unit_cost: string, line_total: string}>}
     */
    private function normalizeItems(int $businessId, array $items): array
    {
        $normalized = [];
        $subtotal = '0.00';

        foreach ($items as $index => $item) {
            $product = Product::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->find($item['product_id'] ?? null);

            if (! $product) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => 'Invalid product for this business.',
                ]);
            }

            $quantity = number_format((float) $item['quantity'], 3, '.', '');
            $unitCost = number_format((float) $item['unit_cost'], 2, '.', '');

            if (bccomp($quantity, '0', 3) <= 0) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => 'Quantity must be greater than zero.',
                ]);
            }

            if (bccomp($unitCost, '0', 2) < 0) {
                throw ValidationException::withMessages([
                    "items.$index.unit_cost" => 'Unit cost cannot be negative.',
                ]);
            }

            $lineTotal = bcmul($quantity, $unitCost, 2);
            $subtotal = bcadd($subtotal, $lineTotal, 2);

            $normalized[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
            ];
        }

        return [$subtotal, $normalized];
    }

    private function nextPurchaseNumber(int $businessId): string
    {
        $count = Purchase::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->withTrashed()
            ->count() + 1;

        return 'PO-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
