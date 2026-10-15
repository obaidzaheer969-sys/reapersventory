<?php

namespace Tests\Feature\Business;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_can_view_own_dashboard_only(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $this->actingAs($ownerA)
            ->get(route('business.dashboard'))
            ->assertOk()
            ->assertSee($ownerA->business->name)
            ->assertDontSee($ownerB->business->name);
    }

    public function test_business_policy_denies_access_to_another_business(): void
    {
        $ownerA = User::factory()->create();
        $otherBusiness = Business::factory()->create();

        $this->assertFalse($ownerA->can('view', $otherBusiness));
        $this->assertFalse($ownerA->can('update', $otherBusiness));
        $this->assertTrue($ownerA->can('view', $ownerA->business));
        $this->assertTrue($ownerA->can('update', $ownerA->business));
    }

    public function test_super_admin_can_view_any_business_but_not_via_owner_routes(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $business = Business::factory()->create();

        $this->assertTrue($superAdmin->can('view', $business));

        $this->actingAs($superAdmin)
            ->get(route('business.dashboard'))
            ->assertForbidden();
    }

    public function test_unverified_users_can_access_business_dashboard_without_email_activation(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('business.dashboard'))
            ->assertOk();
    }
}
