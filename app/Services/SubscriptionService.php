<?php

namespace App\Services;

use App\Enums\BillingInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly BusinessNotifier $notifier,
    ) {}

    public function startTrialOrPending(Business $business, Plan $plan, BillingInterval $interval, User $actor): Subscription
    {
        if (! $plan->is_active) {
            throw ValidationException::withMessages([
                'plan_id' => 'This plan is not available.',
            ]);
        }

        return DB::transaction(function () use ($business, $plan, $interval, $actor) {
            $existing = Subscription::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->whereIn('status', [
                    SubscriptionStatus::Trial->value,
                    SubscriptionStatus::Active->value,
                    SubscriptionStatus::Grace->value,
                    SubscriptionStatus::PastDue->value,
                ])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'plan_id' => 'This business already has an active subscription.',
                ]);
            }

            $now = now();
            $trialDays = (int) $plan->trial_days;

            if ($trialDays > 0) {
                $trialEnds = $now->copy()->addDays($trialDays);
                $subscription = Subscription::withoutGlobalScopes()->create([
                    'business_id' => $business->id,
                    'plan_id' => $plan->id,
                    'status' => SubscriptionStatus::Trial,
                    'billing_interval' => $interval,
                    'trial_ends_at' => $trialEnds,
                    'starts_at' => $now,
                    'ends_at' => $trialEnds,
                    'current_period_start' => $now,
                    'current_period_end' => $trialEnds,
                    'auto_renew' => false,
                ]);
            } else {
                [$periodStart, $periodEnd] = $this->calculatePeriod($now, $interval);

                $subscription = Subscription::withoutGlobalScopes()->create([
                    'business_id' => $business->id,
                    'plan_id' => $plan->id,
                    'status' => SubscriptionStatus::PastDue,
                    'billing_interval' => $interval,
                    'starts_at' => null,
                    'ends_at' => null,
                    'current_period_start' => $periodStart,
                    'current_period_end' => $periodEnd,
                    'auto_renew' => false,
                ]);
            }

            $this->auditLogger->log(
                action: 'subscription.started',
                auditable: $subscription,
                newValues: $subscription->only(['plan_id', 'status', 'billing_interval']),
                user: $actor,
            );

            return $subscription;
        });
    }

    /**
     * Activate or renew a subscription for an approved payment period.
     * Idempotent: if the subscription already covers this exact period, no duplicate extension.
     */
    public function activateOrRenewForPeriod(
        Subscription $subscription,
        Carbon $periodStart,
        Carbon $periodEnd,
        BillingInterval $interval,
        ?User $actor = null,
    ): Subscription {
        return DB::transaction(function () use ($subscription, $periodStart, $periodEnd, $interval, $actor) {
            $subscription = Subscription::withoutGlobalScopes()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            $alreadyOnPeriod = $subscription->current_period_start
                && $subscription->current_period_end
                && $subscription->current_period_start->equalTo($periodStart)
                && $subscription->current_period_end->equalTo($periodEnd)
                && $subscription->status === SubscriptionStatus::Active;

            if ($alreadyOnPeriod) {
                return $subscription;
            }

            // Do not stack the same billing period twice.
            if (
                $subscription->current_period_start
                && $subscription->current_period_end
                && $subscription->current_period_start->equalTo($periodStart)
                && $subscription->current_period_end->equalTo($periodEnd)
                && $subscription->status === SubscriptionStatus::Active
            ) {
                return $subscription;
            }

            $graceDays = (int) SystemSetting::getValue(
                'subscription_grace_period_days',
                config('reapersventory.grace_period_days', 7)
            );

            $old = $subscription->only([
                'status', 'starts_at', 'ends_at', 'grace_ends_at',
                'current_period_start', 'current_period_end', 'billing_interval',
            ]);

            $subscription->fill([
                'status' => SubscriptionStatus::Active,
                'billing_interval' => $interval,
                'starts_at' => $subscription->starts_at ?? $periodStart,
                'ends_at' => $periodEnd,
                'grace_ends_at' => $periodEnd->copy()->addDays($graceDays),
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'cancelled_at' => null,
                'trial_ends_at' => $subscription->trial_ends_at,
            ])->save();

            $this->auditLogger->log(
                action: 'subscription.activated_or_renewed',
                auditable: $subscription,
                oldValues: $old,
                newValues: $subscription->only(array_keys($old)),
                user: $actor,
            );

            return $subscription->refresh();
        });
    }

    public function cancel(Subscription $subscription, User $actor): Subscription
    {
        return DB::transaction(function () use ($subscription, $actor) {
            $subscription = Subscription::withoutGlobalScopes()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($subscription->cancelled_at !== null) {
                return $subscription;
            }

            $old = $subscription->only(['status', 'cancelled_at', 'auto_renew']);

            // Keep access until current period ends; scheduler will expire afterward.
            $subscription->fill([
                'cancelled_at' => now(),
                'auto_renew' => false,
            ])->save();

            $this->auditLogger->log(
                action: 'subscription.cancelled',
                auditable: $subscription,
                oldValues: $old,
                newValues: $subscription->only(array_keys($old)),
                user: $actor,
            );

            return $subscription;
        });
    }

    public function markExpiredSubscriptions(): int
    {
        $count = 0;
        $now = now();

        Subscription::withoutGlobalScopes()
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
                SubscriptionStatus::Grace->value,
            ])
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($now, &$count) {
                foreach ($subscriptions as $subscription) {
                    if ($subscription->allowsAccess($now)) {
                        if (
                            $subscription->status === SubscriptionStatus::Active
                            && $subscription->ends_at
                            && $subscription->ends_at->lessThan($now)
                            && $subscription->grace_ends_at
                            && $subscription->grace_ends_at->greaterThanOrEqualTo($now)
                        ) {
                            $subscription->update(['status' => SubscriptionStatus::Grace]);
                            $this->notifier->subscriptionEvent($subscription->fresh(), 'grace');
                            $count++;
                        }

                        continue;
                    }

                    $subscription->update(['status' => SubscriptionStatus::Expired]);
                    $this->notifier->subscriptionEvent($subscription->fresh(), 'expired');
                    $count++;
                }
            });

        return $count;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function calculatePeriod(Carbon $from, BillingInterval $interval): array
    {
        $start = $from->copy()->startOfDay();
        $end = $interval === BillingInterval::Yearly
            ? $start->copy()->addYear()->subSecond()
            : $start->copy()->addMonth()->subSecond();

        return [$start, $end];
    }

    public function nextPeriodFor(Subscription $subscription): array
    {
        $from = $subscription->current_period_end
            ? $subscription->current_period_end->copy()->addSecond()
            : now();

        return $this->calculatePeriod($from, $subscription->billing_interval);
    }

    public function currentAccessibleSubscription(Business $business): ?Subscription
    {
        $subscription = Subscription::withoutGlobalScopes()
            ->with('plan.features')
            ->where('business_id', $business->id)
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
                SubscriptionStatus::Grace->value,
            ])
            ->latest('id')
            ->first();

        if (! $subscription || ! $subscription->allowsAccess()) {
            return null;
        }

        return $subscription;
    }

    /**
     * Custom branding is only available after a paid (Active) subscription.
     */
    public function isPaid(Business $business): bool
    {
        $subscription = $this->currentAccessibleSubscription($business);

        return $subscription !== null
            && $subscription->status === SubscriptionStatus::Active;
    }
}
