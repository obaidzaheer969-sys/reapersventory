<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Enums\TaskType;
use App\Models\EarningTask;
use App\Models\User;
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

    /**
     * A legacy campaign still waiting for admin approval.
     */
    public function campaign(int $quantity = 100): static
    {
        return $this->state(fn (): array => [
            'user_id' => User::factory(),
            'task_type' => TaskType::YoutubeSubscribe,
            'duration_seconds' => 15,
            'reward' => 2,
            'is_active' => false,
            'campaign_status' => CampaignStatus::Pending,
            'target_quantity' => $quantity,
            'completed_quantity' => 0,
            'total_cost' => round(2 * $quantity, 2),
        ]);
    }

    /**
     * An approved campaign that other users can complete right now.
     */
    public function liveCampaign(int $quantity = 100): static
    {
        return $this->campaign($quantity)->state(fn () => [
            'campaign_status' => CampaignStatus::Active,
            'is_active' => true,
            'approved_at' => now(),
        ]);
    }
}
