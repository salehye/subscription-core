<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Seeder Plans
    |--------------------------------------------------------------------------
    |
    | Define the plans that will be seeded into the database. Each plan
    | requires a unique slug. You can add, remove, or modify plans here
    | to match your application's pricing structure.
    |
    | Supported billing_cycle values: "monthly", "yearly", "lifetime"
    |
    */

    'plans' => [
        [
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Basic free plan with limited features.',
            'billing_cycle' => 'lifetime',
            'price' => 0,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ],
        [
            'name' => 'Basic',
            'slug' => 'basic',
            'description' => 'Entry-level paid plan for small projects.',
            'billing_cycle' => 'monthly',
            'price' => 9.99,
            'trial_days' => 7,
            'is_active' => true,
            'sort_order' => 2,
        ],
        [
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'Professional plan for growing businesses.',
            'billing_cycle' => 'monthly',
            'price' => 29.99,
            'trial_days' => 7,
            'is_active' => true,
            'sort_order' => 3,
        ],
        [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'Enterprise plan with all features and priority support.',
            'billing_cycle' => 'yearly',
            'price' => 299.99,
            'trial_days' => 14,
            'is_active' => true,
            'sort_order' => 4,
        ],

        // Add-on plans
        [
            'name' => 'Extra Storage 10GB',
            'slug' => 'extra-storage-10gb',
            'description' => 'Additional 10GB storage space.',
            'billing_cycle' => 'monthly',
            'price' => 4.99,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 10,
        ],
        [
            'name' => 'Premium Support',
            'slug' => 'premium-support',
            'description' => '24/7 priority support with 1-hour response time.',
            'billing_cycle' => 'monthly',
            'price' => 19.99,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 11,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeder Features
    |--------------------------------------------------------------------------
    |
    | Define the features that will be seeded into the database. Each feature
    | requires a unique slug. Supported type values: "toggle", "consumable", "limit"
    |
    | - toggle:   boolean on/off (value: "true" or "false")
    | - consumable: usage-based with tracking (value: numeric string or "unlimited")
    | - limit:     static numeric cap (value: numeric string or "unlimited")
    |
    */

    'features' => [
        [
            'name' => 'Max Users',
            'slug' => 'max_users',
            'type' => 'limit',
            'description' => 'Maximum number of users allowed.',
        ],
        [
            'name' => 'Max Storage (MB)',
            'slug' => 'max_storage',
            'type' => 'consumable',
            'description' => 'Maximum storage space in megabytes.',
        ],
        [
            'name' => 'API Access',
            'slug' => 'api_access',
            'type' => 'toggle',
            'description' => 'Enable or disable API access.',
        ],
        [
            'name' => 'Advanced Reports',
            'slug' => 'advanced_reports',
            'type' => 'toggle',
            'description' => 'Access to advanced reporting features.',
        ],
        [
            'name' => 'Priority Support',
            'slug' => 'priority_support',
            'type' => 'toggle',
            'description' => 'Priority customer support access.',
        ],
        [
            'name' => 'Max Projects',
            'slug' => 'max_projects',
            'type' => 'limit',
            'description' => 'Maximum number of projects allowed.',
        ],
        [
            'name' => 'Monthly Emails',
            'slug' => 'monthly_emails',
            'type' => 'consumable',
            'description' => 'Number of emails that can be sent per month.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan-Feature Mapping
    |--------------------------------------------------------------------------
    |
    | Map each plan slug to its feature values. The key is the plan slug,
    | and the value is an associative array of feature_slug => value pairs.
    |
    | Values depend on the feature type:
    | - toggle:   "true" or "false"
    | - consumable/limit: numeric string or "unlimited"
    |
    | Omit a feature from a plan's mapping to leave it unattached.
    |
    */

    'plan_features' => [
        'free' => [
            'max_users' => '3',
            'max_storage' => '100',
            'api_access' => 'false',
            'advanced_reports' => 'false',
            'priority_support' => 'false',
            'max_projects' => '1',
            'monthly_emails' => '100',
        ],
        'basic' => [
            'max_users' => '10',
            'max_storage' => '1000',
            'api_access' => 'true',
            'advanced_reports' => 'false',
            'priority_support' => 'false',
            'max_projects' => '5',
            'monthly_emails' => '5000',
        ],
        'pro' => [
            'max_users' => '50',
            'max_storage' => '10000',
            'api_access' => 'true',
            'advanced_reports' => 'true',
            'priority_support' => 'false',
            'max_projects' => 'unlimited',
            'monthly_emails' => '50000',
        ],
        'enterprise' => [
            'max_users' => 'unlimited',
            'max_storage' => 'unlimited',
            'api_access' => 'true',
            'advanced_reports' => 'true',
            'priority_support' => 'true',
            'max_projects' => 'unlimited',
            'monthly_emails' => 'unlimited',
        ],

        // Add-on plan features
        'extra-storage-10gb' => [
            'max_storage' => '10240',
        ],
        'premium-support' => [
            'priority_support' => 'true',
        ],
    ],
];
