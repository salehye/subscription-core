# Architecture

## 🏗 Directory Structure

```
src/
├── Commands/
│   ├── CreateRecurringInvoices.php
│   ├── ExpireSubscriptions.php
│   └── ResetUsage.php
├── Contracts/
│   ├── FeatureResolver.php
│   ├── HasSubscriptions.php
│   ├── PlanRepository.php
│   ├── SubscriptionManager.php
│   └── TenantResolver.php
├── Enums/
│   ├── BillingCycle.php
│   ├── FeatureType.php
│   └── SubscriptionStatus.php
├── Events/
│   ├── FeatureConsumed.php
│   ├── FeatureLimitReached.php
│   ├── PlanChanged.php
│   ├── SubscriptionCancelled.php
│   ├── SubscriptionExpired.php
│   ├── SubscriptionRenewed.php
│   └── SubscriptionStarted.php
├── Exceptions/
│   ├── FeatureLimitExceededException.php
│   ├── InvalidPlanException.php
│   └── SubscriptionNotFoundException.php
├── Facades/
│   └── Subscription.php
├── Helpers/
│   └── helpers.php
├── Listeners/
│   └── SendSubscriptionNotification.php
├── Middleware/
│   └── CheckFeatureAccess.php
├── Models/
│   ├── Feature.php
│   ├── Plan.php
│   ├── Subscription.php
│   └── SubscriptionUsage.php
├── Repositories/
│   ├── EloquentPlanRepository.php
│   └── EloquentSubscriptionRepository.php
├── Resolvers/
│   ├── DefaultFeatureResolver.php
│   └── HierarchicalFeatureResolver.php
├── Services/
│   ├── FeatureGuard.php
│   ├── PricingCalculator.php
│   ├── SubscriptionManagerImpl.php
│   └── UsageTracker.php
├── Traits/
│   └── HasSubscriptions.php
└── SubscriptionServiceProvider.php
```

---

## 🧱 Design Pattern

The package follows **Contracts (Interfaces)** with **Dependency Injection**:

```
Contracts
    ↑
    ├── Repositories ← Models (Eloquent)
    ├── Services ← Services Implementation
    └── Resolvers ← Resolver Implementation
```

### Data Flow

```
1. Controller / Artisan Command
       ↓
2. Facade / Helper / Trait
       ↓
3. SubscriptionManager (Interface)
       ↓
4. SubscriptionManagerImpl (Implementation)
       ↓
5. Repositories ← Models ← Database
       ↓
6. Events → Listeners (Notifications)
```

---

## 🔌 Service Provider

`SubscriptionServiceProvider` performs:

- **register()**: Binds all contracts to implementations
- **boot()**:
  - Publishes config and migrations
  - Registers middleware alias
  - Registers commands
  - Registers events and listeners
  - Registers Facade

---
