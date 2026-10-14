<?php

namespace Tests\Feature\Inventory;

use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesSubscribedBusiness;
use Tests\TestCase;

class PurchaseStockTest extends TestCase
{
    use CreatesSubscribedBusiness;
    use RefreshDatabase;

    public function test_receiving_purchase_updates_stock_transactionally(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->default()->create(['business_id' => $owner->business_id]);
        $supplier = Supplier::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'cost_price' => 10,
            'reorder_level' => 2,
        ]);

        $purchase = app(PurchaseService::class)->create($owner->business, $owner, [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'purchase_date' => now()->toDateString(),
            'tax' => 50,
            'discount' => 10,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 20],
            ],
        ]);

        $this->assertSame(PurchaseStatus::Ordered, $purchase->status);
        $this->assertSame('140.00', (string) $purchase->total); // 100 + 50 - 10

        $received = app(PurchaseService::class)->receive($purchase, $owner);

        $this->assertSame(PurchaseStatus::Received, $received->status);
        $this->assertDatabaseHas('inventory_balances', [
            'business_id' => $owner->business_id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => '5.000',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::PurchaseIn->value,
            'quantity' => '5.000',
            'balance_after' => '5.000',
        ]);
        $this->assertSame('20.00', (string) $product->fresh()->cost_price);
        $this->assertSame('100.00', (string) $received->subtotal);
    }

    public function test_purchase_totals_remain_stable_after_product_price_changes(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $supplier = Supplier::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create(['business_id' => $owner->business_id]);

        $purchase = app(PurchaseService::class)->create($owner->business, $owner, [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => 15.5],
            ],
        ]);

        $product->update(['cost_price' => 999, 'selling_price' => 999]);

        $purchase->refresh();
        $item = $purchase->items()->first();

        $this->assertSame('31.00', (string) $item->line_total);
        $this->assertSame('15.50', (string) $item->unit_cost);
        $this->assertSame('31.00', (string) $purchase->total);
    }

    public function test_stock_adjustment_cannot_go_negative(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create(['business_id' => $owner->business_id]);

        $this->expectException(ValidationException::class);

        app(InventoryService::class)->adjust(
            $product,
            $warehouse,
            \App\Enums\StockMovementType::AdjustmentOut,
            1,
            $owner,
        );
    }

    public function test_received_purchase_cannot_be_cancelled(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $supplier = Supplier::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create(['business_id' => $owner->business_id]);

        $service = app(PurchaseService::class);
        $purchase = $service->create($owner->business, $owner, [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 10],
            ],
        ]);
        $service->receive($purchase, $owner);

        $this->expectException(ValidationException::class);
        $service->cancel($purchase->fresh(), $owner);
    }

    public function test_receiving_same_purchase_twice_is_blocked(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $supplier = Supplier::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create(['business_id' => $owner->business_id]);

        $service = app(PurchaseService::class);
        $purchase = $service->create($owner->business, $owner, [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => 10],
            ],
        ]);
        $service->receive($purchase, $owner);

        $this->expectException(ValidationException::class);
        $service->receive($purchase->fresh(), $owner);

        $this->assertSame(1, StockMovement::withoutGlobalScopes()->count());
        $this->assertSame('3.000', (string) InventoryBalance::withoutGlobalScopes()->value('quantity'));
    }
}
