<?php

namespace App\Services;

use App\Enums\ReturnStatus;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly PlanLimitService $planLimits,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{
     *   return_date: string,
     *   notes?: string|null,
     *   refund_amount?: float|string|null,
     *   items: list<array{sale_item_id: int, quantity: float|string}>
     * }  $data
     */
    public function create(Sale $sale, User $actor, array $data): ProductReturn
    {
        $this->planLimits->requireAccessibleSubscription($sale->business);

        return DB::transaction(function () use ($sale, $actor, $data) {
            $sale = Sale::withoutGlobalScopes()
                ->with(['items.product', 'warehouse'])
                ->whereKey($sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->status !== SaleStatus::Completed && $sale->status !== SaleStatus::Returned) {
                throw ValidationException::withMessages([
                    'status' => 'Only completed sales can be returned.',
                ]);
            }

            if (empty($data['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'At least one return item is required.',
                ]);
            }

            $subtotal = '0.00';
            $normalized = [];

            foreach ($data['items'] as $index => $row) {
                $saleItem = SaleItem::withoutGlobalScopes()
                    ->where('sale_id', $sale->id)
                    ->where('business_id', $sale->business_id)
                    ->find($row['sale_item_id'] ?? null);

                if (! $saleItem) {
                    throw ValidationException::withMessages([
                        "items.$index.sale_item_id" => 'Invalid sale item.',
                    ]);
                }

                $qty = number_format((float) $row['quantity'], 3, '.', '');
                if (bccomp($qty, '0', 3) <= 0) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => 'Quantity must be greater than zero.',
                    ]);
                }

                if (bccomp($qty, $saleItem->returnableQuantity(), 3) > 0) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => 'Cannot return more than was sold.',
                    ]);
                }

                $lineTotal = bcmul($qty, (string) $saleItem->unit_price, 2);
                $subtotal = bcadd($subtotal, $lineTotal, 2);

                $normalized[] = [
                    'sale_item' => $saleItem,
                    'quantity' => $qty,
                    'unit_price' => (string) $saleItem->unit_price,
                    'line_total' => $lineTotal,
                ];
            }

            $refund = number_format((float) ($data['refund_amount'] ?? $subtotal), 2, '.', '');
            if (bccomp($refund, $subtotal, 2) > 0) {
                throw ValidationException::withMessages([
                    'refund_amount' => 'Refund cannot exceed returned merchandise value.',
                ]);
            }

            $return = ProductReturn::withoutGlobalScopes()->create([
                'business_id' => $sale->business_id,
                'sale_id' => $sale->id,
                'warehouse_id' => $sale->warehouse_id,
                'return_number' => $this->nextNumber($sale->business_id),
                'status' => ReturnStatus::Completed,
                'return_date' => $data['return_date'],
                'subtotal' => $subtotal,
                'refund_amount' => $refund,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($normalized as $row) {
                /** @var SaleItem $saleItem */
                $saleItem = $row['sale_item'];

                ReturnItem::withoutGlobalScopes()->create([
                    'business_id' => $sale->business_id,
                    'return_id' => $return->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'line_total' => $row['line_total'],
                ]);

                $saleItem->update([
                    'returned_quantity' => bcadd((string) $saleItem->returned_quantity, $row['quantity'], 3),
                ]);

                $product = Product::withoutGlobalScopes()->findOrFail($saleItem->product_id);
                if ($product->track_inventory) {
                    $this->inventory->adjust(
                        product: $product,
                        warehouse: $sale->warehouse,
                        type: StockMovementType::ReturnIn,
                        quantity: $row['quantity'],
                        actor: $actor,
                        reference: $return,
                        notes: "Return {$return->return_number}",
                    );
                }
            }

            $fullyReturned = $sale->items()->get()->every(
                fn (SaleItem $item) => bccomp($item->returnableQuantity(), '0', 3) === 0
            );

            if ($fullyReturned) {
                $sale->update(['status' => SaleStatus::Returned]);
            }

            $this->auditLogger->log(
                action: 'sale.returned',
                auditable: $return,
                newValues: [
                    'return_number' => $return->return_number,
                    'refund_amount' => $return->refund_amount,
                ],
                user: $actor,
            );

            return $return->load(['items.product', 'sale']);
        });
    }

    private function nextNumber(int $businessId): string
    {
        $count = ProductReturn::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->withTrashed()
            ->count() + 1;

        return 'RT-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
