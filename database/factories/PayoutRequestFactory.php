<?php

namespace Database\Factories;

use App\Enums\PayoutProvider;
use App\Enums\PayoutStatus;
use App\Models\PayoutRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayoutRequest>
 */
class PayoutRequestFactory extends Factory
{
    protected $model = PayoutRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->earner(),
            'payout_account_id' => null,
            'provider' => PayoutProvider::Easypaisa,
            'account_name' => fake()->name(),
            'account_number' => '03'.fake()->numerify('#########'),
            'amount' => 450,
            'gross_amount' => 500,
            'fee' => 50,
            'status' => PayoutStatus::Pending,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => PayoutStatus::Paid,
            'processed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => PayoutStatus::Rejected,
            'processed_at' => now(),
            'admin_notes' => 'Invalid account',
        ]);
    }
}
