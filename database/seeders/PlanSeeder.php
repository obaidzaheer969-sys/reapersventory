<?php

namespace Database\Seeders;

use App\Enums\PlanFeatureKey;
use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'For small shops getting started with inventory.',
                'price_monthly' => 2999,
                'price_yearly' => 29990,
                'trial_days' => 7,
                'is_featured' => false,
                'sort_order' => 1,
                'features' => [
                    PlanFeatureKey::ProductsLimit->value => 100,
                    PlanFeatureKey::InvoicesLimit->value => 200,
                    PlanFeatureKey::WarehousesLimit->value => 1,
                    PlanFeatureKey::UsersLimit->value => 1,
                ],
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'Multi-warehouse inventory with higher limits.',
                'price_monthly' => 6999,
                'price_yearly' => 69990,
                'trial_days' => 7,
                'is_featured' => true,
                'sort_order' => 2,
                'features' => [
                    PlanFeatureKey::ProductsLimit->value => 1000,
                    PlanFeatureKey::InvoicesLimit->value => 2000,
                    PlanFeatureKey::WarehousesLimit->value => 5,
                    PlanFeatureKey::UsersLimit->value => 3,
                ],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Unlimited catalog with advanced operations readiness.',
                'price_monthly' => 12999,
                'price_yearly' => 129990,
                'trial_days' => 14,
                'is_featured' => false,
                'sort_order' => 3,
                'features' => [
                    PlanFeatureKey::ProductsLimit->value => null,
                    PlanFeatureKey::InvoicesLimit->value => null,
                    PlanFeatureKey::WarehousesLimit->value => null,
                    PlanFeatureKey::UsersLimit->value => 10,
                ],
            ],
        ];

        foreach ($plans as $data) {
            $features = $data['features'];
            unset($data['features']);

            $plan = Plan::query()->updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'currency' => 'PKR',
                    'is_active' => true,
                ])
            );

            foreach ($features as $key => $limit) {
                PlanFeature::query()->updateOrCreate(
                    ['plan_id' => $plan->id, 'feature_key' => $key],
                    ['limit_value' => $limit, 'is_enabled' => true]
                );
            }
        }
    }
}
