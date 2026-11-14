<?php

namespace Database\Factories;

use App\Enums\PayoutProvider;
use App\Models\PayoutAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayoutAccount>
 */
class PayoutAccountFactory extends Factory
{
    protected $model = PayoutAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->earner(),
            'provider' => fake()->randomElement(PayoutProvider::cases()),
            'account_name' => fake()->name(),
            'account_number' => '03'.fake()->numerify('#########'),
            'is_default' => true,
        ];
    }
}
