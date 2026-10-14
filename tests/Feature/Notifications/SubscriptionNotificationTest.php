<?php

namespace Tests\Feature\Notifications;

use App\Enums\BillingInterval;
use App\Enums\SubscriptionStatus;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionPaymentApprovedNotification;
use App\Notifications\SubscriptionPaymentRejectedNotification;
use App\Notifications\SubscriptionStatusNotification;
use App\Services\PaymentApprovalService;
use App\Services\SubscriptionPaymentService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubscriptionNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_notifies_business_owner(): void
    {
        Notification::fake();
        Storage::fake('local');

        $owner = User::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $plan = Plan::factory()->create(['trial_days' => 0]);
        $method = PaymentMethod::factory()->create();

        $subscription = app(SubscriptionService::class)->startTrialOrPending(
            $owner->business,
            $plan,
            BillingInterval::Monthly,
            $owner,
        );

        $payment = app(SubscriptionPaymentService::class)->submitManualPayment(
            $subscription,
            $owner,
            [
                'payment_method_id' => $method->id,
                'billing_interval' => 'monthly',
            ],
            UploadedFile::fake()->create('proof.jpg', 120, 'image/jpeg'),
        );

        app(PaymentApprovalService::class)->approve($payment, $admin);

        Notification::assertSentTo($owner, SubscriptionPaymentApprovedNotification::class);
    }

    public function test_rejection_notifies_business_owner(): void
    {
        Notification::fake();
        Storage::fake('local');

        $owner = User::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $plan = Plan::factory()->create(['trial_days' => 0]);
        $method = PaymentMethod::factory()->create();

        $subscription = app(SubscriptionService::class)->startTrialOrPending(
            $owner->business,
            $plan,
            BillingInterval::Monthly,
            $owner,
        );

        $payment = app(SubscriptionPaymentService::class)->submitManualPayment(
            $subscription,
            $owner,
            [
                'payment_method_id' => $method->id,
                'billing_interval' => 'monthly',
            ],
            UploadedFile::fake()->create('proof.jpg', 120, 'image/jpeg'),
        );

        app(PaymentApprovalService::class)->reject($payment, $admin, 'Unclear receipt');

        Notification::assertSentTo($owner, SubscriptionPaymentRejectedNotification::class);
    }

    public function test_expire_command_notifies_on_grace_and_expired(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $plan = Plan::factory()->create();

        Subscription::factory()->create([
            'business_id' => $owner->business_id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'ends_at' => now()->subDay(),
            'grace_ends_at' => now()->addDays(3),
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->subDay(),
        ]);

        Artisan::call('subscriptions:expire');

        Notification::assertSentTo(
            $owner,
            SubscriptionStatusNotification::class,
            fn (SubscriptionStatusNotification $n) => $n->event === 'grace',
        );

        Notification::fake();

        $expiredOwner = User::factory()->create();
        Subscription::factory()->create([
            'business_id' => $expiredOwner->business_id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Grace,
            'ends_at' => now()->subDays(10),
            'grace_ends_at' => now()->subDay(),
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->subDays(10),
        ]);

        Artisan::call('subscriptions:expire');

        Notification::assertSentTo(
            $expiredOwner,
            SubscriptionStatusNotification::class,
            fn (SubscriptionStatusNotification $n) => $n->event === 'expired',
        );
    }

    public function test_expiring_soon_command_notifies_once_per_period_end(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $plan = Plan::factory()->create();

        Subscription::factory()->create([
            'business_id' => $owner->business_id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'ends_at' => now()->addDays(2),
            'grace_ends_at' => now()->addDays(9),
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->addDays(2),
        ]);

        Artisan::call('subscriptions:notify-expiring');
        Artisan::call('subscriptions:notify-expiring');

        Notification::assertSentToTimes($owner, SubscriptionStatusNotification::class, 1);
        Notification::assertSentTo(
            $owner,
            SubscriptionStatusNotification::class,
            fn (SubscriptionStatusNotification $n) => $n->event === 'expiring_soon',
        );
    }
}
