<?php

namespace App\Services;

use App\Enums\BillingInterval;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\PaymentMethod;
use App\Models\PaymentProof;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubscriptionPaymentService
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly AuditLogger $auditLogger,
        private readonly BusinessNotifier $notifier,
    ) {}

    /**
     * Submit a manual payment with proof. Does NOT activate the subscription.
     *
     * @param  array{payment_method_id: int, billing_interval: string, reference?: string|null, notes?: string|null}  $data
     */
    public function submitManualPayment(
        Subscription $subscription,
        User $actor,
        array $data,
        UploadedFile $proof,
    ): SubscriptionPayment {
        if (! $actor->isBusinessOwner() || ! $actor->belongsToBusiness($subscription->business_id)) {
            abort(403, 'Unauthorized payment submission.');
        }

        $method = PaymentMethod::query()->active()->findOrFail($data['payment_method_id']);
        $interval = BillingInterval::from($data['billing_interval']);
        $plan = Plan::query()->findOrFail($subscription->plan_id);

        return DB::transaction(function () use ($subscription, $actor, $data, $proof, $method, $interval, $plan) {
            $subscription = Subscription::withoutGlobalScopes()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($subscription->status, [SubscriptionStatus::Cancelled, SubscriptionStatus::Expired], true)
                && ! $subscription->allowsAccess()) {
                // Allow renewal submission for expired/cancelled by recreating period intent.
            }

            [$periodStart, $periodEnd] = $this->resolveBillingPeriod($subscription, $interval);

            $duplicateApproved = SubscriptionPayment::withoutGlobalScopes()
                ->where('subscription_id', $subscription->id)
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->where('status', PaymentStatus::Approved)
                ->exists();

            if ($duplicateApproved) {
                throw ValidationException::withMessages([
                    'billing_interval' => 'This billing period is already paid and approved.',
                ]);
            }

            $pendingExists = SubscriptionPayment::withoutGlobalScopes()
                ->where('subscription_id', $subscription->id)
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->where('status', PaymentStatus::Pending)
                ->exists();

            if ($pendingExists) {
                throw ValidationException::withMessages([
                    'payment_method_id' => 'A payment for this billing period is already pending review.',
                ]);
            }

            $payment = SubscriptionPayment::withoutGlobalScopes()->create([
                'business_id' => $subscription->business_id,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'payment_method_id' => $method->id,
                'amount' => $plan->priceFor($interval),
                'currency' => $plan->currency,
                'billing_interval' => $interval,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => PaymentStatus::Pending,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'submitted_by' => $actor->id,
            ]);

            $this->storeProof($payment, $actor, $proof);

            // Explicit business rule: upload alone must not activate.
            if ($subscription->status === SubscriptionStatus::Trial && $subscription->allowsAccess()) {
                // keep trial
            } elseif ($subscription->status === SubscriptionStatus::Active && $subscription->allowsAccess()) {
                // keep active until period ends
            } else {
                $subscription->update(['status' => SubscriptionStatus::PastDue]);
            }

            $this->auditLogger->log(
                action: 'subscription_payment.submitted',
                auditable: $payment,
                newValues: [
                    'amount' => $payment->amount,
                    'period_start' => $payment->period_start?->toDateTimeString(),
                    'period_end' => $payment->period_end?->toDateTimeString(),
                    'status' => $payment->status->value,
                ],
                user: $actor,
            );

            $payment = $payment->load('proofs', 'paymentMethod');
            $this->notifier->paymentSubmitted($payment);

            return $payment;
        });
    }

    public function storeProof(SubscriptionPayment $payment, User $actor, UploadedFile $file): PaymentProof
    {
        $disk = config('reapersventory.payment_proof_disk', 'local');
        $path = $file->store(
            "payment-proofs/{$payment->business_id}/{$payment->id}",
            $disk
        );

        return PaymentProof::withoutGlobalScopes()->create([
            'business_id' => $payment->business_id,
            'subscription_payment_id' => $payment->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $actor->id,
        ]);
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    private function resolveBillingPeriod(Subscription $subscription, BillingInterval $interval): array
    {
        if (
            $subscription->status === SubscriptionStatus::Active
            && $subscription->current_period_end
            && $subscription->current_period_end->greaterThan(now())
        ) {
            return $this->subscriptions->nextPeriodFor($subscription);
        }

        if (
            in_array($subscription->status, [SubscriptionStatus::Trial, SubscriptionStatus::PastDue], true)
            && $subscription->current_period_start
            && $subscription->current_period_end
            && ! SubscriptionPayment::withoutGlobalScopes()
                ->where('subscription_id', $subscription->id)
                ->where('period_start', $subscription->current_period_start)
                ->where('period_end', $subscription->current_period_end)
                ->where('status', PaymentStatus::Approved)
                ->exists()
        ) {
            return [
                $subscription->current_period_start->copy(),
                $subscription->current_period_end->copy(),
            ];
        }

        return $this->subscriptions->calculatePeriod(now(), $interval);
    }
}
