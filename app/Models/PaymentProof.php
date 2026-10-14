<?php

namespace App\Models;

use App\Models\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'business_id',
    'subscription_payment_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
    'uploaded_by',
])]
class PaymentProof extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new BusinessScope);

        static::deleting(function (PaymentProof $proof): void {
            Storage::disk($proof->disk)->delete($proof->path);
        });
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPayment::class, 'subscription_payment_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
