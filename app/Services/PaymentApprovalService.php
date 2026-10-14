<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentApprovalService
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly AuditLogger $auditLogger,
        private readonly BusinessNotifier $notifier,
    ) {}

    public function approve(SubscriptionPayment $payment, User $admin, ?string $reviewNotes = null): SubscriptionPayment
    {
        if (! $admin->isSuperAdmin()) {
            abort(403, 'Only Super Admin can approve payments.');
        }

        $wasPending = false;

        $result = DB::transaction(function () use ($payment, $admin, $reviewNotes, &$wasPending) {
            $payment = SubscriptionPayment::withoutGlobalScopes()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotent: already approved for this record.
            if ($payment->status === PaymentStatus::Approved) {
                return $payment;
            }

            if ($payment->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending payments can be approved.',
                ]);
            }

            $duplicatePeriod = SubscriptionPayment::withoutGlobalScopes()
                ->where('subscription_id', $payment->subscription_id)
                ->where('period_start', $payment->period_start)
                ->where('period_end', $payment->period_end)
                ->where('status', PaymentStatus::Approved)
                ->where('id', '!=', $payment->id)
                ->exists();

            if ($duplicatePeriod) {
                throw ValidationException::withMessages([
                    'status' => 'This billing period was already approved. Renewal must not duplicate a period.',
                ]);
            }

            $wasPending = true;

            $payment->fill([
                'status' => PaymentStatus::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
            ])->save();

            $this->subscriptions->activateOrRenewForPeriod(
                subscription: $payment->subscription()->withoutGlobalScopes()->firstOrFail(),
                periodStart: $payment->period_start,
                periodEnd: $payment->period_end,
                interval: $payment->billing_interval,
                actor: $admin,
            );

            $this->auditLogger->log(
                action: 'subscription_payment.approved',
                auditable: $payment,
                newValues: [
                    'status' => PaymentStatus::Approved->value,
                    'reviewed_by' => $admin->id,
                    'period_start' => $payment->period_start?->toDateTimeString(),
                    'period_end' => $payment->period_end?->toDateTimeString(),
                ],
                user: $admin,
            );

            return $payment->refresh();
        });

        if ($wasPending) {
            $this->notifier->paymentApproved($result);
        }

        return $result;
    }

    public function reject(SubscriptionPayment $payment, User $admin, ?string $reviewNotes = null): SubscriptionPayment
    {
        if (! $admin->isSuperAdmin()) {
            abort(403, 'Only Super Admin can reject payments.');
        }

        $wasPending = false;

        $result = DB::transaction(function () use ($payment, $admin, $reviewNotes, &$wasPending) {
            $payment = SubscriptionPayment::withoutGlobalScopes()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === PaymentStatus::Rejected) {
                return $payment;
            }

            if ($payment->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending payments can be rejected.',
                ]);
            }

            $wasPending = true;

            $payment->fill([
                'status' => PaymentStatus::Rejected,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
            ])->save();

            $this->auditLogger->log(
                action: 'subscription_payment.rejected',
                auditable: $payment,
                newValues: [
                    'status' => PaymentStatus::Rejected->value,
                    'reviewed_by' => $admin->id,
                    'review_notes' => $reviewNotes,
                ],
                user: $admin,
            );

            return $payment->refresh();
        });

        if ($wasPending) {
            $this->notifier->paymentRejected($result);
        }

        return $result;
    }
}
