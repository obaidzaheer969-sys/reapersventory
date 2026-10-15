<?php

namespace Database\Factories;

use App\Enums\SaleCustomerType;
use App\Enums\SaleStatus;
use App\Models\Business;
use App\Models\Sale;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'customer_id' => null,
            'customer_type' => SaleCustomerType::WalkIn,
            'warehouse_id' => Warehouse::factory(),
            'sale_number' => 'SL-'.fake()->unique()->numerify('######'),
            'status' => SaleStatus::Completed,
            'sale_date' => now()->toDateString(),
            'subtotal' => 100,
            'tax' => 0,
            'discount' => 0,
            'total' => 100,
            'amount_paid' => 0,
            'amount_due' => 100,
        ];
    }
}
