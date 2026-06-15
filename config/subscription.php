<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Models
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default model classes used by the package.
    | You can extend these models and update the references here.
    |
    */

    'models' => [
        'plan' => Salehye\Subscription\Models\Plan::class,
        'feature' => Salehye\Subscription\Models\Feature::class,
        'subscription' => Salehye\Subscription\Models\Subscription::class,
        'subscription_usage' => Salehye\Subscription\Models\SubscriptionUsage::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Billing Cycle
    |--------------------------------------------------------------------------
    |
    | The default billing cycle duration in days for each cycle type.
    |
    */

    'billing' => [
        'monthly_days' => 30,
        'yearly_days' => 365,
        'lifetime_days' => 36500, // ~100 years
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial Settings
    |--------------------------------------------------------------------------
    |
    | Default trial period settings for new subscriptions.
    |
    */

    'trial' => [
        'enabled' => true,
        'default_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Caching
    |--------------------------------------------------------------------------
    |
    | Enable or disable caching for feature lookups. When enabled, the
    | package will cache feature values per subscription for better
    | performance. Disable during development if needed.
    |
    */

    'cache' => [
        'enabled' => env('SUBSCRIPTION_CACHE_ENABLED', true),
        'ttl' => env('SUBSCRIPTION_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'subscription_features_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Resolver
    |--------------------------------------------------------------------------
    |
    | The default feature resolver class. You can replace this with your
    | own implementation to support hierarchical or custom resolution.
    |
    */

    'feature_resolver' => Salehye\Subscription\Resolvers\DefaultFeatureResolver::class,

    /*
    |--------------------------------------------------------------------------
    | Plan Repository
    |--------------------------------------------------------------------------
    |
    | The default plan repository class. Replace with your own implementation
    | if you need custom storage logic.
    |
    */

    'plan_repository' => Salehye\Subscription\Repositories\EloquentPlanRepository::class,

    /*
    |--------------------------------------------------------------------------
    | Subscription Repository
    |--------------------------------------------------------------------------
    |
    | The default subscription repository class.
    |
    */

    'subscription_repository' => Salehye\Subscription\Repositories\EloquentSubscriptionRepository::class,

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | Configure multi-tenancy support. When enabled, the package will
    | scope queries and operations to the current tenant.
    |
    */

    'multi_tenancy' => [
        'enabled' => env('SUBSCRIPTION_MULTI_TENANCY', false),
        'tenant_column' => 'tenant_id',
        'tenant_resolver' => null, // Custom resolver class implementing TenantResolver
    ],

    /*
    |--------------------------------------------------------------------------
    | Unlimited Value Indicator
    |--------------------------------------------------------------------------
    |
    | The string value used in pivot tables to indicate unlimited usage.
    |
    */

    'unlimited_value' => 'unlimited',

    /*
    |--------------------------------------------------------------------------
    | Subscription Statuses
    |--------------------------------------------------------------------------
    |
    | The default subscription statuses that are considered "active" for
    | feature checking purposes.
    |
    */

    'active_statuses' => [
        Salehye\Subscription\Enums\SubscriptionStatus::Active,
    ],

    /*
    |--------------------------------------------------------------------------
    | Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of days after expiration before the subscription is fully
    | deactivated. During this period, features are still accessible.
    |
    */

    'grace_period_days' => env('SUBSCRIPTION_GRACE_PERIOD', 0),

    /*
    |--------------------------------------------------------------------------
    | Event Discovery
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will auto-register events and listeners.
    | Disable if you want to register them manually in your EventServiceProvider.
    |
    */

    'auto_register_events' => true,
];
