<?php

namespace App\Models;

use App\Concerns\BelongsToBusiness;
use App\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'business_id',
    'sale_id',
    'warehouse_id',
    'return_number',
    'status',
    'return_date',
    'subtotal',
    'refund_amount',
    'notes',
    'created_by',
])]
class ProductReturn extends Model
{
    use BelongsToBusiness, SoftDeletes;

    protected $table = 'returns';

    protected function casts(): array
    {
        return [
            'status' => ReturnStatus::class,
            'return_date' => 'date',
            'subtotal' => 'decimal:2',
            'refund_amount' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
