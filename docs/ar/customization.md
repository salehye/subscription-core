<div dir="rtl">

# التخصيص (Customization)

## استبدال محلل الميزات

```php
use Salehye\Subscription\Contracts\FeatureResolver;

class CustomFeatureResolver implements FeatureResolver
{
    public function resolve(Subscription $subscription, Feature $feature): string
    {
        // منطقك الخاص
    }

    public function canAccess(Subscription $subscription, Feature $feature): bool { /* ... */ }
    public function getLimit(Subscription $subscription, Feature $feature): ?int { /* ... */ }
    public function isUnlimited(Subscription $subscription, Feature $feature): bool { /* ... */ }
}
```

ثم في `config/subscription.php`:

```php
'feature_resolver' => App\Resolvers\CustomFeatureResolver::class,
```

## استبدال المستودعات

```php
// config/subscription.php
'plan_repository' => App\Repositories\CustomPlanRepository::class,
'subscription_repository' => App\Repositories\CustomSubscriptionRepository::class,
```

## تمديد الموديلات

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

ثم حدّث الإعدادات:

```php
'models' => [
    'plan' => App\Models\Plan::class,
    // ...
],
```

## إنشاء SubscriptionManager مخصص

```php
use Salehye\Subscription\Contracts\SubscriptionManager;

class CustomSubscriptionManager implements SubscriptionManager
{
    public function subscribe($subscriber, Plan $plan, ...): Subscription { /* ... */ }
    // ...
}
```

ثم في Service Provider:

```php
$this->app->singleton(SubscriptionManager::class, CustomSubscriptionManager::class);
```

---

</div>
