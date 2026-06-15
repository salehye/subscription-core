<?php

declare(strict_types=1);

namespace Salehye\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Salehye\Subscription\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::query()->create([
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Basic free plan with limited features.',
            'billing_cycle' => 'lifetime',
            'price' => 0,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'description' => 'Entry-level paid plan for small projects.',
            'billing_cycle' => 'monthly',
            'price' => 9.99,
            'trial_days' => 7,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Plan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'Professional plan for growing businesses.',
            'billing_cycle' => 'monthly',
            'price' => 29.99,
            'trial_days' => 7,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        Plan::query()->create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'Enterprise plan with all features and priority support.',
            'billing_cycle' => 'yearly',
            'price' => 299.99,
            'trial_days' => 14,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Add-on plans
        Plan::query()->create([
            'name' => 'Extra Storage 10GB',
            'slug' => 'extra-storage-10gb',
            'description' => 'Additional 10GB storage space.',
            'billing_cycle' => 'monthly',
            'price' => 4.99,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        Plan::query()->create([
            'name' => 'Premium Support',
            'slug' => 'premium-support',
            'description' => '24/7 priority support with 1-hour response time.',
            'billing_cycle' => 'monthly',
            'price' => 19.99,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 11,
        ]);
    }
}
