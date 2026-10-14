<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Notifications\LowStockAlertNotification;
use App\Notifications\SubscriptionPaymentApprovedNotification;
use App\Notifications\SubscriptionPaymentRejectedNotification;
use App\Notifications\SubscriptionStatusNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class BusinessNotifier
{
    public function ownerForBusiness(int $businessId): ?User
    {
        return User::query()
            ->where('business_id', $businessId)
            ->where('role', UserRole::BusinessOwner)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    public function paymentApproved(SubscriptionPayment $payment): void
    {
        $owner = $this->ownerForBusiness((int) $payment->business_id);
        if ($owner) {
            $owner->notify(new SubscriptionPaymentApprovedNotification($payment));
        }
    }

    public function paymentRejected(SubscriptionPayment $payment): void
    {
        $owner = $this->ownerForBusiness((int) $payment->business_id);
        if ($owner) {
            $owner->notify(new SubscriptionPaymentRejectedNotification($payment));
        }
    }

    public function subscriptionEvent(Subscription $subscription, string $event): void
    {
        $owner = $this->ownerForBusiness((int) $subscription->business_id);
        if ($owner) {
            $owner->notify(new SubscriptionStatusNotification($subscription, $event));
        }
    }

    /**
     * @param  Collection<int, \App\Models\Product>  $products
     */
    public function lowStock(Business $business, Collection $products): void
    {
        if ($products->isEmpty()) {
            return;
        }

        $owner = $this->ownerForBusiness((int) $business->id);
        if ($owner) {
            Notification::send($owner, new LowStockAlertNotification($products));
        }
    }
}
