<?php

namespace Tests\Feature\Subscription;

use App\Enums\PlanFeatureKey;
use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanSeederLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_plans_have_expected_user_and_warehouse_limits(): void
    {
        $this->seed(PlanSeeder::class);

        $business = Plan::query()->where('slug', 'business')->firstOrFail();
        $growBusiness = Plan::query()->where('slug', 'grow-business')->firstOrFail();

        $this->assertSame(2, $business->featureLimit(PlanFeatureKey::UsersLimit));
        $this->assertSame(2, $business->featureLimit(PlanFeatureKey::WarehousesLimit));

        $this->assertSame(3, $growBusiness->featureLimit(PlanFeatureKey::UsersLimit));
        $this->assertSame(3, $growBusiness->featureLimit(PlanFeatureKey::WarehousesLimit));
    }

    public function test_ensure_feature_rows_fills_missing_keys(): void
    {
        $plan = Plan::factory()->create();
        $plan->features()->where('feature_key', PlanFeatureKey::UsersLimit->value)->delete();

        $plan->ensureFeatureRows();

        $this->assertSame(0, $plan->fresh()->featureLimit(PlanFeatureKey::UsersLimit));
    }
}
