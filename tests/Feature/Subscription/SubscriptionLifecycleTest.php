<?php

namespace Tests\Feature\Subscription;

use App\Enums\BillingInterval;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Livewire\Business\ChoosePlan;
use App\Livewire\Business\SubmitPaymentProof;
use App\Livewire\Business\UpdatePlan;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\PaymentApprovalService;
use App\Services\SubscriptionPaymentService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_start_trial_subscription(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['trial_days' => 7]);

        Livewire::actingAs($user)
            ->test(ChoosePlan::class)
            ->set('planId', $plan->id)
            ->set('billingInterval', 'monthly')
            ->call('start')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('subscriptions', [
            'business_id' => $user->business_id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial->value,
        ]);
    }

    public function test_payment_proof_upload_does_not_activate_subscription(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $plan = Plan::factory()->create(['trial_days' => 0]);
        $method = PaymentMethod::factory()->create();

        $subscription = app(SubscriptionService::class)->startTrialOrPending(
            $user->business,
            $plan,
            BillingInterval::Monthly,
            $user,
        );

        $this->assertSame(SubscriptionStatus::PastDue, $subscription->status);

        Livewire::actingAs($user)
            ->test(SubmitPaymentProof::class, ['subscriptionId' => $subscription->id])
            ->set('paymentMethodId', $method->id)
            ->set('billingInterval', 'monthly')
            ->set('reference', 'TXN-1')
            ->set('proof', UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'))
            ->call('submit')
            ->assertHasNoErrors();

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::PastDue, $subscription->status);
        $this->assertDatabaseHas('subscription_payments', [
            'subscription_id' => $subscription->id,
            'status' => PaymentStatus::Pending->value,
        ]);
        $this->assertDatabaseCount('payment_proofs', 1);
    }

    public function test_super_admin_approval_is_transactional_and_activates_subscription(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $plan = Plan::factory()->create(['trial_days' => 0, 'price_monthly' => 2999]);
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
                'reference' => 'OK-1',
            ],
            UploadedFile::fake()->create('proof.jpg', 120, 'image/jpeg'),
        );

        $approved = app(PaymentApprovalService::class)->approve($payment, $admin, 'Looks good');

        $this->assertSame(PaymentStatus::Approved, $approved->status);
        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertTrue($subscription->allowsAccess());
    }

    public function test_approval_is_idempotent_and_rejects_duplicate_period(): void
    {
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

        $service = app(PaymentApprovalService::class);
        $first = $service->approve($payment, $admin);
        $second = $service->approve($payment->fresh(), $admin);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(PaymentStatus::Approved, $second->status);
        $this->assertSame(1, SubscriptionPayment::withoutGlobalScopes()
            ->where('subscription_id', $subscription->id)
            ->where('status', PaymentStatus::Approved)
            ->count());
    }

    public function test_renewal_does_not_duplicate_billing_period(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $plan = Plan::factory()->create(['trial_days' => 0]);
        $method = PaymentMethod::factory()->create();
        $subscriptions = app(SubscriptionService::class);
        $payments = app(SubscriptionPaymentService::class);
        $approver = app(PaymentApprovalService::class);

        $subscription = $subscriptions->startTrialOrPending(
            $owner->business,
            $plan,
            BillingInterval::Monthly,
            $owner,
        );

        $firstPayment = $payments->submitManualPayment(
            $subscription,
            $owner,
            ['payment_method_id' => $method->id, 'billing_interval' => 'monthly'],
            UploadedFile::fake()->create('a.jpg', 120, 'image/jpeg'),
        );
        $approver->approve($firstPayment, $admin);

        $subscription->refresh();
        $periodStart = $subscription->current_period_start->copy();
        $periodEnd = $subscription->current_period_end->copy();

        // Idempotent renew for the same period keeps dates stable.
        $subscriptions->activateOrRenewForPeriod(
            $subscription,
            $periodStart,
            $periodEnd,
            BillingInterval::Monthly,
            $admin,
        );

        $subscription->refresh();
        $this->assertTrue($subscription->current_period_start->equalTo($periodStart));
        $this->assertTrue($subscription->current_period_end->equalTo($periodEnd));

        $duplicate = SubscriptionPayment::withoutGlobalScopes()->create([
            'business_id' => $subscription->business_id,
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'payment_method_id' => $method->id,
            'amount' => $plan->price_monthly,
            'currency' => $plan->currency,
            'billing_interval' => BillingInterval::Monthly,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => PaymentStatus::Pending,
            'submitted_by' => $owner->id,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $approver->approve($duplicate, $admin);
    }

    public function test_business_owner_cannot_approve_payment(): void
    {
        $owner = User::factory()->create();
        $payment = SubscriptionPayment::factory()->create([
            'business_id' => $owner->business_id,
            'subscription_id' => Subscription::factory()->create([
                'business_id' => $owner->business_id,
            ])->id,
            'submitted_by' => $owner->id,
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(PaymentApprovalService::class)->approve($payment, $owner);
    }

    public function test_subscription_page_is_tenant_isolated(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $plan = Plan::factory()->create();

        Subscription::factory()->create([
            'business_id' => $ownerB->business_id,
            'plan_id' => $plan->id,
        ]);

        $this->actingAs($ownerA)
            ->get(route('business.subscription.index'))
            ->assertOk()
            ->assertDontSee($ownerB->business->name);
    }

    public function test_owner_can_update_package_to_another_plan(): void
    {
        $owner = User::factory()->create();
        $basic = Plan::factory()->create(['name' => 'Basic Pack', 'price_monthly' => 1000, 'trial_days' => 7]);
        $pro = Plan::factory()->create(['name' => 'Pro Pack', 'price_monthly' => 3000, 'trial_days' => 7]);

        $subscription = app(SubscriptionService::class)->startTrialOrPending(
            $owner->business,
            $basic,
            BillingInterval::Monthly,
            $owner,
        );

        $this->actingAs($owner)
            ->get(route('business.subscription.index'))
            ->assertOk()
            ->assertSee('Update package')
            ->assertSee('Pro Pack');

        Livewire::actingAs($owner)
            ->test(UpdatePlan::class, ['subscriptionId' => $subscription->id])
            ->set('planId', $pro->id)
            ->call('update')
            ->assertHasNoErrors();

        $subscription->refresh();
        // Current package stays until admin approval.
        $this->assertSame($basic->id, $subscription->plan_id);
        $this->assertSame($pro->id, $subscription->pending_plan_id);
        $this->assertSame(SubscriptionStatus::Trial, $subscription->status);
    }

    public function test_pending_package_applies_only_after_admin_approves_payment(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $basic = Plan::factory()->create(['name' => 'Basic Pack', 'price_monthly' => 1000, 'trial_days' => 0]);
        $pro = Plan::factory()->create(['name' => 'Pro Pack', 'price_monthly' => 3000, 'trial_days' => 0]);
        $method = PaymentMethod::factory()->create();

        $subscription = app(SubscriptionService::class)->startTrialOrPending(
            $owner->business,
            $basic,
            BillingInterval::Monthly,
            $owner,
        );

        app(SubscriptionService::class)->changePlan($subscription, $pro, $owner);
        $subscription->refresh();
        $this->assertSame($basic->id, $subscription->plan_id);
        $this->assertSame($pro->id, $subscription->pending_plan_id);

        $payment = app(SubscriptionPaymentService::class)->submitManualPayment(
            $subscription->fresh(),
            $owner,
            [
                'payment_method_id' => $method->id,
                'billing_interval' => 'monthly',
                'reference' => 'UPGRADE-1',
            ],
            UploadedFile::fake()->create('proof.jpg', 120, 'image/jpeg'),
        );

        $this->assertSame($pro->id, $payment->plan_id);
        $this->assertSame('3000.00', (string) $payment->amount);

        app(PaymentApprovalService::class)->approve($payment, $admin, 'Upgrade ok');

        $subscription->refresh();
        $this->assertSame($pro->id, $subscription->plan_id);
        $this->assertNull($subscription->pending_plan_id);
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
    }

    public function test_owner_cannot_update_to_same_package(): void
    {
        $owner = User::factory()->create();
        $plan = Plan::factory()->create(['trial_days' => 7]);

        $subscription = app(SubscriptionService::class)->startTrialOrPending(
            $owner->business,
            $plan,
            BillingInterval::Monthly,
            $owner,
        );

        Livewire::actingAs($owner)
            ->test(UpdatePlan::class, ['subscriptionId' => $subscription->id])
            ->set('planId', $plan->id)
            ->call('update')
            ->assertHasErrors(['planId']);
    }
}
