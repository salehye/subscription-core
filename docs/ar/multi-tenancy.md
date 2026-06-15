<div dir="rtl">

# تعدد المستأجرين (Multi-Tenancy)

## 1. فعّل في الإعدادات

```php
// config/subscription.php
'multi_tenancy' => [
    'enabled' => true,
    'tenant_column' => 'tenant_id',
    'tenant_resolver' => App\Resolvers\TenantResolver::class,
],
```

## 2. أنشئ Tenant Resolver

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

## 3. جهّز الموديل

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

## 4. استخدم المحلل الهرمي (اختياري)

```php
// config/subscription.php
'feature_resolver' => Salehye\Subscription\Resolvers\HierarchicalFeatureResolver::class,

// في الكود
$resolver = app(FeatureResolver::class);
$resolver->setTenantOverrides('tenant_abc', [
    'max_users' => '999',
    'max_storage' => 'unlimited',
]);
```

## ملاحظات مهمة

- عمود `tenant_id` موجود في جداول `plans` و `subscriptions`
- التريت `HasSubscriptions` يتضمن دالة `getTenantId()` اختيارية
- إذا لم تكن بحاجة لتعدد المستأجرين، اترك `enabled: false`

---

</div>
