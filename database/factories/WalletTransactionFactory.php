<?php

namespace Database\Factories;

use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->earner(),
            'type' => WalletTransactionType::TaskReward,
            'amount' => 20,
            'balance_after' => 20,
            'description' => 'Task reward',
        ];
    }
}
