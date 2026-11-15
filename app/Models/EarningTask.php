<?php

namespace App\Models;

use App\Enums\TaskType;
use Database\Factories\EarningTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'task_type',
    'target_url',
    'duration_seconds',
    'reward',
    'instructions',
    'is_active',
    'sort_order',
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
        ];
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TaskAttempt::class);
    }

    /**
     * @param  Builder<EarningTask>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
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
