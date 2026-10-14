<?php

namespace Database\Factories;

use App\Enums\PurchaseStatus;
use App\Models\Business;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'purchase_number' => 'PO-'.fake()->unique()->numerify('######'),
            'status' => PurchaseStatus::Ordered,
            'purchase_date' => now()->toDateString(),
            'subtotal' => 0,
            'tax' => 0,
            'discount' => 0,
            'total' => 0,
        ];
    }
}
