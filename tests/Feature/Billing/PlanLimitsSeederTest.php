<?php

namespace Tests\Feature\Billing;

use App\Enums\PlanFeatureKey;
use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanLimitsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_seeder_creates_four_plans_with_expected_limits(): void
    {
        $this->seed(PlanSeeder::class);

        $starter = Plan::query()->where('slug', 'starter')->with('features')->firstOrFail();
        $grow = Plan::query()->where('slug', 'grow')->with('features')->firstOrFail();
        $business = Plan::query()->where('slug', 'business')->with('features')->firstOrFail();
        $growBusiness = Plan::query()->where('slug', 'grow-business')->with('features')->firstOrFail();

        $this->assertSame(10, $starter->featureLimit(PlanFeatureKey::ProductsLimit));
        $this->assertSame(1, $starter->featureLimit(PlanFeatureKey::WarehousesLimit));
        $this->assertNull($starter->featureLimit(PlanFeatureKey::InvoicesLimit));

        $this->assertSame(30, $grow->featureLimit(PlanFeatureKey::ProductsLimit));
        $this->assertSame(1, $grow->featureLimit(PlanFeatureKey::WarehousesLimit));
        $this->assertNull($grow->featureLimit(PlanFeatureKey::InvoicesLimit));

        $this->assertNull($business->featureLimit(PlanFeatureKey::ProductsLimit));
        $this->assertSame(2, $business->featureLimit(PlanFeatureKey::WarehousesLimit));
        $this->assertNull($business->featureLimit(PlanFeatureKey::InvoicesLimit));

        $this->assertNull($growBusiness->featureLimit(PlanFeatureKey::ProductsLimit));
        $this->assertSame(3, $growBusiness->featureLimit(PlanFeatureKey::WarehousesLimit));
        $this->assertNull($growBusiness->featureLimit(PlanFeatureKey::InvoicesLimit));
    }
}
