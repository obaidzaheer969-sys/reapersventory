<?php

namespace Tests\Feature\Sales;

use App\Enums\InvoiceStatus;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\ReturnService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesSubscribedBusiness;
use Tests\TestCase;

class SaleLifecycleTest extends TestCase
{
    use CreatesSubscribedBusiness;
    use RefreshDatabase;

    public function test_completed_sale_deducts_stock_and_creates_invoice(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->default()->create(['business_id' => $owner->business_id]);
        $customer = Customer::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'cost_price' => 40,
            'selling_price' => 100,
        ]);

        app(InventoryService::class)->adjust(
            $product,
            $warehouse,
            StockMovementType::AdjustmentIn,
            10,
            $owner,
        );

        $sale = app(SaleService::class)->createCompleted($owner->business, $owner, [
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'sale_date' => now()->toDateString(),
            'tax' => 10,
            'discount' => 5,
            'payment_amount' => 50,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100],
            ],
        ]);

        $this->assertSame(SaleStatus::Completed, $sale->status);
        $this->assertSame('205.00', (string) $sale->total); // 200 + 10 - 5
        $this->assertSame('50.00', (string) $sale->amount_paid);
        $this->assertSame('155.00', (string) $sale->amount_due);
        $this->assertNotNull($sale->invoice);
        $this->assertSame(InvoiceStatus::Partial, $sale->invoice->status);
        $this->assertSame('40.00', (string) $sale->items->first()->unit_cost);
        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => '8.000',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'type' => StockMovementType::SaleOut->value,
            'quantity' => '2.000',
        ]);
    }

    public function test_sale_shows_clear_error_when_warehouse_stock_is_insufficient(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->default()->create([
            'business_id' => $owner->business_id,
            'name' => 'Test',
        ]);
        $otherWarehouse = Warehouse::factory()->create([
            'business_id' => $owner->business_id,
            'name' => 'Other',
        ]);
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'name' => 'new 7',
            'selling_price' => 200,
            'track_inventory' => true,
        ]);

        // Stock exists, but in a different warehouse than the sale warehouse.
        app(InventoryService::class)->adjust($product, $otherWarehouse, StockMovementType::AdjustmentIn, 10, $owner);

        try {
            app(SaleService::class)->createCompleted($owner->business, $owner, [
                'warehouse_id' => $warehouse->id,
                'sale_date' => now()->toDateString(),
                'payment_amount' => 1000,
                'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 200]],
            ]);
            $this->fail('Expected insufficient stock validation.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('Insufficient stock for new 7 in warehouse "Test"', $e->getMessage());
            $this->assertStringContainsString('Available: 0', $e->getMessage());
        }
    }

    public function test_tax_percent_is_added_to_bill_total(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->default()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'selling_price' => 100,
            'track_inventory' => false,
        ]);

        $sale = app(SaleService::class)->createCompleted($owner->business, $owner, [
            'warehouse_id' => $warehouse->id,
            'sale_date' => now()->toDateString(),
            'tax_percent' => 10,
            'discount_percent' => 5,
            'payment_amount' => 105,
            'payment_method' => 'card',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ]);

        // 100 + 10% tax - 5% discount = 110 - 5 = 105
        $this->assertSame('100.00', (string) $sale->subtotal);
        $this->assertSame('10.00', (string) $sale->tax);
        $this->assertSame('5.00', (string) $sale->discount);
        $this->assertSame('105.00', (string) $sale->total);
        $this->assertSame('card', $sale->payments()->value('method'));
        $this->assertSame('105.00', (string) $sale->payments()->value('amount'));
    }

    public function test_historical_sale_costs_remain_stable_after_product_cost_changes(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'cost_price' => 25,
            'selling_price' => 50,
        ]);

        app(InventoryService::class)->adjust($product, $warehouse, StockMovementType::AdjustmentIn, 5, $owner);

        $sale = app(SaleService::class)->createCompleted($owner->business, $owner, [
            'warehouse_id' => $warehouse->id,
            'sale_date' => now()->toDateString(),
            'payment_amount' => 50,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50],
            ],
        ]);

        $product->update(['cost_price' => 999, 'selling_price' => 999]);

        $item = $sale->items()->first();
        $this->assertSame('25.00', (string) $item->unit_cost);
        $this->assertSame('50.00', (string) $item->unit_price);
        $this->assertSame('50.00', (string) $sale->fresh()->total);
    }

    public function test_partial_payment_updates_invoice_status(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create(['business_id' => $owner->business_id, 'selling_price' => 100]);
        app(InventoryService::class)->adjust($product, $warehouse, StockMovementType::AdjustmentIn, 5, $owner);

        $sales = app(SaleService::class);
        $sale = $sales->createCompleted($owner->business, $owner, [
            'warehouse_id' => $warehouse->id,
            'sale_date' => now()->toDateString(),
            'payment_amount' => 20,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ]);

        $sales->recordPayment($sale, $owner, ['amount' => 80, 'method' => 'bank']);

        $sale->refresh();
        $this->assertSame('100.00', (string) $sale->amount_paid);
        $this->assertSame('0.00', (string) $sale->amount_due);
        $this->assertSame(InvoiceStatus::Paid, $sale->invoice->fresh()->status);
    }

    public function test_return_restores_stock_transactionally(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create(['business_id' => $owner->business_id, 'selling_price' => 30]);
        app(InventoryService::class)->adjust($product, $warehouse, StockMovementType::AdjustmentIn, 5, $owner);

        $sale = app(SaleService::class)->createCompleted($owner->business, $owner, [
            'warehouse_id' => $warehouse->id,
            'sale_date' => now()->toDateString(),
            'payment_amount' => 90,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 30],
            ],
        ]);

        $item = $sale->items->first();
        app(ReturnService::class)->create($sale, $owner, [
            'return_date' => now()->toDateString(),
            'items' => [
                ['sale_item_id' => $item->id, 'quantity' => 2],
            ],
        ]);

        $this->assertSame('4.000', (string) InventoryBalance::withoutGlobalScopes()
            ->where('product_id', $product->id)
            ->value('quantity'));
        $this->assertDatabaseHas('stock_movements', [
            'type' => StockMovementType::ReturnIn->value,
            'quantity' => '2.000',
        ]);
        $this->assertSame('2.000', (string) $item->fresh()->returned_quantity);
    }

    public function test_cannot_overpay_sale(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create(['business_id' => $owner->business_id]);
        app(InventoryService::class)->adjust($product, $warehouse, StockMovementType::AdjustmentIn, 2, $owner);

        $sale = app(SaleService::class)->createCompleted($owner->business, $owner, [
            'warehouse_id' => $warehouse->id,
            'sale_date' => now()->toDateString(),
            'payment_amount' => 0,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 40],
            ],
        ]);

        $this->expectException(ValidationException::class);
        app(SaleService::class)->recordPayment($sale, $owner, ['amount' => 50]);
    }

    public function test_monthly_invoice_limit_is_enforced(): void
    {
        $owner = $this->subscribedOwner();
        $plan = $owner->business->subscriptions()->first()->plan;
        $plan->features()->where('feature_key', 'invoices_limit')->update(['limit_value' => 1]);

        $warehouse = Warehouse::factory()->create(['business_id' => $owner->business_id]);
        $product = Product::factory()->create(['business_id' => $owner->business_id]);
        app(InventoryService::class)->adjust($product, $warehouse, StockMovementType::AdjustmentIn, 5, $owner);

        $sales = app(SaleService::class);
        $sales->createCompleted($owner->business, $owner, [
            'warehouse_id' => $warehouse->id,
            'sale_date' => now()->toDateString(),
            'payment_amount' => 10,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10]],
        ]);

        $this->expectException(ValidationException::class);
        $sales->createCompleted($owner->business, $owner, [
            'warehouse_id' => $warehouse->id,
            'sale_date' => now()->toDateString(),
            'payment_amount' => 10,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10]],
        ]);
    }
}
