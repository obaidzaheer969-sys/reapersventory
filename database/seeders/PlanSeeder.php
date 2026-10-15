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
        // Rename legacy Growth → Grow (keep subscriptions linked by plan id).
        Plan::withTrashed()
            ->where('slug', 'growth')
            ->update([
                'name' => 'Grow',
                'slug' => 'grow',
            ]);

        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => '10 products, 1 user, 1 warehouse, unlimited billing/invoices.',
                'price_monthly' => 2999,
                'price_yearly' => 29990,
                'trial_days' => 7,
                'is_featured' => false,
                'sort_order' => 1,
                'features' => [
                    PlanFeatureKey::ProductsLimit->value => 10,
                    PlanFeatureKey::InvoicesLimit->value => null,
                    PlanFeatureKey::WarehousesLimit->value => 1,
                    PlanFeatureKey::UsersLimit->value => 1,
                ],
            ],
            [
                'name' => 'Grow',
                'slug' => 'grow',
                'description' => '30 products, 2 users, 1 warehouse, unlimited billing/invoices.',
                'price_monthly' => 6999,
                'price_yearly' => 69990,
                'trial_days' => 7,
                'is_featured' => true,
                'sort_order' => 2,
                'features' => [
                    PlanFeatureKey::ProductsLimit->value => 30,
                    PlanFeatureKey::InvoicesLimit->value => null,
                    PlanFeatureKey::WarehousesLimit->value => 1,
                    PlanFeatureKey::UsersLimit->value => 2,
                ],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Unlimited products, 2 users, 2 warehouses, unlimited billing/invoices.',
                'price_monthly' => 12999,
                'price_yearly' => 129990,
                'trial_days' => 14,
                'is_featured' => false,
                'sort_order' => 3,
                'features' => [
                    PlanFeatureKey::ProductsLimit->value => null,
                    PlanFeatureKey::InvoicesLimit->value => null,
                    PlanFeatureKey::WarehousesLimit->value => 2,
                    PlanFeatureKey::UsersLimit->value => 2,
                ],
            ],
            [
                'name' => 'Grow Business',
                'slug' => 'grow-business',
                'description' => 'Unlimited products, 3 users, 3 warehouses, unlimited billing/invoices.',
                'price_monthly' => 19999,
                'price_yearly' => 199990,
                'trial_days' => 14,
                'is_featured' => false,
                'sort_order' => 4,
                'features' => [
                    PlanFeatureKey::ProductsLimit->value => null,
                    PlanFeatureKey::InvoicesLimit->value => null,
                    PlanFeatureKey::WarehousesLimit->value => 3,
                    PlanFeatureKey::UsersLimit->value => 3,
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
