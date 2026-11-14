<?php

namespace App\Models;

use App\Enums\TaskAttemptStatus;
use Database\Factories\TaskAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'user_id',
    'earning_task_id',
    'status',
    'started_at',
    'expires_at',
    'completed_at',
    'watched_seconds',
    'reward_amount',
    'ip_address',
])]
class TaskAttempt extends Model
{
    /** @use HasFactory<TaskAttemptFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TaskAttemptStatus::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'watched_seconds' => 'integer',
            'reward_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(EarningTask::class, 'earning_task_id');
    }

    /**
     * @param  Builder<TaskAttempt>  $query
     */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', TaskAttemptStatus::Completed->value);
    }

    /**
     * @param  Builder<TaskAttempt>  $query
     */
    public function scopeInProgress(Builder $query): void
    {
        $query->where('status', TaskAttemptStatus::InProgress->value);
    }

    public function isCompleted(): bool
    {
        return $this->status === TaskAttemptStatus::Completed;
    }

    public function isReadyToClaim(): bool
    {
        return $this->expires_at->lessThanOrEqualTo(Carbon::now());
    }

    public function remainingSeconds(): int
    {
        return max(0, (int) ceil(Carbon::now()->diffInSeconds($this->expires_at, false)));
    }
}
