# Multi-Tenancy

## 1. Enable in Config

```php
// config/subscription.php
'multi_tenancy' => [
    'enabled' => true,
    'tenant_column' => 'tenant_id',
    'tenant_resolver' => App\Resolvers\TenantResolver::class,
],
```

## 2. Create a Tenant Resolver

```php
<?php

namespace App\Resolvers;

use Salehye\Subscription\Contracts\TenantResolver;

class TenantResolver implements TenantResolver
{
    public function resolve(): ?string
    {
        return auth()->user()?->tenant_id;
    }
}
```

## 3. Prepare Your Model

```php
class User extends Authenticatable implements HasSubscriptionsContract
{
    use HasSubscriptions;

    public function getTenantId(): ?string
    {
        return $this->tenant_id;
    }
}
```

## 4. Use Hierarchical Resolver (Optional)

```php
// config/subscription.php
'feature_resolver' => Salehye\Subscription\Resolvers\HierarchicalFeatureResolver::class,

// In code
$resolver = app(FeatureResolver::class);
$resolver->setTenantOverrides('tenant_abc', [
    'max_users' => '999',
    'max_storage' => 'unlimited',
]);
```

## Important Notes

- `tenant_id` column exists in both `plans` and `subscriptions` tables
- The `HasSubscriptions` trait includes an optional `getTenantId()` method
- If you don't need multi-tenancy, leave `enabled: false`

---
