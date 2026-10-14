<?php

namespace Tests\Unit;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_grace_status_allows_access_until_grace_ends(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::Grace,
            'ends_at' => now()->subDay(),
            'grace_ends_at' => now()->addDays(3),
        ]);

        $this->assertTrue($subscription->allowsAccess());
        $this->assertFalse($subscription->allowsAccess(now()->addDays(4)));
    }

    public function test_active_status_allows_access_during_grace_window(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::Active,
            'ends_at' => now()->subHour(),
            'grace_ends_at' => now()->addDays(3),
        ]);

        $this->assertTrue($subscription->allowsAccess());
        $this->assertFalse($subscription->allowsAccess(now()->addDays(4)));
    }

    public function test_expired_status_never_allows_access(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::Expired,
            'ends_at' => now()->addDay(),
            'grace_ends_at' => now()->addDays(5),
        ]);

        $this->assertFalse($subscription->allowsAccess());
    }
}
