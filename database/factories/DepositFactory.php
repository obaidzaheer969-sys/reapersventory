<?php

namespace Database\Factories;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Deposit>
 */
class DepositFactory extends Factory
{
    protected $model = Deposit::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 100, 5000);
        $fee = round($amount * 0.10, 2);

        return [
            'user_id' => User::factory(),
            'provider' => 'cashmaal',
            'order_id' => 'DEP-'.Str::upper(Str::random(16)),
            'amount' => $amount,
            'fee' => $fee,
            'credit_amount' => round($amount - $fee, 2),
            'currency' => 'PKR',
            'status' => DepositStatus::Pending,
            'cm_tid' => null,
            'client_email' => fake()->safeEmail(),
            'admin_notes' => null,
            'payload' => null,
            'paid_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => DepositStatus::Completed,
            'cm_tid' => 'CM'.Str::upper(Str::random(10)),
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => DepositStatus::Failed,
        ]);
    }
}
