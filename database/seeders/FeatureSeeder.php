<?php

declare(strict_types=1);

namespace Salehye\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Models\Plan;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = config('subscription_seeder.features', []);
        $planFeatures = config('subscription_seeder.plan_features', []);

        $featureIds = [];

        foreach ($features as $featureData) {
            $feature = Feature::query()->updateOrCreate(
                ['slug' => $featureData['slug']],
                [
                    'name' => $featureData['name'],
                    'type' => $featureData['type'],
                    'description' => $featureData['description'] ?? null,
                    'metadata' => $featureData['metadata'] ?? null,
                ],
            );

            $featureIds[$featureData['slug']] = $feature->id;
        }

        foreach ($planFeatures as $planSlug => $featureValues) {
            $plan = Plan::query()->where('slug', $planSlug)->first();

            if (!$plan) {
                continue;
            }

            $syncData = [];

            foreach ($featureValues as $featureSlug => $value) {
                if (!isset($featureIds[$featureSlug])) {
                    continue;
                }

                $syncData[$featureIds[$featureSlug]] = ['value' => $value];
            }

            $plan->features()->sync($syncData);
        }
    }
}
