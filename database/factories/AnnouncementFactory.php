<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'body' => fake()->paragraph(),
            'is_active' => true,
            'is_pinned' => false,
            'published_at' => now()->subMinute(),
            'expires_at' => null,
            'created_by' => User::factory()->superAdmin(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'published_at' => now()->subWeek(),
            'expires_at' => now()->subDay(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
