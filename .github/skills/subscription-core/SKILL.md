---
name: subscription-core
description: 'Laravel subscription management package with plans, features, multi-tenancy, usage tracking, billing cycles, events, and Artisan commands. Use when working with subscriptions, plans, features, subscriber billing, or SaaS metering.'
---

# Subscription Core

A powerful Laravel package for managing subscriptions, plans, features, and usage tracking with multi-tenancy support.

## When to Use

- Creating and managing **subscription plans** (monthly, yearly, lifetime)
- **Subscribing** any model (User, Team, Organization) to plans
- Checking **feature access** (`toggle`, `consumable`, `limit`)
- Tracking **consumable usage** with limit enforcement
- Managing **add-on subscriptions** attached to primary subscriptions
- **Multi-tenant** subscription scoping
- Running **subscription lifecycle** commands (expire, reset, invoices)

## Quick Start

```bash
composer require salehye/subscription-core

php artisan vendor:publish --tag=subscription-config
php artisan vendor:publish --tag=subscription-migrations
php artisan migrate
```

## Key Concepts

### Models (`src/Models/`)

| Model | Description |
|-------|-------------|
| `Plan` | Subscription plans with billing cycle, price, trial days, soft deletes |
| `Feature` | Features with type: `toggle`, `consumable`, `limit` |
| `Subscription` | Polymorphic subscriber subscriptions (primary + addon), supports multi-tenancy |
| `SubscriptionUsage` | Tracks consumable feature usage per subscription |

### Enums (`src/Enums/`)

| Enum | Values |
|------|--------|
| `BillingCycle` | `monthly` (30d), `yearly` (365d), `lifetime` (36500d) |
| `FeatureType` | `toggle` (on/off), `consumable` (usage-based), `limit` (dynamic check) |
| `SubscriptionStatus` | `active`, `canceled`, `expired`, `suspended`, `paused`, `pending` |

### Services (`src/Services/`)

| Service | Purpose |
|---------|---------|
| `SubscriptionManagerImpl` | Core operations: subscribe, cancel, renew, switchPlan, pause, resume, attachAddon |
| `FeatureGuard` | Check feature access, consume, get remaining/limit/usage |
| `UsageTracker` | Track and get/reset consumable usage records |
| `PricingCalculator` | Calculate prorated prices, trial periods, renewal costs |

### Artisan Commands

```bash
php artisan subscription:expire          # Expire overdue subscriptions
php artisan subscription:expire --dry-run # Preview only
php artisan subscription:reset-usage      # Reset all usage tracking
php artisan subscription:reset-usage --subscription=1  # Reset specific
php artisan subscription:invoices:generate # Generate recurring invoices
```

## Usage Patterns

### 1. Prepare Your Model

```php
use Salehye\Subscription\Traits\HasSubscriptions;

class User extends Authenticatable
{
    use HasSubscriptions;

    // For multi-tenancy:
    public function getTenantId(): ?string
    {
        return $this->tenant_id;
    }
}
```

### 2. Create Plan with Features

```php
$plan = Plan::create([
    'name' => 'Pro Monthly',
    'slug' => 'pro-monthly',
    'billing_cycle' => 'monthly',
    'price' => 29.99,
    'trial_days' => 7,
    'is_active' => true,
]);

$feature = Feature::create([
    'name' => 'Max Users',
    'slug' => 'max_users',
    'type' => 'limit',
]);

$plan->features()->attach($feature->id, ['value' => '10']);
```

### 3. Subscribe

```php
// Via Facade
\Subscription::subscribe($user, $plan);

// Via Trait
$user->subscribeTo($plan);
$user->subscribeTo($plan, trialDays: 14, autoRenew: true);
```

### 4. Manage Subscription

```php
\Subscription::cancel($subscription);             // At period end
\Subscription::cancel($subscription, true);        // Immediately
\Subscription::switchPlan($subscription, $newPlan);
\Subscription::pause($subscription);
\Subscription::resume($subscription);
\Subscription::renew($subscription);
\Subscription::attachAddon($subscription, $addonPlan);
```

### 5. Check Features

```php
$user->canAccessFeature('api_access');
$user->consumeFeature('monthly_emails', 10);
$user->remainingFeature('monthly_emails');
```

### 6. Using FeatureGuard

```php
$guard = app(\Salehye\Subscription\Services\FeatureGuard::class);

$guard->can($subscription, 'api_access');
$guard->consume($subscription, 'monthly_emails', 5);
$guard->remaining($subscription, 'monthly_emails');
$guard->limit($subscription, 'max_users');
$guard->usage($subscription, 'monthly_emails');
```

### 7. Route Middleware

```php
Route::middleware('subscription.feature:api_access')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Redirect on failure
Route::middleware('subscription.feature:premium,redirect')->group(function () {});
```

## Events (`src/Events/`)

| Event | Fires When |
|-------|-----------|
| `SubscriptionStarted` | Subscription created or renewed |
| `SubscriptionRenewed` | Subscription renewed |
| `SubscriptionCancelled` | Subscription cancelled |
| `PlanChanged` | Subscription switches plan |
| `FeatureConsumed` | Consumable feature used |
| `FeatureLimitReached` | Feature limit hit |
| `SubscriptionExpired` | Subscription expires |

## Architecture

```
┌──────────────────────────────────────┐
│         SubscriptionManager          │
│  (subscribe, cancel, renew, switch,  │
│   pause, resume, attachAddon)        │
└──────────┬───────────────────────────┘
           │
┌──────────┴───────────────────────────┐
│           FeatureGuard               │
│  (can, consume, remaining, limit,    │
│   usage — with resolvers)           │
└──────┬────────────────────┬──────────┘
       │                    │
┌──────┴─────┐     ┌───────┴──────────┐
│ UsageTracker│     │ FeatureResolver  │
│ (track, get,│     │ (resolve, can,   │
│  reset)     │     │  getLimit, etc)  │
└────────────┘     └──────────────────┘
```

## Customization

Replace any component via `config/subscription.php`:

```php
'feature_resolver' => App\Resolvers\CustomFeatureResolver::class,
'plan_repository' => App\Repositories\CustomPlanRepository::class,
```

Multi-tenancy config:

```php
'multi_tenancy' => [
    'enabled' => true,
    'tenant_column' => 'tenant_id',
    'tenant_resolver' => App\Resolvers\TenantResolver::class,
],
```

## Contracts (`src/Contracts/`)

| Contract | Purpose |
|----------|---------|
| `SubscriptionManager` | Core subscription operations interface |
| `FeatureResolver` | Feature value resolution interface |
| `PlanRepository` | Plan storage interface |
| `TenantResolver` | Multi-tenancy tenant resolution |
| `HasSubscriptions` | Subscriber contract |

## Testing

```bash
composer test
```
