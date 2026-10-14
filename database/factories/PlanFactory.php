<?php

namespace Database\Factories;

use App\Enums\PlanFeatureKey;
use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
            'price_monthly' => 2999,
            'price_yearly' => 29990,
            'currency' => 'PKR',
            'trial_days' => 7,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Plan $plan): void {
            foreach ([
                [PlanFeatureKey::ProductsLimit, 100],
                [PlanFeatureKey::InvoicesLimit, 200],
                [PlanFeatureKey::WarehousesLimit, 1],
                [PlanFeatureKey::UsersLimit, 1],
            ] as [$key, $limit]) {
                PlanFeature::query()->create([
                    'plan_id' => $plan->id,
                    'feature_key' => $key,
                    'limit_value' => $limit,
                    'is_enabled' => true,
                ]);
            }
        });
    }

    public function unlimited(): static
    {
        return $this->afterCreating(function (Plan $plan): void {
            $plan->features()->delete();
            foreach (PlanFeatureKey::cases() as $key) {
                PlanFeature::query()->create([
                    'plan_id' => $plan->id,
                    'feature_key' => $key,
                    'limit_value' => null,
                    'is_enabled' => true,
                ]);
            }
        });
    }
}
