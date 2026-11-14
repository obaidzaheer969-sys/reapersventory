<?php

namespace Database\Factories;

use App\Enums\TaskType;
use App\Models\EarningTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EarningTask>
 */
class EarningTaskFactory extends Factory
{
    protected $model = EarningTask::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'task_type' => fake()->randomElement(TaskType::cases()),
            'target_url' => fake()->url(),
            'duration_seconds' => 15,
            'reward' => fake()->randomFloat(2, 5, 100),
            'instructions' => fake()->sentence(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function youtubeSubscribe(int $seconds = 15, float $reward = 20): static
    {
        return $this->state(fn () => [
            'task_type' => TaskType::YoutubeSubscribe,
            'duration_seconds' => $seconds,
            'reward' => $reward,
        ]);
    }
}
