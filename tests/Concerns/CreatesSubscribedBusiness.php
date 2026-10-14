<?php

namespace Tests\Concerns;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

trait CreatesSubscribedBusiness
{
    protected function subscribedOwner(array $planOverrides = []): User
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create($planOverrides);

        Subscription::factory()->create([
            'business_id' => $user->business_id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'ends_at' => now()->addMonth(),
            'grace_ends_at' => now()->addMonth()->addDays(7),
            'current_period_start' => now()->startOfDay(),
            'current_period_end' => now()->addMonth()->subSecond(),
        ]);

        return $user->fresh();
    }
}
