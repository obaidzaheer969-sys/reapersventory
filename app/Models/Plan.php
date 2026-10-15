<?php

namespace App\Models;

use App\Enums\BillingInterval;
use App\Enums\PlanFeatureKey;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'description',
    'price_monthly',
    'price_yearly',
    'currency',
    'trial_days',
    'is_active',
    'is_featured',
    'sort_order',
])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Plan $plan): void {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function priceFor(BillingInterval $interval): string
    {
        return $interval === BillingInterval::Yearly
            ? (string) $this->price_yearly
            : (string) $this->price_monthly;
    }

    public function featureLimit(PlanFeatureKey|string $key): ?int
    {
        $key = $key instanceof PlanFeatureKey ? $key->value : $key;

        $feature = $this->relationLoaded('features')
            ? $this->features->first(function (PlanFeature $feature) use ($key) {
                return $feature->feature_key->value === $key;
            })
            : $this->features()->where('feature_key', $key)->first();

        if (! $feature || ! $feature->is_enabled) {
            return 0;
        }

        return $feature->limit_value;
    }

    public function hasUnlimited(PlanFeatureKey|string $key): bool
    {
        return $this->featureLimit($key) === null;
    }

    /**
     * Ensure every feature key exists so admin-created plans always enforce limits.
     * Missing keys default to 0 (blocked) until the admin sets a value.
     */
    public function ensureFeatureRows(): void
    {
        foreach (PlanFeatureKey::cases() as $key) {
            PlanFeature::query()->firstOrCreate(
                [
                    'plan_id' => $this->id,
                    'feature_key' => $key,
                ],
                [
                    'limit_value' => 0,
                    'is_enabled' => true,
                ],
            );
        }
    }
}
