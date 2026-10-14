<?php

namespace Tests\Feature\Subscription;

use App\Enums\PlanFeatureKey;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlanLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_limit_service_blocks_when_limit_reached(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        Subscription::factory()->create([
            'business_id' => $user->business_id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'ends_at' => now()->addMonth(),
            'current_period_end' => now()->addMonth(),
        ]);

        $service = app(PlanLimitService::class);

        $this->expectException(ValidationException::class);
        $service->assertWithinLimit($user->business, PlanFeatureKey::ProductsLimit, 100);
    }

    public function test_plan_limit_service_allows_under_limit(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        Subscription::factory()->create([
            'business_id' => $user->business_id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'ends_at' => now()->addMonth(),
            'current_period_end' => now()->addMonth(),
        ]);

        app(PlanLimitService::class)
            ->assertWithinLimit($user->business, PlanFeatureKey::ProductsLimit, 10);

        $this->assertTrue(true);
    }

    public function test_missing_subscription_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);
        app(PlanLimitService::class)
            ->assertWithinLimit($user->business, PlanFeatureKey::ProductsLimit, 0);
    }
}
