<?php

namespace App\Models;

use App\Enums\BillingInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Scopes\BusinessScope;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'business_id',
    'plan_id',
    'pending_plan_id',
    'status',
    'billing_interval',
    'trial_ends_at',
    'starts_at',
    'ends_at',
    'grace_ends_at',
    'current_period_start',
    'current_period_end',
    'cancelled_at',
    'auto_renew',
])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new BusinessScope);
    }

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'billing_interval' => BillingInterval::class,
            'trial_ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancelled_at' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function pendingPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'pending_plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * Plan used for payment amount when a package change is waiting for admin approval.
     */
    public function billablePlan(): Plan
    {
        $plan = $this->pendingPlan ?? $this->plan;

        if (! $plan && $this->plan_id) {
            $plan = Plan::query()->find($this->plan_id);
        }

        if (! $plan) {
            throw new \RuntimeException('Subscription has no billable plan.');
        }

        return $plan;
    }

    public function allowsAccess(?Carbon $at = null): bool
    {
        $at ??= now();

        return match ($this->status) {
            SubscriptionStatus::Trial => $this->trial_ends_at === null || $this->trial_ends_at->greaterThan($at),
            SubscriptionStatus::Active => ($this->ends_at === null || $this->ends_at->greaterThanOrEqualTo($at))
                || ($this->grace_ends_at !== null && $this->grace_ends_at->greaterThanOrEqualTo($at)),
            SubscriptionStatus::Grace => $this->grace_ends_at !== null && $this->grace_ends_at->greaterThanOrEqualTo($at),
            default => false,
        };
    }

    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatus::Cancelled || $this->cancelled_at !== null;
    }
}
