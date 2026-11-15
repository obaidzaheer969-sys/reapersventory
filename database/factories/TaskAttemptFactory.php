<?php

namespace Database\Factories;

use App\Enums\TaskAttemptStatus;
use App\Models\EarningTask;
use App\Models\TaskAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskAttempt>
 */
class TaskAttemptFactory extends Factory
{
    protected $model = TaskAttempt::class;

    public function definition(): array
    {
        $startedAt = now()->subMinutes(5);

        return [
            'user_id' => User::factory()->earner(),
            'earning_task_id' => EarningTask::factory(),
            'status' => TaskAttemptStatus::Completed,
            'started_at' => $startedAt,
            'expires_at' => $startedAt->copy()->addSeconds(15),
            'completed_at' => now(),
            'watched_seconds' => 15,
            'reward_amount' => 20,
            'ip_address' => '127.0.0.1',
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => TaskAttemptStatus::InProgress,
            'expires_at' => now()->addMinutes(1),
            'completed_at' => null,
            'watched_seconds' => 0,
            'reward_amount' => 0,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => TaskAttemptStatus::Failed,
            'failed_at' => now(),
            'completed_at' => null,
            'watched_seconds' => 0,
            'reward_amount' => 0,
        ]);
    }
}
