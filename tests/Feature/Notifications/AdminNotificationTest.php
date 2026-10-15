<?php

namespace Tests\Feature\Notifications;

use App\Enums\BillingInterval;
use App\Livewire\Business\UpdatePlan;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\NewBusinessRegisteredAdminNotification;
use App\Notifications\PackageChangeRequestedAdminNotification;
use App\Notifications\PaymentSubmittedAdminNotification;
use App\Services\SubscriptionPaymentService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_notifies_super_admins(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();

        $this->post(route('register'), [
            'name' => 'Sara Ali',
            'email' => 'sara@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Sara Shop',
        ])->assertRedirect(route('business.dashboard'));

        Notification::assertSentTo($admin, NewBusinessRegisteredAdminNotification::class);
    }

    public function test_payment_submission_notifies_super_admins_in_filament_format(): void
    {
        Notification::fake();
        Storage::fake('local');

        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $plan = Plan::factory()->create(['trial_days' => 0, 'price_monthly' => 2999]);
        $method = PaymentMethod::factory()->create();

        $subscription = app(SubscriptionService::class)->startTrialOrPending(
            $owner->business,
            $plan,
            BillingInterval::Monthly,
            $owner,
        );

        app(SubscriptionPaymentService::class)->submitManualPayment(
            $subscription,
            $owner,
            [
                'payment_method_id' => $method->id,
                'billing_interval' => 'monthly',
                'reference' => 'ADMIN-NOTE-1',
            ],
            UploadedFile::fake()->create('proof.jpg', 120, 'image/jpeg'),
        );

        Notification::assertSentTo($admin, PaymentSubmittedAdminNotification::class);

        $notification = new PaymentSubmittedAdminNotification(
            $subscription->payments()->latest('id')->first()
        );
        $data = $notification->toDatabase($admin);

        $this->assertSame('filament', $data['format'] ?? null);
        $this->assertNotEmpty($data['title'] ?? null);
    }

    public function test_package_change_notifies_super_admins(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $basic = Plan::factory()->create(['name' => 'Basic', 'trial_days' => 7]);
        $pro = Plan::factory()->create(['name' => 'Pro', 'trial_days' => 7]);

        $subscription = app(SubscriptionService::class)->startTrialOrPending(
            $owner->business,
            $basic,
            BillingInterval::Monthly,
            $owner,
        );

        Livewire::actingAs($owner)
            ->test(UpdatePlan::class, ['subscriptionId' => $subscription->id])
            ->set('planId', $pro->id)
            ->call('update')
            ->assertHasNoErrors();

        Notification::assertSentTo($admin, PackageChangeRequestedAdminNotification::class);
    }
}
