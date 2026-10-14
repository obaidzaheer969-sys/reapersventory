<?php

namespace Tests\Feature\Inventory;

use App\Livewire\Business\ProductForm;
use App\Models\Product;
use App\Models\User;
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
            ->set('sellingPrice', '250')
            ->set('costPrice', '100')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'business_id' => $owner->business_id,
            'sku' => 'WDG-1',
            'name' => 'Widget',
        ]);
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
