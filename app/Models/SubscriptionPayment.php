<?php

namespace App\Models;

use App\Enums\BillingInterval;
use App\Enums\PaymentStatus;
use App\Models\Scopes\BusinessScope;
use Database\Factories\SubscriptionPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_id',
    'subscription_id',
    'plan_id',
    'payment_method_id',
    'amount',
    'currency',
    'billing_interval',
    'period_start',
    'period_end',
    'status',
    'reference',
    'notes',
    'submitted_by',
    'reviewed_by',
    'reviewed_at',
    'review_notes',
])]
class SubscriptionPayment extends Model
{
    /** @use HasFactory<SubscriptionPaymentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new BusinessScope);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'billing_interval' => BillingInterval::class,
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'status' => PaymentStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }
}
