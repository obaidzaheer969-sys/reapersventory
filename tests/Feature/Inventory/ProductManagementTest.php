<?php

namespace Tests\Feature\Inventory;

use App\Livewire\Business\ProductForm;
use App\Livewire\Business\StockAdjustmentForm;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\CreatesSubscribedBusiness;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use CreatesSubscribedBusiness;
    use RefreshDatabase;

    public function test_subscribed_owner_can_create_product(): void
    {
        $owner = $this->subscribedOwner();

        Livewire::actingAs($owner)
            ->test(ProductForm::class)
            ->set('name', 'Widget')
            ->set('sku', 'WDG-1')
            ->set('unit', 'kg')
            ->set('sellingPrice', '250')
            ->set('costPrice', '100')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'business_id' => $owner->business_id,
            'sku' => 'WDG-1',
            'name' => 'Widget',
            'unit' => 'kg',
        ]);
    }

    public function test_create_product_with_opening_stock(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create([
            'business_id' => $owner->business_id,
            'is_active' => true,
        ]);

        Livewire::actingAs($owner)
            ->test(ProductForm::class)
            ->set('name', 'Rice')
            ->set('sku', 'RICE-1')
            ->set('unit', 'kg')
            ->set('sellingPrice', '120')
            ->set('costPrice', '90')
            ->set('stockQuantity', '25.5')
            ->set('warehouseId', $warehouse->id)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->where('sku', 'RICE-1')->firstOrFail();

        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 25.5,
        ]);
    }

    public function test_edit_product_can_add_stock(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create([
            'business_id' => $owner->business_id,
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'track_inventory' => true,
            'unit' => 'pcs',
            'name' => 'Golden Pearl',
        ]);

        Livewire::actingAs($owner)
            ->test(ProductForm::class, ['product' => $product])
            ->assertSet('currentStock', '0.000')
            ->set('stockQuantity', '40')
            ->set('warehouseId', $warehouse->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 40,
        ]);
    }

    public function test_owner_can_add_stock_from_inventory_form(): void
    {
        $owner = $this->subscribedOwner();
        $warehouse = Warehouse::factory()->create([
            'business_id' => $owner->business_id,
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'track_inventory' => true,
            'unit' => 'pcs',
        ]);

        Livewire::actingAs($owner)
            ->test(StockAdjustmentForm::class, ['productId' => $product->id])
            ->set('warehouseId', $warehouse->id)
            ->set('direction', 'in')
            ->set('quantity', '12')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 12,
        ]);
    }

    public function test_subscribed_owner_can_edit_product(): void
    {
        $owner = $this->subscribedOwner();
        $product = Product::factory()->create([
            'business_id' => $owner->business_id,
            'name' => 'Old Name',
            'sku' => 'OLD-1',
            'selling_price' => 100,
        ]);

        $this->actingAs($owner)
            ->get(route('business.products.edit', $product))
            ->assertOk()
            ->assertSee('Edit product')
            ->assertSee('Old Name');

        Livewire::actingAs($owner)
            ->test(ProductForm::class, ['product' => $product])
            ->set('name', 'New Name')
            ->set('sellingPrice', '175.50')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('business.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Name',
            'selling_price' => 175.50,
        ]);
    }

    public function test_owner_cannot_edit_another_tenants_product(): void
    {
        $ownerA = $this->subscribedOwner();
        $ownerB = $this->subscribedOwner();
        $product = Product::factory()->create([
            'business_id' => $ownerB->business_id,
            'sku' => 'OTHER-1',
        ]);

        $this->actingAs($ownerA)
            ->get(route('business.products.edit', $product))
            ->assertNotFound();
    }

    public function test_product_limit_is_enforced_server_side(): void
    {
        $owner = $this->subscribedOwner();
        $plan = $owner->business->subscriptions()->first()->plan;
        $plan->features()->where('feature_key', 'products_limit')->update(['limit_value' => 1]);

        Product::factory()->create(['business_id' => $owner->business_id, 'sku' => 'ONE']);

        $this->expectException(ValidationException::class);

        app(ProductService::class)->create($owner->business, $owner, [
            'name' => 'Two',
            'sku' => 'TWO',
            'cost_price' => 1,
            'selling_price' => 2,
            'reorder_level' => 0,
            'unit' => 'pcs',
            'track_inventory' => true,
            'is_active' => true,
        ]);
    }

    public function test_sku_is_unique_per_business_but_reusable_across_tenants(): void
    {
        $ownerA = $this->subscribedOwner();
        $ownerB = $this->subscribedOwner();

        Product::factory()->create([
            'business_id' => $ownerA->business_id,
            'sku' => 'SHARED',
        ]);

        app(ProductService::class)->create($ownerB->business, $ownerB, [
            'name' => 'Other',
            'sku' => 'SHARED',
            'cost_price' => 1,
            'selling_price' => 2,
            'reorder_level' => 0,
            'unit' => 'pcs',
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $this->assertSame(2, Product::withoutGlobalScopes()->where('sku', 'SHARED')->count());
    }

    public function test_unsubscribed_owner_cannot_access_products(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->get(route('business.products.index'))
            ->assertRedirect(route('business.subscription.index'));
    }

    public function test_tenant_isolation_on_product_list(): void
    {
        $ownerA = $this->subscribedOwner();
        $ownerB = $this->subscribedOwner();

        Product::factory()->create([
            'business_id' => $ownerB->business_id,
            'name' => 'Secret Product',
            'sku' => 'SEC-1',
        ]);

        $this->actingAs($ownerA)
            ->get(route('business.products.index'))
            ->assertOk()
            ->assertDontSee('Secret Product');
    }
}
