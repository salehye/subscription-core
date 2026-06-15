---
name: subscription-core
description: "Develops subscription management with Laravel. Activates when creating or managing subscription plans;
adding the HasSubscriptions trait to models; subscribing users to plans; checking feature access (toggle, consumable,
limit); tracking consumable usage; handling billing cycles (monthly, yearly, lifetime); managing add-ons, pauses,
cancellations, or plan switches; configuring multi-tenancy; running subscription Artisan commands; or when the user
mentions subscriptions, plans, features, or SaaS metering in a Laravel project. Make sure to use this skill whenever the
user works with subscription functionality in Laravel, even if they don't explicitly mention the package name."
license: MIT
metadata:
author: salehye
---
@php
    /** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Subscription Core

A powerful Laravel package for managing subscriptions, plans, features, usage tracking, and multi-tenancy.

## When to Apply

Activate this skill when:

- Creating or managing **subscription plans** with different billing cycles
- Making Eloquent models **subscribable** (User, Team, Organization, etc.)
- **Subscribing** models to plans with trials and auto-renewal
- Checking **feature access** (`toggle`, `consumable`, `limit`)
- Tracking **consumable usage** with automatic limit enforcement
- Managing **add-on subscriptions** attached to primary subscriptions
- **Canceling, pausing, resuming, renewing**, or **switching plans**
- Configuring **multi-tenancy** for SaaS applications
- Running **subscription lifecycle** Artisan commands
- Handling **subscription events** and notifications

## Installation

```bash
composer require salehye/subscription-core
```

```bash
{{ $assist->artisanCommand('vendor:publish --tag=subscription-config') }}
{{ $assist->artisanCommand('vendor:publish --tag=subscription-migrations') }}
{{ $assist->artisanCommand('migrate') }}
```

### Add the HasSubscriptions Trait

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

## Key Concepts

### Models

| Model | Description |
|-------|-------------|
| `Plan` | Subscription plans with billing cycle, price, trial days, soft deletes |
| `Feature` | Features with type: `toggle`, `consumable`, `limit` |
| `Subscription` | Polymorphic subscriber subscriptions (primary + addon), multi-tenancy |
| `SubscriptionUsage` | Tracks consumable feature usage per subscription |

### Enums

| Enum | Values |
|------|--------|
| `BillingCycle` | `monthly` (30d), `yearly` (365d), `lifetime` (36500d) |
| `FeatureType` | `toggle` (on/off), `consumable` (usage-based), `limit` (dynamic check) |
| `SubscriptionStatus` | `active`, `canceled`, `expired`, `suspended`, `paused`, `pending` |

### Services

| Service | Purpose |
|---------|---------|
| `SubscriptionManagerImpl` | Core operations: subscribe, cancel, renew, switchPlan, pause, resume, attachAddon |
| `FeatureGuard` | Check feature access, consume, get remaining/limit/usage |
| `UsageTracker` | Track and get/reset consumable usage records |
| `PricingCalculator` | Calculate prorated prices, trial periods, renewal costs |

## Creating Plans & Features

```php
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Models\Feature;

$plan = Plan::create([
'name' => 'Pro Monthly',
'slug' => 'pro-monthly',
'billing_cycle' => 'monthly',
'price' => 29.99,
'trial_days' => 7,
'is_active' => true,
'sort_order' => 1,
]);

$feature = Feature::create([
'name' => 'Max Users',
'slug' => 'max_users',
'type' => 'limit',
]);

$plan->features()->attach($feature->id, ['value' => '10']);
```

## Subscribing Users

```php
$user = User::find(1);
$plan = Plan::where('slug', 'pro-monthly')->first();

// Via Facade
\Subscription::subscribe($user, $plan);

// Via Trait
$user->subscribeTo($plan);
$user->subscribeTo($plan, trialDays: 14, autoRenew: true);
```

## Managing Subscriptions

```php
\Subscription::cancel($subscription); // At period end
\Subscription::cancel($subscription, true); // Immediately
\Subscription::switchPlan($subscription, $newPlan);
\Subscription::pause($subscription);
\Subscription::resume($subscription);
\Subscription::renew($subscription);
\Subscription::attachAddon($subscription, $addonPlan);
```

## Checking Feature Access

```php
// Check access
if ($user->canAccessFeature('api_access')) {
// Grant API access
}

// Get remaining / consume
$user->remainingFeature('monthly_emails');
$user->consumeFeature('monthly_emails', 10);
```

## Using FeatureGuard

```php
use Salehye\Subscription\Services\FeatureGuard;

$guard = app(FeatureGuard::class);

$guard->can($subscription, 'api_access');
$guard->consume($subscription, 'monthly_emails', 5);
$guard->remaining($subscription, 'monthly_emails');
$guard->limit($subscription, 'max_users');
$guard->usage($subscription, 'monthly_emails');
```

## Route Middleware

```php
Route::middleware('subscription.feature:api_access')->group(function () {
Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Redirect on failure
Route::middleware('subscription.feature:premium,redirect')->group(function () {});
```

## Feature Types

| Type | Description | Example Value |
|------|-------------|---------------|
| `toggle` | On/off feature access | `true`, `false` |
| `consumable` | Usage-based with tracking | `100`, `unlimited` |
| `limit` | Dynamic check (count existing records) | `10`, `unlimited` |

## Events

| Event | Fires When |
|-------|-----------|
| `SubscriptionStarted` | Subscription created or renewed |
| `SubscriptionRenewed` | Subscription renewed |
| `SubscriptionCancelled` | Subscription cancelled |
| `PlanChanged` | Subscription switches plan |
| `FeatureConsumed` | Consumable feature used |
| `FeatureLimitReached` | Feature limit reached |
| `SubscriptionExpired` | Subscription expires |

## Key Artisan Commands

| Command | Purpose |
|---------|---------|
| `{{ $assist->artisanCommand('subscription:expire') }}` | Expire overdue subscriptions |
| `{{ $assist->artisanCommand('subscription:expire --dry-run') }}` | Preview without making changes |
| `{{ $assist->artisanCommand('subscription:reset-usage') }}` | Reset all usage tracking |
| `{{ $assist->artisanCommand('subscription:reset-usage --subscription=1') }}` | Reset usage for a specific subscription
|
| `{{ $assist->artisanCommand('subscription:invoices:generate') }}` | Generate recurring invoices |

## Multi-Tenancy

```php
// config/subscription.php
'multi_tenancy' => [
'enabled' => true,
'tenant_column' => 'tenant_id',
'tenant_resolver' => App\Resolvers\TenantResolver::class,
],
```

## Customization

```php
// config/subscription.php
'feature_resolver' => App\Resolvers\CustomFeatureResolver::class,
'plan_repository' => App\Repositories\CustomPlanRepository::class,
```

## Architecture

```
┌──────────────────────────────────────┐
│ SubscriptionManager │
│ (subscribe, cancel, renew, switch, │
│ pause, resume, attachAddon) │
└──────────┬───────────────────────────┘
│
┌──────────┴───────────────────────────┐
│ FeatureGuard │
│ (can, consume, remaining, limit, │
│ usage — with resolvers) │
└──────┬────────────────────┬──────────┘
│ │
┌──────┴─────┐ ┌───────┴──────────┐
│ UsageTracker│ │ FeatureResolver │
│ (track, get,│ │ (resolve, can, │
│ reset) │ │ getLimit, etc) │
└────────────┘ └──────────────────┘
```

## Testing

```bash
{{ $assist->artisanCommand('test') }}
```

## Common Pitfalls

- **Unlimited values** — Use the string `'unlimited'` (not `-1` or `null`) in pivot tables
- **Add-on aggregation** — Feature values from add-ons auto-aggregate with primary subscription
- **Multi-tenancy scoping** — All queries auto-scope to tenant when enabled. Ensure `getTenantId()` is consistent
- **Grace period** — `config.subscription.grace_period_days` keeps features accessible after expiration. Set to `0` to
disable
- **Cache invalidation** — Feature lookups cached per subscription. Clear with
`Cache::forget('subscription_features_'.$id)` if pivots change
- **Polymorphic subscriber** — `subscriber_id` is a `string` column. Cast keys properly