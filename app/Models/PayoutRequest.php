<?php

namespace App\Models;

use App\Enums\PayoutProvider;
use App\Enums\PayoutStatus;
use Database\Factories\PayoutRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'payout_account_id',
    'provider',
    'account_name',
    'account_number',
    'amount',
    'gross_amount',
    'fee',
    'status',
    'admin_notes',
    'processed_at',
    'processed_by',
])]
class PayoutRequest extends Model
{
    /** @use HasFactory<PayoutRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'provider' => PayoutProvider::class,
            'status' => PayoutStatus::class,
            'amount' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(PayoutAccount::class, 'payout_account_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * @param  Builder<PayoutRequest>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', PayoutStatus::Pending->value);
    }

    public function isPending(): bool
    {
        return $this->status === PayoutStatus::Pending;
    }

    public function grossAmount(): float
    {
        return round((float) ($this->gross_amount ?? $this->amount), 2);
    }

    public function feeAmount(): float
    {
        return round((float) ($this->fee ?? 0), 2);
    }
}
