<?php

namespace Tests\Feature\Inventory;

use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesSubscribedBusiness;
use Tests\TestCase;

class WarehouseLimitTest extends TestCase
{
    use CreatesSubscribedBusiness;
    use RefreshDatabase;

    public function test_warehouse_limit_is_enforced(): void
    {
        $owner = $this->subscribedOwner();
        $plan = $owner->business->subscriptions()->first()->plan;
        $plan->features()->where('feature_key', 'warehouses_limit')->update(['limit_value' => 1]);

        Warehouse::factory()->create(['business_id' => $owner->business_id, 'code' => 'WH1']);

        $this->expectException(ValidationException::class);

        app(WarehouseService::class)->create($owner->business, $owner, [
            'name' => 'Second',
            'code' => 'WH2',
            'is_default' => false,
            'is_active' => true,
        ]);
    }

    public function test_low_stock_detection(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $product = \App\Models\Product::factory()->create([
            'business_id' => $owner->business_id,
            'reorder_level' => 10,
        ]);

        app(\App\Services\InventoryService::class)->adjust(
            $product,
            $warehouse,
            \App\Enums\StockMovementType::AdjustmentIn,
            4,
            $owner,
        );

        $low = app(\App\Services\InventoryService::class)->lowStockProducts($owner->business_id);

        $this->assertCount(1, $low);
        $this->assertSame($product->id, $low[0]->id);
    }
}
