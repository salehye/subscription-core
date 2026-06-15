# Installation & Configuration

## 💻 Requirements

| Requirement | Version                     |
| ----------- | --------------------------- |
| PHP         | `^8.3`                      |
| Laravel     | `^11.0 \| ^12.0 \| ^13.0`   |
| Database    | MySQL / PostgreSQL / SQLite |

---

## 📦 Installation

### 1. Install via Composer

```bash
composer require salehye/subscription-core
```

### 2. Publish Config & Migrations

```bash
# Publish config file
php artisan vendor:publish --tag=subscription-config

# Publish migration files
php artisan vendor:publish --tag=subscription-migrations

# Run migrations
php artisan migrate
```

### 3. Quick Start with Seeders

```bash
php artisan db:seed --class="Salehye\Subscription\Database\Seeders\PlanSeeder"
php artisan db:seed --class="Salehye\Subscription\Database\Seeders\FeatureSeeder"
```

---

## ⚙️ Configuration `config/subscription.php`

```php
<?php

return [
    // Default models — you can extend them
    'models' => [
        'plan' => Salehye\Subscription\Models\Plan::class,
        'feature' => Salehye\Subscription\Models\Feature::class,
        'subscription' => Salehye\Subscription\Models\Subscription::class,
        'subscription_usage' => Salehye\Subscription\Models\SubscriptionUsage::class,
    ],

    // Billing settings
    'billing' => [
        'monthly_days' => 30,
        'yearly_days' => 365,
        'lifetime_days' => 36500, // ~100 years
    ],

    // Trial settings
    'trial' => [
        'enabled' => true,
        'default_days' => 7,
    ],

    // Cache settings
    'cache' => [
        'enabled' => env('SUBSCRIPTION_CACHE_ENABLED', true),
        'ttl' => env('SUBSCRIPTION_CACHE_TTL', 3600),
    ],

    // Feature resolver
    'feature_resolver' => Salehye\Subscription\Resolvers\DefaultFeatureResolver::class,

    // Multi-tenancy
    'multi_tenancy' => [
        'enabled' => env('SUBSCRIPTION_MULTI_TENANCY', false),
        'tenant_column' => 'tenant_id',
        'tenant_resolver' => null,
    ],

    // Unlimited value indicator
    'unlimited_value' => 'unlimited',

    // Active subscription statuses
    'active_statuses' => ['active', 'paused'],

    // Grace period days
    'grace_period_days' => 0,
];
```

### Configuration Reference

| Setting                 | Description                          |
| ----------------------- | ------------------------------------ |
| `models`                | Model classes — can be extended      |
| `billing.monthly_days`  | Days in a monthly cycle (default 30) |
| `billing.yearly_days`   | Days in a yearly cycle (default 365) |
| `billing.lifetime_days` | Days for lifetime (default 36500)    |
| `trial.enabled`         | Enable trial period                  |
| `trial.default_days`    | Default trial days                   |
| `cache.enabled`         | Enable feature caching               |
| `cache.ttl`             | Cache TTL in seconds                 |
| `multi_tenancy.enabled` | Enable multi-tenancy                 |
| `unlimited_value`       | Value representing "unlimited"       |
| `active_statuses`       | Statuses considered "active"         |
| `grace_period_days`     | Grace period after expiration        |

---
