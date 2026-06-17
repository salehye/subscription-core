<?php

declare(strict_types=1);

namespace Salehye\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Salehye\Subscription\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = config('subscription_seeder.plans', []);

        foreach ($plans as $planData) {
            Plan::query()->updateOrCreate(
                ['slug' => $planData['slug']],
                [
                    'name' => $planData['name'],
                    'description' => $planData['description'] ?? null,
                    'billing_cycle' => $planData['billing_cycle'],
                    'price' => $planData['price'] ?? 0,
                    'trial_days' => $planData['trial_days'] ?? 0,
                    'is_active' => $planData['is_active'] ?? true,
                    'sort_order' => $planData['sort_order'] ?? 0,
                    'metadata' => $planData['metadata'] ?? null,
                ],
            );
        }
    }
}
