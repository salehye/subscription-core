<div dir="rtl">

# التثبيت والإعدادات

## 💻 المتطلبات

| المتطلب      | الإصدار                     |
| ------------ | --------------------------- |
| PHP          | `^8.3`                      |
| Laravel      | `^11.0 \| ^12.0 \| ^13.0`   |
| قاعدة بيانات | MySQL / PostgreSQL / SQLite |

---

## 📦 التثبيت

### 1. تثبيت عبر Composer

```bash
composer require salehye/subscription-core
```

### 2. نشر الإعدادات والهجرات

```bash
# نشر ملف الإعدادات
php artisan vendor:publish --tag=subscription-config

# نشر ملفات الهجرة (migrations)
php artisan vendor:publish --tag=subscription-migrations

# تشغيل الهجرات
php artisan migrate
```

### 3. بداية سريعة مع البذور (Seeders)

```bash
php artisan db:seed --class="Salehye\Subscription\Database\Seeders\PlanSeeder"
php artisan db:seed --class="Salehye\Subscription\Database\Seeders\FeatureSeeder"
```

---

## ⚙️ ملف الإعدادات `config/subscription.php`

```php
<?php

return [
    // الموديلات الافتراضية — يمكنك تمديدها
    'models' => [
        'plan' => Salehye\Subscription\Models\Plan::class,
        'feature' => Salehye\Subscription\Models\Feature::class,
        'subscription' => Salehye\Subscription\Models\Subscription::class,
        'subscription_usage' => Salehye\Subscription\Models\SubscriptionUsage::class,
    ],

    // إعدادات الفوترة
    'billing' => [
        'monthly_days' => 30,
        'yearly_days' => 365,
        'lifetime_days' => 36500, // ~100 سنة
    ],

    // إعدادات الفترة التجريبية
    'trial' => [
        'enabled' => true,
        'default_days' => 7,
    ],

    // التخزين المؤقت للميزات
    'cache' => [
        'enabled' => env('SUBSCRIPTION_CACHE_ENABLED', true),
        'ttl' => env('SUBSCRIPTION_CACHE_TTL', 3600),
    ],

    // محلل الميزات
    'feature_resolver' => Salehye\Subscription\Resolvers\DefaultFeatureResolver::class,

    // تعدد المستأجرين
    'multi_tenancy' => [
        'enabled' => env('SUBSCRIPTION_MULTI_TENANCY', false),
        'tenant_column' => 'tenant_id',
        'tenant_resolver' => null,
    ],

    // قيمة "غير محدود"
    'unlimited_value' => 'unlimited',

    // الحالات النشطة للاشتراك
    'active_statuses' => ['active', 'paused'],

    // أيام السماح
    'grace_period_days' => 0,
];
```

### شرح الإعدادات

| الإعداد                 | الوصف                                        |
| ----------------------- | -------------------------------------------- |
| `models`                | الموديلات المستخدمة — يمكنك تمديدها          |
| `billing.monthly_days`  | عدد أيام الدورة الشهرية (افتراضي 30)         |
| `billing.yearly_days`   | عدد أيام الدورة السنوية (افتراضي 365)        |
| `billing.lifetime_days` | عدد أيام الاشتراك مدى الحياة (افتراضي 36500) |
| `trial.enabled`         | تفعيل الفترة التجريبية                       |
| `trial.default_days`    | عدد أيام الفترة التجريبية الافتراضية         |
| `cache.enabled`         | تفعيل التخزين المؤقت للميزات                 |
| `cache.ttl`             | مدة التخزين المؤقت بالثواني                  |
| `multi_tenancy.enabled` | تفعيل تعدد المستأجرين                        |
| `unlimited_value`       | القيمة التي تعني "غير محدود"                 |
| `active_statuses`       | الحالات التي تعتبر "نشطة"                    |
| `grace_period_days`     | أيام السماح بعد انتهاء الاشتراك              |

---

</div>
