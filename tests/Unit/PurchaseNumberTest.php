<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSubscribedBusiness;
use Tests\TestCase;

class PurchaseNumberTest extends TestCase
{
    use CreatesSubscribedBusiness;
    use RefreshDatabase;

    public function test_purchase_numbers_are_unique_per_business(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $supplier = Supplier::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create(['business_id' => $owner->business_id]);

        $service = app(PurchaseService::class);

        $first = $service->create($owner->business, $owner, [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'purchase_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 5]],
        ]);

        $second = $service->create($owner->business, $owner, [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'purchase_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 5]],
        ]);

        $this->assertSame('PO-000001', $first->purchase_number);
        $this->assertSame('PO-000002', $second->purchase_number);
        $this->assertNotSame($first->purchase_number, $second->purchase_number);
    }
}
