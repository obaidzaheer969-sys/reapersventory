<?php

namespace Database\Factories;

use App\Enums\BillingInterval;
use App\Enums\PaymentStatus;
use App\Models\Business;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPayment>
 */
class SubscriptionPaymentFactory extends Factory
{
    protected $model = SubscriptionPayment::class;

    public function definition(): array
    {
        $start = now()->startOfDay();
        $end = $start->copy()->addMonth()->subSecond();

        return [
            'business_id' => Business::factory(),
            'subscription_id' => Subscription::factory(),
            'plan_id' => Plan::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'amount' => 2999,
            'currency' => 'PKR',
            'billing_interval' => BillingInterval::Monthly,
            'period_start' => $start,
            'period_end' => $end,
            'status' => PaymentStatus::Pending,
            'reference' => fake()->optional()->bothify('TXN-####'),
            'notes' => null,
            'submitted_by' => User::factory(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_notes' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }
}
