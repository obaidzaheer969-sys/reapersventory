<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'category' => fake()->randomElement(['Rent', 'Utilities', 'Transport', 'Marketing']),
            'title' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ];
    }
}
