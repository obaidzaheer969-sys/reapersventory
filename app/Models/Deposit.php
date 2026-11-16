<?php

namespace App\Models;

use App\Enums\DepositStatus;
use Database\Factories\DepositFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'provider',
    'order_id',
    'amount',
    'fee',
    'credit_amount',
    'currency',
    'status',
    'cm_tid',
    'client_email',
    'admin_notes',
    'payload',
    'paid_at',
])]
class Deposit extends Model
{
    /** @use HasFactory<DepositFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => DepositStatus::class,
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'credit_amount' => 'decimal:2',
            'payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Deposit>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', DepositStatus::Pending->value);
    }

    /**
     * @param  Builder<Deposit>  $query
     */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', DepositStatus::Completed->value);
    }

    public function isPending(): bool
    {
        return $this->status === DepositStatus::Pending;
    }

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }
}
