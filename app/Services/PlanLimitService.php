<?php

namespace App\Services;

use App\Enums\PlanFeatureKey;
use App\Models\Business;
use App\Models\Subscription;
use Illuminate\Validation\ValidationException;

class PlanLimitService
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function assertWithinLimit(Business $business, PlanFeatureKey $feature, int $currentCount): void
    {
        $subscription = $this->subscriptions->currentAccessibleSubscription($business);

        if (! $subscription) {
            throw ValidationException::withMessages([
                'subscription' => 'An active subscription is required for this action.',
            ]);
        }

        $limit = $subscription->plan->featureLimit($feature);

        if ($limit === null) {
            return; // unlimited
        }

        if ($currentCount >= $limit) {
            $message = match ($feature) {
                PlanFeatureKey::ProductsLimit => "Your plan allows only {$limit} products. Upgrade to add more.",
                PlanFeatureKey::WarehousesLimit => "Your plan allows only {$limit} warehouse(s). Upgrade to add more.",
                PlanFeatureKey::InvoicesLimit => "Your plan invoice limit ({$limit}) for this month has been reached.",
                PlanFeatureKey::UsersLimit => "Your plan allows only {$limit} user(s). Upgrade to add more.",
            };

            throw ValidationException::withMessages([
                'limit' => $message,
            ]);
        }
    }

    public function remaining(Business $business, PlanFeatureKey $feature, int $currentCount): ?int
    {
        $subscription = $this->subscriptions->currentAccessibleSubscription($business);

        if (! $subscription) {
            return 0;
        }

        $limit = $subscription->plan->featureLimit($feature);

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $currentCount);
    }

    public function requireAccessibleSubscription(Business $business): Subscription
    {
        $subscription = $this->subscriptions->currentAccessibleSubscription($business);

        if (! $subscription) {
            throw ValidationException::withMessages([
                'subscription' => 'Your subscription is inactive or expired.',
            ]);
        }

        return $subscription;
    }
}
