<div dir="rtl">

# دليل الاستخدام

## 1. تجهيز الموديل

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Salehye\Subscription\Contracts\HasSubscriptions as HasSubscriptionsContract;
use Salehye\Subscription\Traits\HasSubscriptions;

class User extends Authenticatable implements HasSubscriptionsContract
{
    use HasSubscriptions;

    // (اختياري) لدعم تعدد المستأجرين
    public function getTenantId(): ?string
    {
        return $this->tenant_id;
    }
}
```

> **⚠️ مهم:** يجب أن يطبّق الموديل واجهة `HasSubscriptionsContract` **ويستخدم** التريت `HasSubscriptions`.

---

## 2. إنشاء الخطط والميزات

```php
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Models\Feature;

// إنشاء خطة
$plan = Plan::create([
    'name' => 'Pro Monthly',
    'slug' => 'pro-monthly',
    'billing_cycle' => 'monthly',
    'price' => 29.99,
    'trial_days' => 7,
    'is_active' => true,
    'sort_order' => 1,
]);

// إنشاء ميزات
$apiAccess = Feature::create([
    'name' => 'API Access',
    'slug' => 'api_access',
    'type' => 'toggle',
]);

$monthlyEmails = Feature::create([
    'name' => 'Monthly Emails',
    'slug' => 'monthly_emails',
    'type' => 'consumable',
]);

$maxUsers = Feature::create([
    'name' => 'Max Users',
    'slug' => 'max_users',
    'type' => 'limit',
]);

// ربط الميزات بالخطة
$plan->features()->attach([
    $apiAccess->id      => ['value' => 'true'],
    $monthlyEmails->id  => ['value' => '1000'],
    $maxUsers->id       => ['value' => '10'],
]);
```

---

## 3. الاشتراك

```php
$user = User::find(1);
$plan = Plan::where('slug', 'pro-monthly')->first();

// عبر Facade
$subscription = \Subscription::subscribe($user, $plan);

// عبر التريت (الأسهل)
$subscription = $user->subscribeTo($plan);

// مع خيارات إضافية
$subscription = $user->subscribeTo(
    plan: $plan,
    trialDays: 14,
    autoRenew: true,
    metadata: ['promo_code' => 'WELCOME2025'],
);
```

---

## 4. إدارة الاشتراك

```php
// الإلغاء في نهاية الدورة
$user->cancelSubscription();
// أو:
\Subscription::cancel($subscription);

// إلغاء فوري
$user->cancelSubscription(true);

// التجديد
\Subscription::renew($subscription);

// تبديل الخطة
\Subscription::switchPlan($subscription, $newPlan, prorate: true);

// إيقاف مؤقت
\Subscription::pause($subscription);

// استئناف
\Subscription::resume($subscription);
```

---

## 5. التحقق من الميزات

```php
if ($user->canAccessFeature('api_access')) {
    // المستخدم لديه صلاحية API
}

$remainingEmails = $user->remainingFeature('monthly_emails');

try {
    $user->consumeFeature('monthly_emails', 10);
} catch (\Salehye\Subscription\Exceptions\FeatureLimitExceededException $e) {
    // تجاوز الحد المسموح
}
```

---

## 6. استخدام FeatureGuard

```php
$guard = app(FeatureGuard::class);

$guard->can($subscription, 'api_access');          // Toggle
$guard->consume($subscription, 'monthly_emails', 5); // استهلاك
$guard->limit($subscription, 'max_users');         // الحد الأقصى
$guard->remaining($subscription, 'monthly_emails');  // المتبقي
$guard->usage($subscription, 'monthly_emails');      // المستخدم
```

---

## 7. الـ Add-ons (الإضافات)

```php
// إضافة خدمة للاشتراك الأساسي
$subscription = $user->activeSubscription();
$addon = \Subscription::attachAddon($subscription, $extraStoragePlan);

// الآن يتم جمع قيم الميزات تلقائيًا:
$guard->limit($subscription->fresh(), 'max_storage'); // storage الأصلي + الإضافي
```

---

## 8. الميدلوير

```php
// 403 إذا الميزة غير متاحة
Route::middleware('subscription.feature:api_access')->group(function () {
    Route::get('/api/export', [ExportController::class, 'index']);
});

// Redirect إذا الميزة غير متاحة
Route::middleware('subscription.feature:advanced_reports,redirect')->group(function () {
    Route::get('/reports/advanced', [ReportController::class, 'advanced']);
});
```

---

## 9. نظام الفترة التجريبية

```php
$plan = Plan::create([
    'name' => 'Premium',
    'billing_cycle' => 'monthly',
    'trial_days' => 7,
    'price' => 19.99,
    'is_active' => true,
]);

$subscription = $user->subscribeTo($plan);

if ($subscription->isOnTrial()) {
    // المستخدم لا يزال في الفترة التجريبية
}

// تجاوز أيام التجربة الافتراضية
$subscription = $user->subscribeTo($plan, trialDays: 30);
```

---

## 10. الجدولة التلقائية (Scheduler)

أضف إلى `routes/console.php`:

```php
Schedule::command('subscription:expire')->daily();
Schedule::command('subscription:reset-usage')->daily();
Schedule::command('subscription:invoices:generate')->daily();
```

---

</div>
