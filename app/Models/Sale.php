<?php

namespace App\Models;

use App\Concerns\BelongsToBusiness;
use App\Enums\SaleCustomerType;
use App\Enums\SaleStatus;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'business_id',
    'customer_id',
    'customer_type',
    'warehouse_id',
    'sale_number',
    'status',
    'sale_date',
    'subtotal',
    'tax',
    'discount',
    'total',
    'amount_paid',
    'amount_due',
    'notes',
    'created_by',
])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use BelongsToBusiness, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => SaleStatus::class,
            'customer_type' => SaleCustomerType::class,
            'sale_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_due' => 'decimal:2',
        ];
    }

    public function customerLabel(): string
    {
        $type = $this->customer_type ?? SaleCustomerType::WalkIn;

        return match ($type) {
            SaleCustomerType::Online => SaleCustomerType::Online->label(),
            SaleCustomerType::Customer => $this->customer?->name ?? 'Customer',
            SaleCustomerType::WalkIn => SaleCustomerType::WalkIn->label(),
        };
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
        return $this->hasMany(SaleItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ProductReturn::class);
    }
}
