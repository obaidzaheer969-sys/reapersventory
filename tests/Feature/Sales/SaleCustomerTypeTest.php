<?php

namespace Tests\Feature\Sales;

use App\Enums\SaleCustomerType;
use App\Enums\StockMovementType;
use App\Livewire\Business\SaleForm;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesSubscribedBusiness;
use Tests\TestCase;

class SaleCustomerTypeTest extends TestCase
{
    use CreatesSubscribedBusiness;
    use RefreshDatabase;

    public function test_sale_form_supports_walk_in_online_and_named_customer(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->default()->create(['business_id' => $owner->business_id]);
        $customer = Customer::factory()->create([
            'business_id' => $owner->business_id,
            'name' => 'Ali Traders',
        ]);
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'selling_price' => 50,
        ]);
        app(InventoryService::class)->adjust($product, $warehouse, StockMovementType::AdjustmentIn, 20, $owner);

        Livewire::actingAs($owner)
            ->test(SaleForm::class)
            ->assertSee('Walk-in customer')
            ->assertSee('Online order')
            ->assertSee('Ali Traders');

        $online = app(SaleService::class)->createCompleted($owner->business, $owner, [
            'customer_type' => SaleCustomerType::Online->value,
            'warehouse_id' => $warehouse->id,
            'sale_date' => now()->toDateString(),
            'payment_amount' => 50,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50]],
        ]);

        $this->assertSame(SaleCustomerType::Online, $online->customer_type);
        $this->assertNull($online->customer_id);
        $this->assertSame('Online order', $online->customerLabel());

        $named = app(SaleService::class)->createCompleted($owner->business, $owner, [
            'customer_type' => SaleCustomerType::Customer->value,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'sale_date' => now()->toDateString(),
            'payment_amount' => 50,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50]],
        ]);

        $this->assertSame(SaleCustomerType::Customer, $named->customer_type);
        $this->assertSame('Ali Traders', $named->customerLabel());

        Livewire::actingAs($owner)
            ->test(SaleForm::class)
            ->set('customerSelection', 'online')
            ->set('warehouseId', $warehouse->id)
            ->set('paymentAmount', '50')
            ->set('items.0.product_id', $product->id)
            ->set('items.0.quantity', '1')
            ->set('items.0.unit_price', '50')
            ->call('save')
            ->assertHasNoErrors();
    }
}
