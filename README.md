# Subscription Core

[![Latest Version](https://img.shields.io/github/v/release/salehye/subscription-core)](https://github.com/salehye/subscription-core/releases)
[![PHP](https://img.shields.io/badge/PHP-^8.3-777BB4)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-^11.0|^12.0-FF2D20)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-brightgreen)](LICENSE)

A powerful, extensible subscription management package for Laravel. Manage plans, features, subscriptions, and usage tracking with multi-tenancy support — no external payment gateway required.

## Features

- ✅ **Plan Management** — Create and manage subscription plans with different billing cycles
- ✅ **Feature System** — Toggle, consumable, and limit feature types
- ✅ **Subscription Management** — Subscribe, cancel, renew, pause/resume, switch plans
- ✅ **Add-on Support** — Attach add-on subscriptions to primary subscriptions
- ✅ **Usage Tracking** — Track consumable feature usage with automatic limit enforcement
- ✅ **Feature Aggregation** — Automatically aggregates feature values from primary + add-on plans
- ✅ **Multi-Tenancy** — Optional tenant scoping for SaaS applications
- ✅ **Event System** — Rich events for all subscription lifecycle changes
- ✅ **Artisan Commands** — Expire subscriptions, reset usage, generate recurring invoices
- ✅ **Middleware** — Protect routes based on feature access
- ✅ **Polymorphic** — Works with any model (User, Team, Organization, etc.)
- ✅ **Extensible** — Replace any component via contracts/interfaces
- ✅ **No Payment Gateway** — Pure subscription logic, ready to integrate with any payment system

## Requirements

- PHP ^8.3
- Laravel ^11.0|^12.0
- MySQL / PostgreSQL / SQLite

## Installation

```bash
composer require salehye/subscription-core
```

### Publish Configuration & Migrations

```bash
# Publish config
php artisan vendor:publish --tag=subscription-config

# Publish migrations
php artisan vendor:publish --tag=subscription-migrations

# Run migrations
php artisan migrate
```

### Quick Start with Seeders

```bash
php artisan db:seed --class="Salehye\Subscription\Database\Seeders\PlanSeeder"
php artisan db:seed --class="Salehye\Subscription\Database\Seeders\FeatureSeeder"
```

## Usage

### 1. Prepare Your Model

Add the `HasSubscriptions` trait to any model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Salehye\Subscription\Traits\HasSubscriptions;

class User extends Authenticatable
{
    use HasSubscriptions;

    // Optional: Override for multi-tenancy
    public function getTenantId(): ?string
    {
        return $this->tenant_id;
    }
}
```

### 2. Create Plans & Features

```php
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Models\Feature;

// Create a plan
$plan = Plan::create([
    'name' => 'Pro Monthly',
    'slug' => 'pro-monthly',
    'description' => 'Professional plan with all features.',
    'billing_cycle' => 'monthly', // monthly, yearly, lifetime
    'price' => 29.99,
    'trial_days' => 7,
    'is_active' => true,
    'sort_order' => 1,
]);

// Create features
$feature = Feature::create([
    'name' => 'Max Users',
    'slug' => 'max_users',
    'type' => 'limit', // toggle, consumable, limit
]);

// Attach feature to plan with a value
$plan->features()->attach($feature->id, ['value' => '10']);
```

### 3. Subscribe a User

```php
$user = User::find(1);
$plan = Plan::where('slug', 'pro-monthly')->first();

// Via Facade
$subscription = \Subscription::subscribe($user, $plan);

// Via Trait
$subscription = $user->subscribeTo($plan);

// With custom trial
$subscription = $user->subscribeTo($plan, trialDays: 14, autoRenew: true);
```

### 4. Manage Subscriptions

```php
// Cancel (at period end)
$user->cancelSubscription();

// Cancel immediately
$user->cancelSubscription(true);

// Switch plan
\Subscription::switchPlan($subscription, $newPlan);

// Pause / Resume
\Subscription::pause($subscription);
\Subscription::resume($subscription);

// Attach add-on
\Subscription::attachAddon($subscription, $addonPlan);

// Renew
\Subscription::renew($subscription);
```

### 5. Check Feature Access

```php
// Check if user can access a feature
if ($user->canAccessFeature('api_access')) {
    // Grant API access
}

// Get remaining usage
$remaining = $user->remainingFeature('monthly_emails');

// Consume a feature
$user->consumeFeature('monthly_emails', 10);
```

### 6. Using the FeatureGuard Service

```php
use Salehye\Subscription\Services\FeatureGuard;

$guard = app(FeatureGuard::class);

// Check access
$guard->can($subscription, 'api_access');

// Get limit
$limit = $guard->limit($subscription, 'max_users');

// Get remaining
$remaining = $guard->remaining($subscription, 'monthly_emails');

// Get current usage
$usage = $guard->usage($subscription, 'monthly_emails');

// Consume
$guard->consume($subscription, 'monthly_emails', 5);
```

### 7. Route Middleware

Protect routes based on feature access:

```php
// In routes/web.php
Route::middleware('subscription.feature:api_access')->group(function () {
    Route::get('/api/dashboard', [ApiController::class, 'dashboard']);
});

// Redirect instead of abort
Route::middleware('subscription.feature:advanced_reports,redirect')->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
});
```

### 8. Artisan Commands

```bash
# Expire subscriptions past their end date
php artisan subscription:expire

# Preview without making changes
php artisan subscription:expire --dry-run

# Reset usage tracking
php artisan subscription:reset-usage

# Reset usage for a specific subscription
php artisan subscription:reset-usage --subscription=1

# Generate recurring invoices for renewals
php artisan subscription:invoices:generate
```

## Feature Types

| Type         | Description                            | Example Value      |
| ------------ | -------------------------------------- | ------------------ |
| `toggle`     | On/off feature access                  | `true`, `false`    |
| `consumable` | Usage-based with tracking              | `100`, `unlimited` |
| `limit`      | Dynamic check (count existing records) | `10`, `unlimited`  |

## Events

| Event                   | Description                                          |
| ----------------------- | ---------------------------------------------------- |
| `SubscriptionStarted`   | Dispatched when a subscription is created or renewed |
| `SubscriptionRenewed`   | Dispatched when a subscription is renewed            |
| `SubscriptionCancelled` | Dispatched when a subscription is cancelled          |
| `PlanChanged`           | Dispatched when a subscription switches plans        |
| `FeatureConsumed`       | Dispatched when a consumable feature is used         |
| `FeatureLimitReached`   | Dispatched when a feature limit is reached           |
| `SubscriptionExpired`   | Dispatched when a subscription expires               |

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    SubscriptionManager                       │
│  (subscribe, cancel, renew, switchPlan, pause, resume, etc) │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────┴──────────────────────────────────────┐
│                      FeatureGuard                            │
│   (can, consume, remaining, limit, usage — with resolvers)  │
└──────┬──────────────────────────────────┬───────────────────┘
       │                                  │
┌──────┴──────┐                  ┌────────┴────────┐
│ UsageTracker │                  │ FeatureResolver  │
│ (track, get, │                  │ (resolve, can,   │
│  reset)      │                  │  getLimit, etc)  │
└─────────────┘                  └─────────────────┘
```

## Customization

### Replace the Feature Resolver

Create a custom resolver:

```php
use Salehye\Subscription\Contracts\FeatureResolver;
use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Models\Subscription;

class CustomFeatureResolver implements FeatureResolver
{
    public function resolve(Subscription $subscription, Feature $feature): string
    {
        // Your custom resolution logic
    }

    // Implement other methods...
}
```

Then update `config/subscription.php`:

```php
'feature_resolver' => App\Resolvers\CustomFeatureResolver::class,
```

### Replace the Plan Repository

```php
'plan_repository' => App\Repositories\CustomPlanRepository::class,
```

### Multi-Tenancy

Enable multi-tenancy in `config/subscription.php`:

```php
'multi_tenancy' => [
    'enabled' => true,
    'tenant_column' => 'tenant_id',
    'tenant_resolver' => App\Resolvers\TenantResolver::class,
],
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). See [LICENSE](LICENSE) for more information.

## Credits

- [Saleh Al-Sanabani](https://github.com/salehye)
- [All Contributors](https://github.com/salehye/subscription-core/graphs/contributors)
