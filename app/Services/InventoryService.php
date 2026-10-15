<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Resolve monitor lazily to avoid circular constructor deps.
     */
    private function lowStockMonitor(): LowStockMonitor
    {
        return app(LowStockMonitor::class);
    }

    /**
     * Adjust stock transactionally and write an immutable movement row.
     */
    public function adjust(
        Product $product,
        Warehouse $warehouse,
        StockMovementType $type,
        string|float|int $quantity,
        ?User $actor = null,
        ?Model $reference = null,
        ?string $notes = null,
    ): StockMovement {
        $qty = $this->normalizeQuantity($quantity);

        if (bccomp($qty, '0', 3) <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Stock quantity must be greater than zero.',
            ]);
        }

        if ($product->business_id !== $warehouse->business_id) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Warehouse and product must belong to the same business.',
            ]);
        }

        if (! $product->track_inventory) {
            throw ValidationException::withMessages([
                'product_id' => 'Inventory tracking is disabled for this product.',
            ]);
        }

        $movement = DB::transaction(function () use ($product, $warehouse, $type, $qty, $actor, $reference, $notes) {
            $balance = InventoryBalance::withoutGlobalScopes()
                ->where('business_id', $product->business_id)
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = InventoryBalance::withoutGlobalScopes()->create([
                    'business_id' => $product->business_id,
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);

                $balance = InventoryBalance::withoutGlobalScopes()
                    ->whereKey($balance->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $current = (string) $balance->quantity;
            $next = $type->isInbound()
                ? bcadd($current, $qty, 3)
                : bcsub($current, $qty, 3);

            if (bccomp($next, '0', 3) < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock for this movement.',
                ]);
            }

            $balance->update(['quantity' => $next]);

            $movement = StockMovement::withoutGlobalScopes()->create([
                'business_id' => $product->business_id,
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $qty,
                'balance_after' => $next,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->getKey(),
                'notes' => $notes,
                'created_by' => $actor?->id,
            ]);

            $this->auditLogger->log(
                action: 'inventory.adjusted',
                auditable: $movement,
                newValues: [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'type' => $type->value,
                    'quantity' => $qty,
                    'balance_after' => $next,
                ],
                user: $actor,
            );

            return $movement;
        });

        try {
            $this->lowStockMonitor()->checkProduct($product->fresh() ?? $product);
        } catch (\Throwable) {
            // never block stock movements
        }

        return $movement;
    }

    /**
     * @return list<\App\Models\Product>
     */
    public function lowStockProducts(int $businessId): array
    {
        return Product::withoutGlobalScopes()
            ->with(['inventoryBalances' => fn ($q) => $q->withoutGlobalScopes()])
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->where('track_inventory', true)
            ->get()
            ->filter(function (Product $product) {
                $total = number_format(
                    (float) $product->inventoryBalances->sum(fn ($b) => (float) $b->quantity),
                    3,
                    '.',
                    ''
                );

                return bccomp($total, number_format((float) $product->reorder_level, 3, '.', ''), 3) <= 0;
            })
            ->values()
            ->all();
    }

    private function normalizeQuantity(string|float|int $quantity): string
    {
        return number_format((float) $quantity, 3, '.', '');
    }
}
