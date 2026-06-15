# Subscription Core — Architecture Reference

## Directory Structure

```
src/
├── Commands/
│   ├── CreateRecurringInvoices.php   # Generate recurring invoices
│   ├── ExpireSubscriptions.php       # Expire overdue subscriptions
│   └── ResetUsage.php                # Reset consumable usage tracking
├── Contracts/
│   ├── FeatureResolver.php           # Feature value resolution contract
│   ├── HasSubscriptions.php          # Subscriber contract
│   ├── PlanRepository.php            # Plan storage contract
│   ├── SubscriptionManager.php       # Core operations contract
│   └── TenantResolver.php            # Multi-tenancy contract
├── Enums/
│   ├── BillingCycle.php              # monthly, yearly, lifetime
│   ├── FeatureType.php               # toggle, consumable, limit
│   └── SubscriptionStatus.php        # active, canceled, expired, etc.
├── Events/                           # 7 lifecycle events
├── Exceptions/                       # FeatureLimitExceeded, InvalidPlan, SubscriptionNotFound
├── Facades/
│   └── Subscription.php              # Subscription facade
├── Helpers/                          # Global helper functions
├── Listeners/
│   └── SendSubscriptionNotification.php
├── Middleware/
│   └── CheckFeatureAccess.php        # Route middleware
├── Models/
│   ├── Plan.php                      # Plan model (soft deletes)
│   ├── Feature.php                   # Feature model
│   ├── Subscription.php              # Polymorphic subscription model
│   └── SubscriptionUsage.php         # Usage tracking model
├── Repositories/                     # Eloquent repositories
├── Resolvers/                        # Default feature resolver
├── Services/
│   ├── FeatureGuard.php              # Feature access guard
│   ├── PricingCalculator.php         # Price calculations
│   ├── SubscriptionManagerImpl.php   # Core implementation
│   └── UsageTracker.php              # Usage tracking
└── Traits/
    └── HasSubscriptions.php          # Subscriber trait

config/subscription.php               # Package configuration
database/
├── migrations/                       # 5 migration files
└── seeders/                          # Plan & Feature seeders
```

## Data Flow

```
User (HasSubscriptions)
  │
  ├── subscribeTo($plan)
  │     └── SubscriptionManager::subscribe()
  │           └── Creates Subscription (polymorphic)
  │
  ├── canAccessFeature($slug)
  │     └── FeatureGuard::can()
  │           └── FeatureResolver::canAccess()
  │                 └── Checks pivot values + addons
  │
  ├── consumeFeature($slug, $units)
  │     └── FeatureGuard::consume()
  │           └── UsageTracker::track()
  │                 └── Checks limits → dispatches events
  │
  └── remainingFeature($slug)
        └── FeatureGuard::remaining()
              └── FeatureResolver::getLimit() - UsageTracker::getUsage()
```

## Migration Structure

1. `2025_01_01_000001_create_plans_table.php`
2. `2025_01_01_000002_create_features_table.php`
3. `2025_01_01_000003_create_plan_feature_table.php` (pivot with `value`)
4. `2025_01_01_000004_create_subscriptions_table.php` (polymorphic subscriber)
5. `2025_01_01_000005_create_subscription_usage_table.php`
