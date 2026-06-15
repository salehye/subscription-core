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
        // Create features
        $maxUsers = Feature::query()->create([
            'name' => 'Max Users',
            'slug' => 'max_users',
            'type' => 'limit',
            'description' => 'Maximum number of users allowed.',
        ]);

        $maxStorage = Feature::query()->create([
            'name' => 'Max Storage (MB)',
            'slug' => 'max_storage',
            'type' => 'consumable',
            'description' => 'Maximum storage space in megabytes.',
        ]);

        $apiAccess = Feature::query()->create([
            'name' => 'API Access',
            'slug' => 'api_access',
            'type' => 'toggle',
            'description' => 'Enable or disable API access.',
        ]);

        $advancedReports = Feature::query()->create([
            'name' => 'Advanced Reports',
            'slug' => 'advanced_reports',
            'type' => 'toggle',
            'description' => 'Access to advanced reporting features.',
        ]);

        $prioritySupport = Feature::query()->create([
            'name' => 'Priority Support',
            'slug' => 'priority_support',
            'type' => 'toggle',
            'description' => 'Priority customer support access.',
        ]);

        $maxProjects = Feature::query()->create([
            'name' => 'Max Projects',
            'slug' => 'max_projects',
            'type' => 'limit',
            'description' => 'Maximum number of projects allowed.',
        ]);

        $monthlyEmails = Feature::query()->create([
            'name' => 'Monthly Emails',
            'slug' => 'monthly_emails',
            'type' => 'consumable',
            'description' => 'Number of emails that can be sent per month.',
        ]);

        // Assign features to plans
        $free = Plan::query()->where('slug', 'free')->first();
        $free?->features()->attach([
            $maxUsers->id => ['value' => '3'],
            $maxStorage->id => ['value' => '100'],
            $apiAccess->id => ['value' => 'false'],
            $advancedReports->id => ['value' => 'false'],
            $prioritySupport->id => ['value' => 'false'],
            $maxProjects->id => ['value' => '1'],
            $monthlyEmails->id => ['value' => '100'],
        ]);

        $basic = Plan::query()->where('slug', 'basic')->first();
        $basic?->features()->attach([
            $maxUsers->id => ['value' => '10'],
            $maxStorage->id => ['value' => '1000'],
            $apiAccess->id => ['value' => 'true'],
            $advancedReports->id => ['value' => 'false'],
            $prioritySupport->id => ['value' => 'false'],
            $maxProjects->id => ['value' => '5'],
            $monthlyEmails->id => ['value' => '5000'],
        ]);

        $pro = Plan::query()->where('slug', 'pro')->first();
        $pro?->features()->attach([
            $maxUsers->id => ['value' => '50'],
            $maxStorage->id => ['value' => '10000'],
            $apiAccess->id => ['value' => 'true'],
            $advancedReports->id => ['value' => 'true'],
            $prioritySupport->id => ['value' => 'false'],
            $maxProjects->id => ['value' => 'unlimited'],
            $monthlyEmails->id => ['value' => '50000'],
        ]);

        $enterprise = Plan::query()->where('slug', 'enterprise')->first();
        $enterprise?->features()->attach([
            $maxUsers->id => ['value' => 'unlimited'],
            $maxStorage->id => ['value' => 'unlimited'],
            $apiAccess->id => ['value' => 'true'],
            $advancedReports->id => ['value' => 'true'],
            $prioritySupport->id => ['value' => 'true'],
            $maxProjects->id => ['value' => 'unlimited'],
            $monthlyEmails->id => ['value' => 'unlimited'],
        ]);

        // Add-on plan features
        $extraStorage = Plan::query()->where('slug', 'extra-storage-10gb')->first();
        $extraStorage?->features()->attach([
            $maxStorage->id => ['value' => '10240'], // 10GB in MB
        ]);

        $premiumSupport = Plan::query()->where('slug', 'premium-support')->first();
        $premiumSupport?->features()->attach([
            $prioritySupport->id => ['value' => 'true'],
        ]);
    }
}
