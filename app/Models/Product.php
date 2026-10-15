<?php

namespace App\Models;

use App\Concerns\BelongsToBusiness;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'business_id',
    'category_id',
    'name',
    'sku',
    'barcode',
    'description',
    'unit',
    'cost_price',
    'selling_price',
    'reorder_level',
    'track_inventory',
    'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToBusiness, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if ($product->barcode === '') {
                $product->barcode = null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'reorder_level' => 'decimal:3',
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function totalQuantity(): string
    {
        return number_format(
            (float) $this->inventoryBalances()->withoutGlobalScopes()->sum('quantity'),
            3,
            '.',
            ''
        );
    }

    /**
     * Reorder level used for alerts. 0 / empty means "not set" → default 5.
     */
    public function effectiveReorderLevel(): string
    {
        $level = (float) $this->reorder_level;

        return number_format($level > 0 ? $level : 5, 3, '.', '');
    }

    public function isLowStock(?float $quantity = null): bool
    {
        $total = number_format(
            $quantity ?? (float) $this->totalQuantity(),
            3,
            '.',
            ''
        );

        return bccomp($total, $this->effectiveReorderLevel(), 3) <= 0;
    }
}
