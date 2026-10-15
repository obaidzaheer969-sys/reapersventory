<?php

namespace Tests\Feature\Subscription;

use App\Enums\PlanFeatureKey;
use App\Models\User;
use App\Services\TeamUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesSubscribedBusiness;
use Tests\TestCase;

class TeamUserLimitTest extends TestCase
{
    use CreatesSubscribedBusiness;
    use RefreshDatabase;

    public function test_team_user_limit_is_enforced(): void
    {
        $owner = $this->subscribedOwner();
        $plan = $owner->business->subscriptions()->first()->plan;
        $plan->features()->where('feature_key', PlanFeatureKey::UsersLimit->value)->update(['limit_value' => 1]);

        $this->expectException(ValidationException::class);

        app(TeamUserService::class)->create($owner->business, $owner, [
            'name' => 'Teammate',
            'email' => 'teammate@example.com',
            'password' => 'password',
        ]);
    }

    public function test_team_user_can_be_created_under_limit(): void
    {
        $owner = $this->subscribedOwner();
        $plan = $owner->business->subscriptions()->first()->plan;
        $plan->features()->where('feature_key', PlanFeatureKey::UsersLimit->value)->update(['limit_value' => 2]);

        $member = app(TeamUserService::class)->create($owner->business, $owner, [
            'name' => 'Teammate',
            'email' => 'teammate@example.com',
            'password' => 'password',
        ]);

        $this->assertSame($owner->business_id, $member->business_id);
        $this->assertSame(2, User::query()->where('business_id', $owner->business_id)->count());
    }

    public function test_business_plan_allows_two_users_and_two_warehouses(): void
    {
        $owner = $this->subscribedOwner();
        $plan = $owner->business->subscriptions()->first()->plan;
        $plan->features()->where('feature_key', PlanFeatureKey::UsersLimit->value)->update(['limit_value' => 2]);
        $plan->features()->where('feature_key', PlanFeatureKey::WarehousesLimit->value)->update(['limit_value' => 2]);

        app(TeamUserService::class)->create($owner->business, $owner, [
            'name' => 'Second',
            'email' => 'second@example.com',
            'password' => 'password',
        ]);

        $this->assertSame(2, User::query()->where('business_id', $owner->business_id)->count());
        $this->assertSame(2, $plan->fresh()->featureLimit(PlanFeatureKey::UsersLimit));
        $this->assertSame(2, $plan->fresh()->featureLimit(PlanFeatureKey::WarehousesLimit));
    }
}
