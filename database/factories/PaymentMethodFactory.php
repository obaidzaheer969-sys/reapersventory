<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        $name = fake()->unique()->company().' Transfer';

        return [
            'name' => $name,
            'code' => Str::slug($name).'-'.fake()->unique()->numerify('##'),
            'instructions' => 'Transfer the exact amount and upload your receipt.',
            'account_name' => fake()->name(),
            'account_number' => fake()->numerify('###########'),
            'additional_info' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
