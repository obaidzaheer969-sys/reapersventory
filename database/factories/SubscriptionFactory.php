<?php

namespace Database\Factories;

use App\Enums\BillingInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $start = now()->startOfDay();
        $end = $start->copy()->addMonth()->subSecond();

        return [
            'business_id' => Business::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Active,
            'billing_interval' => BillingInterval::Monthly,
            'trial_ends_at' => null,
            'starts_at' => $start,
            'ends_at' => $end,
            'grace_ends_at' => $end->copy()->addDays(7),
            'current_period_start' => $start,
            'current_period_end' => $end,
            'cancelled_at' => null,
            'auto_renew' => false,
        ];
    }

    public function trial(): static
    {
        return $this->state(function () {
            $start = now();
            $end = $start->copy()->addDays(7);

            return [
                'status' => SubscriptionStatus::Trial,
                'trial_ends_at' => $end,
                'starts_at' => $start,
                'ends_at' => $end,
                'current_period_start' => $start,
                'current_period_end' => $end,
            ];
        });
    }

    public function pastDue(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::PastDue,
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }
}
