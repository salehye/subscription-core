# Customization

## Replace Feature Resolver

```php
use Salehye\Subscription\Contracts\FeatureResolver;

class CustomFeatureResolver implements FeatureResolver
{
    public function resolve(Subscription $subscription, Feature $feature): string
    {
        // Your custom logic
    }

    public function canAccess(Subscription $subscription, Feature $feature): bool { /* ... */ }
    public function getLimit(Subscription $subscription, Feature $feature): ?int { /* ... */ }
    public function isUnlimited(Subscription $subscription, Feature $feature): bool { /* ... */ }
}
```

Then in `config/subscription.php`:

```php
'feature_resolver' => App\Resolvers\CustomFeatureResolver::class,
```

## Replace Repositories

```php
// config/subscription.php
'plan_repository' => App\Repositories\CustomPlanRepository::class,
'subscription_repository' => App\Repositories\CustomSubscriptionRepository::class,
```

## Extend Models

```php
namespace App\Models;

use Salehye\Subscription\Models\Plan as BasePlan;

class Plan extends BasePlan
{
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }
}
```

Then update config:

```php
'models' => [
    'plan' => App\Models\Plan::class,
    // ...
],
```

## Create Custom SubscriptionManager

```php
use Salehye\Subscription\Contracts\SubscriptionManager;

class CustomSubscriptionManager implements SubscriptionManager
{
    public function subscribe($subscriber, Plan $plan, ...): Subscription { /* ... */ }
    // ...
}
```

Then in Service Provider:

```php
$this->app->singleton(SubscriptionManager::class, CustomSubscriptionManager::class);
```

---
