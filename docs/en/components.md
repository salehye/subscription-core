# Components

## 1. Enums

### BillingCycle

```php
use Salehye\Subscription\Enums\BillingCycle;

BillingCycle::Monthly;   // 'monthly'  → 30 days
BillingCycle::Yearly;    // 'yearly'   → 365 days
BillingCycle::Lifetime;  // 'lifetime' → 36500 days

$days = BillingCycle::Monthly->days(); // 30
$label = BillingCycle::Monthly->label(); // 'Monthly'
```

### FeatureType

```php
use Salehye\Subscription\Enums\FeatureType;

FeatureType::Toggle;      // 'toggle'     — On/Off
FeatureType::Consumable;  // 'consumable' — Usage-based
FeatureType::Limit;       // 'limit'      — Max count

FeatureType::Toggle->label(); // 'Toggle (On/Off)'
```

### SubscriptionStatus

```php
use Salehye\Subscription\Enums\SubscriptionStatus;

SubscriptionStatus::Active;     // 'active'
SubscriptionStatus::Canceled;   // 'canceled'
SubscriptionStatus::Expired;    // 'expired'
SubscriptionStatus::Suspended;  // 'suspended'
SubscriptionStatus::Paused;     // 'paused'
SubscriptionStatus::Pending;    // 'pending'

SubscriptionStatus::Active->isActive();     // true
SubscriptionStatus::Canceled->isActive();   // false
SubscriptionStatus::Expired->isTerminal();  // true
```

---

## 2. Models

### Plan

```php
$plan->features;      // BelongsToMany
$plan->subscriptions; // HasMany

$plan->getBillingCycleEnum();
$plan->getFeatureValue('max_users');
```

**Scopes:** `scopeActive()`, `scopeByTenant()`, `scopeOrdered()`

### Feature

```php
$feature->plans;  // BelongsToMany
$feature->getTypeEnum();
```

**Scopes:** `scopeByType()`

### Subscription

```php
$subscription->subscriber();         // MorphTo
$subscription->plan();               // BelongsTo
$subscription->parentSubscription(); // BelongsTo
$subscription->addons();             // HasMany
$subscription->usage();              // HasMany

$subscription->isActive();
$subscription->isOnTrial();
$subscription->isOnGracePeriod();
$subscription->isCanceled();
$subscription->isExpired();
$subscription->isPrimary();
$subscription->isAddon();
$subscription->getStatusEnum();
$subscription->remainingDays();
```

**Scopes:** `scopeActive()`, `scopePrimary()`, `scopeAddon()`, `scopeByTenant()`, `scopeExpiringSoon($days)`

### SubscriptionUsage

```php
$usage->subscription(); // BelongsTo
$usage->feature();      // BelongsTo
```

**Scopes:** `scopeCurrentPeriod()`, `scopeForFeature()`

---

## 3. Contracts

### SubscriptionManager

```php
interface SubscriptionManager {
    public function subscribe($subscriber, Plan $plan, ...): Subscription;
    public function cancel(Subscription $subscription, bool $immediately = false): Subscription;
    public function renew(Subscription $subscription): Subscription;
    public function switchPlan(Subscription $subscription, Plan $newPlan, bool $prorate = true): Subscription;
    public function attachAddon(Subscription $parentSubscription, Plan $addonPlan, ...): Subscription;
    public function pause(Subscription $subscription): Subscription;
    public function resume(Subscription $subscription): Subscription;
    public function getActiveSubscription(HasSubscriptions $subscriber): ?Subscription;
    public function hasActiveSubscription(HasSubscriptions $subscriber): bool;
}
```

### HasSubscriptions

```php
interface HasSubscriptions {
    public function subscriptions(): MorphMany;
    public function getTenantId(): ?string;
}
```

### FeatureResolver

```php
interface FeatureResolver {
    public function resolve(Subscription $subscription, Feature $feature): string;
    public function canAccess(Subscription $subscription, Feature $feature): bool;
    public function getLimit(Subscription $subscription, Feature $feature): ?int;
    public function isUnlimited(Subscription $subscription, Feature $feature): bool;
}
```

### PlanRepository

```php
interface PlanRepository {
    public function findById(int $id): ?Plan;
    public function findBySlug(string $slug): ?Plan;
    public function getAll(): Collection;
    public function getActive(): Collection;
    public function getFeatures(Plan $plan): Collection;
    public function create(array $data): Plan;
    public function update(Plan $plan, array $data): Plan;
    public function delete(Plan $plan): bool;
}
```

---

## 4. Services

### SubscriptionManagerImpl

**subscribe($subscriber, $plan, ...)**

1. Validates plan is active
2. Calculates `trial_ends_at`
3. Calculates `ends_at` based on billing cycle
4. Creates `primary` subscription
5. Dispatches `SubscriptionStarted`

**cancel($subscription, $immediately = false)**

1. Sets status to `canceled`
2. Records `canceled_at`
3. If `$immediately = true` → sets `ends_at` to now
4. Cancels all active add-ons
5. Dispatches `SubscriptionCancelled`

**renew($subscription)**

1. Checks `auto_renew = true`
2. Recalculates `ends_at`
3. Resets usage
4. Dispatches `SubscriptionStarted`

**switchPlan($subscription, $newPlan, $prorate)**

1. Validates new plan
2. Changes plan and updates `ends_at`
3. Resets usage
4. Dispatches `PlanChanged`

**attachAddon($parentSubscription, $addonPlan, ...)**

- Creates an `addon` subscription linked to parent

**pause($subscription)** → `paused`

**resume($subscription)** → `active`

### FeatureGuard

```php
$guard = app(FeatureGuard::class);

$guard->can($subscription, 'api_access');           // Toggle check
$guard->consume($subscription, 'monthly_emails', 10); // Consume units
$guard->limit($subscription, 'max_users');          // Max limit
$guard->remaining($subscription, 'monthly_emails'); // Remaining units
$guard->usage($subscription, 'monthly_emails');     // Used units
```

### UsageTracker

```php
$tracker->track($subscription, $feature, $units);
$tracker->getUsage($subscription, $feature);
$tracker->reset($subscription);
$tracker->clearCache();
```

### PricingCalculator

```php
$calculator->calculateProratedAmount($subscription, $newPlan);
$calculator->calculateTotal($plan, $trialDays);
$calculator->dailyRate($plan);
```

---

## 5. Feature Resolvers

### DefaultFeatureResolver

1. Gets value from plan
2. If `unlimited` → returns immediately
3. Aggregates add-on values
4. Any add-on with `unlimited` makes total unlimited

### HierarchicalFeatureResolver

Extends default + tenant overrides:

```php
$resolver->setTenantOverrides('tenant_1', [
    'max_users' => '999',
    'api_access' => 'true',
]);
```

---

## 6. HasSubscriptions Trait

```php
use Salehye\Subscription\Traits\HasSubscriptions;

class User extends Authenticatable implements HasSubscriptionsContract
{
    use HasSubscriptions;
}
```

| Method                                    | Description                      |
| ----------------------------------------- | -------------------------------- |
| `$user->subscriptions()`                  | All subscriptions (MorphMany)    |
| `$user->activeSubscription()`             | Active primary subscription      |
| `$user->hasActiveSubscription()`          | Check if has active subscription |
| `$user->subscribeTo($plan, ...)`          | Subscribe to a plan              |
| `$user->canAccessFeature($slug)`          | Check feature access             |
| `$user->consumeFeature($slug, $units)`    | Consume feature units            |
| `$user->remainingFeature($slug)`          | Remaining units                  |
| `$user->cancelSubscription($immediately)` | Cancel subscription              |
| `$user->getTenantId()`                    | Get tenant ID                    |

---

## 7. Events

| Event                   | Fired When                   | Properties                                             |
| ----------------------- | ---------------------------- | ------------------------------------------------------ |
| `SubscriptionStarted`   | Subscription created/renewed | `$subscription`                                        |
| `SubscriptionRenewed`   | Subscription renewed         | `$subscription`                                        |
| `SubscriptionCancelled` | Subscription canceled        | `$subscription`, `$immediately`                        |
| `PlanChanged`           | Plan switched                | `$subscription`, `$oldPlan`, `$newPlan`, `$prorated`   |
| `FeatureConsumed`       | Feature consumed             | `$subscription`, `$feature`, `$units`, `$totalUsed`    |
| `FeatureLimitReached`   | Feature limit reached        | `$subscription`, `$feature`, `$limit`, `$currentUsage` |
| `SubscriptionExpired`   | Subscription expired         | `$subscription`                                        |

## 8. Middleware

```php
// 403 if feature not available
Route::middleware('subscription.feature:api_access')->group(function () { ... });

// Redirect if feature not available
Route::middleware('subscription.feature:advanced_reports,redirect')->group(function () { ... });
```

## 9. Artisan Commands

```bash
php artisan subscription:expire              # Expire subscriptions
php artisan subscription:expire --dry-run    # Dry run
php artisan subscription:reset-usage         # Reset usage
php artisan subscription:invoices:generate   # Generate invoices
```

## 10. Facade

```php
\Subscription::subscribe($user, $plan);
\Subscription::cancel($subscription);
\Subscription::renew($subscription);
\Subscription::switchPlan($subscription, $newPlan);
// etc.
```

## 11. Helpers

```php
subscription();   // app(SubscriptionManager::class)
featureGuard();   // app(FeatureGuard::class)
```

## 12. Seeders

### PlanSeeder

| Plan                  | Cycle            | Price   | Trial |
| --------------------- | ---------------- | ------- | ----- |
| 🆓 Free               | Lifetime         | $0.00   | 0     |
| 🔵 Basic              | Monthly          | $9.99   | 7     |
| 🟣 Pro                | Monthly          | $29.99  | 7     |
| 🏢 Enterprise         | Yearly           | $299.99 | 14    |
| 💾 Extra Storage 10GB | Monthly (Add-on) | $4.99   | 0     |
| ⭐ Premium Support    | Monthly (Add-on) | $19.99  | 0     |

### FeatureSeeder

| Feature            | Type       | Free  | Basic   | Pro     | Enterprise |
| ------------------ | ---------- | ----- | ------- | ------- | ---------- |
| `max_users`        | limit      | 3     | 10      | 50      | unlimited  |
| `max_storage`      | consumable | 100MB | 1,000MB | 5,000MB | unlimited  |
| `api_access`       | toggle     | ❌    | ✅      | ✅      | ✅         |
| `advanced_reports` | toggle     | ❌    | ❌      | ✅      | ✅         |
| `priority_support` | toggle     | ❌    | ❌      | ❌      | ✅         |
| `max_projects`     | limit      | 1     | 5       | 20      | unlimited  |
| `monthly_emails`   | consumable | 100   | 5,000   | 50,000  | unlimited  |

---
