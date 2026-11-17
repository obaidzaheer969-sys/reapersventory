<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\TaskType;
use Database\Factories\EarningTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'title',
    'task_type',
    'target_url',
    'duration_seconds',
    'reward',
    'instructions',
    'is_active',
    'sort_order',
    'campaign_status',
    'target_quantity',
    'completed_quantity',
    'total_cost',
    'admin_notes',
    'approved_at',
])]
class EarningTask extends Model
{
    /** @use HasFactory<EarningTaskFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'task_type' => TaskType::class,
            'duration_seconds' => 'integer',
            'reward' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'campaign_status' => CampaignStatus::class,
            'target_quantity' => 'integer',
            'completed_quantity' => 'integer',
            'total_cost' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TaskAttempt::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param  Builder<EarningTask>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<EarningTask>  $query
     */
    public function scopeSystem(Builder $query): void
    {
        $query->whereNull('campaign_status');
    }

    /**
     * @param  Builder<EarningTask>  $query
     */
    public function scopeCampaigns(Builder $query): void
    {
        $query->whereNotNull('campaign_status');
    }

    /**
     * @param  Builder<EarningTask>  $query
     */
    public function scopeLiveCampaigns(Builder $query): void
    {
        $query->where('campaign_status', CampaignStatus::Active->value)
            ->where('is_active', true)
            ->whereColumn('completed_quantity', '<', 'target_quantity');
    }

    /**
     * @param  Builder<EarningTask>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    public function isCampaign(): bool
    {
        return $this->campaign_status !== null;
    }

    /**
     * An unparseable or unsupported URL falls back to the real-page flow, so
     * the player requirement must be based on the actual URL, not just type.
     */
    public function hasEmbeddedPlayer(): bool
    {
        return $this->task_type->embedUrl($this->target_url) !== null;
    }

    public function isLiveCampaign(): bool
    {
        return $this->isCampaign()
            && $this->campaign_status === CampaignStatus::Active
            && $this->is_active
            && ! $this->isSoldOut();
    }

    public function isSoldOut(): bool
    {
        return $this->target_quantity !== null
            && $this->completed_quantity >= $this->target_quantity;
    }

    public function remainingQuantity(): int
    {
        if ($this->target_quantity === null) {
            return 0;
        }

        return max(0, $this->target_quantity - $this->completed_quantity);
    }

    public function progressPercent(): int
    {
        if (! $this->target_quantity) {
            return 0;
        }

        return (int) min(100, round(($this->completed_quantity / $this->target_quantity) * 100));
    }

    public function budgetTotal(): float
    {
        return round((float) $this->reward * (int) $this->target_quantity, 2);
    }

    public function budgetSpent(): float
    {
        return round((float) $this->reward * (int) $this->completed_quantity, 2);
    }

    public function platformFee(): float
    {
        return max(0, round((float) $this->total_cost - $this->budgetTotal(), 2));
    }

    public function budgetRemaining(): float
    {
        return max(0, round($this->budgetTotal() - $this->budgetSpent(), 2));
    }

    public function platform(): string
    {
        return $this->task_type->platform();
    }

    public function actionLabel(): string
    {
        return $this->task_type->actionText();
    }

    public function durationLabel(): string
    {
        return $this->duration_seconds.' sec';
    }
}
