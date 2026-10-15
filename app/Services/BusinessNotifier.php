<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Notifications\LowStockAlertNotification;
use App\Notifications\PaymentSubmittedAdminNotification;
use App\Notifications\PurchaseReceivedNotification;
use App\Notifications\SaleCompletedNotification;
use App\Notifications\SubscriptionPaymentApprovedNotification;
use App\Notifications\SubscriptionPaymentRejectedNotification;
use App\Notifications\SubscriptionStatusNotification;
use App\Notifications\WelcomeBusinessNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class BusinessNotifier
{
    private function safeNotify(User $user, object $notification): void
    {
        try {
            $user->notify($notification);
        } catch (Throwable) {
            // Do not break business actions if notifications table/mailer fails.
        }
    }

    public function ownerForBusiness(int $businessId): ?User
    {
        return User::query()
            ->where('business_id', $businessId)
            ->where('role', UserRole::BusinessOwner)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function superAdmins(): Collection
    {
        return User::query()
            ->where('role', UserRole::SuperAdmin)
            ->where('is_active', true)
            ->get();
    }

    public function welcome(User $owner, Business $business): void
    {
        $this->safeNotify($owner, new WelcomeBusinessNotification($business->name));
    }

    public function paymentApproved(SubscriptionPayment $payment): void
    {
        $owner = $this->ownerForBusiness((int) $payment->business_id);
        if ($owner) {
            $this->safeNotify($owner, new SubscriptionPaymentApprovedNotification($payment));
        }
    }

    public function paymentRejected(SubscriptionPayment $payment): void
    {
        $owner = $this->ownerForBusiness((int) $payment->business_id);
        if ($owner) {
            $this->safeNotify($owner, new SubscriptionPaymentRejectedNotification($payment));
        }
    }

    public function paymentSubmitted(SubscriptionPayment $payment): void
    {
        $admins = $this->superAdmins();
        if ($admins->isEmpty()) {
            return;
        }

        try {
            Notification::send($admins, new PaymentSubmittedAdminNotification($payment));
        } catch (Throwable) {
            // ignore
        }
    }

    public function subscriptionEvent(Subscription $subscription, string $event): void
    {
        $owner = $this->ownerForBusiness((int) $subscription->business_id);
        if ($owner) {
            $this->safeNotify($owner, new SubscriptionStatusNotification($subscription, $event));
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
            $this->safeNotify($owner, new LowStockAlertNotification($products));
        }
    }

    public function saleCompleted(Sale $sale): void
    {
        $owner = $this->ownerForBusiness((int) $sale->business_id);
        if ($owner) {
            $this->safeNotify($owner, new SaleCompletedNotification($sale));
        }
    }

    public function purchaseReceived(Purchase $purchase): void
    {
        $owner = $this->ownerForBusiness((int) $purchase->business_id);
        if ($owner) {
            $this->safeNotify($owner, new PurchaseReceivedNotification($purchase));
        }
    }
}
