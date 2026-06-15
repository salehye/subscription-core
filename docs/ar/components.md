<div dir="rtl">

# المكونات الأساسية بالتفصيل

## 1. الأنواع المعدودة (Enums)

### BillingCycle — دورة الفوترة

```php
use Salehye\Subscription\Enums\BillingCycle;

BillingCycle::Monthly;   // 'monthly'  → 30 يومًا
BillingCycle::Yearly;    // 'yearly'   → 365 يومًا
BillingCycle::Lifetime;  // 'lifetime' → 36500 يومًا

$days = BillingCycle::Monthly->days(); // 30
$label = BillingCycle::Monthly->label(); // 'Monthly'
```

### FeatureType — نوع الميزة

```php
use Salehye\Subscription\Enums\FeatureType;

FeatureType::Toggle;      // 'toggle'     — تشغيل/إيقاف
FeatureType::Consumable;  // 'consumable' — قابلة للاستهلاك
FeatureType::Limit;       // 'limit'      — حد أقصى

FeatureType::Toggle->label(); // 'Toggle (On/Off)'
```

### SubscriptionStatus — حالة الاشتراك

```php
use Salehye\Subscription\Enums\SubscriptionStatus;

SubscriptionStatus::Active;     // 'active'    — نشط
SubscriptionStatus::Canceled;   // 'canceled'  — ملغي
SubscriptionStatus::Expired;    // 'expired'   — منتهي
SubscriptionStatus::Suspended;  // 'suspended' — معلّق
SubscriptionStatus::Paused;     // 'paused'    — موقّت مؤقتًا
SubscriptionStatus::Pending;    // 'pending'   — قيد الانتظار

SubscriptionStatus::Active->isActive();     // true
SubscriptionStatus::Canceled->isActive();   // false
SubscriptionStatus::Expired->isTerminal();  // true (حالة نهائية)
```

---

## 2. النماذج (Models)

### Plan

```php
$plan->features;      // BelongsToMany — الميزات
$plan->subscriptions; // HasMany — الاشتراكات

$plan->getBillingCycleEnum();          // BillingCycle enum
$plan->getFeatureValue('max_users');   // قيمة ميزة محددة
```

**Scopes:** `scopeActive()`, `scopeByTenant()`, `scopeOrdered()`

### Feature

```php
$feature->plans;  // BelongsToMany — الخطط
$feature->getTypeEnum(); // FeatureType enum
```

**Scopes:** `scopeByType()`

### Subscription

```php
$subscription->subscriber();         // MorphTo
$subscription->plan();               // BelongsTo
$subscription->parentSubscription(); // BelongsTo (الأب)
$subscription->addons();             // HasMany (الإضافات)
$subscription->usage();              // HasMany (الاستخدام)

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

## 3. العقود (Contracts)

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

### HasSubscriptions (واجهة الموديل)

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

## 4. المستودعات (Repositories)

### EloquentPlanRepository

عمليات CRUD على الخطط وجلب الميزات.

### EloquentSubscriptionRepository

- `findById($id)` — البحث مع تحميل العلاقات
- `findActiveForSubscriber($subscriber)` — الاشتراك النشط
- `getAllForSubscriber($subscriber)` — جميع الاشتراكات
- `findExpired()` — المنتهية
- `findExpiringSoon($days)` — ستنتهي قريبًا
- `getUsageForPeriod($subscription, $featureSlug)` — الاستخدام في الدورة
- `createOrUpdateUsage(...)` — إنشاء/تحديث الاستخدام
- `resetUsage($subscription)` — إعادة تعيين

---

## 5. الخدمات (Services)

### SubscriptionManagerImpl

**subscribe($subscriber, $plan, ...)**

1. يتحقق من أن الخطة نشطة
2. يحسب `trial_ends_at` إذا وجدت أيام تجريبية
3. يحسب `ends_at` بناءً على دورة الفوترة
4. ينشئ اشتراكًا من نوع `primary`
5. يطلق `SubscriptionStarted`

**cancel($subscription, $immediately = false)**

1. يغير الحالة إلى `canceled`
2. يسجل `canceled_at`
3. إذا `$immediately = true` ← `ends_at` = now
4. يلغي جميع الـ Add-ons
5. يطلق `SubscriptionCancelled`

**renew($subscription)**

1. يتحقق من `auto_renew = true`
2. يحسب `ends_at` جديدًا
3. يعيد تعيين الاستخدام
4. يطلق `SubscriptionStarted`

**switchPlan($subscription, $newPlan, $prorate)**

1. يتحقق من الخطة الجديدة
2. يغير الخطة ويحدّث `ends_at`
3. يعيد تعيين الاستخدام
4. يطلق `PlanChanged`

**attachAddon($parentSubscription, $addonPlan, ...)**

1. يتحقق من خطة الإضافة
2. يتحقق من أن الأب من نوع `primary`
3. ينشئ اشتراك `addon`
4. يطلق `SubscriptionStarted`

**pause($subscription)** ← `paused`

**resume($subscription)** ← `active`

### FeatureGuard

```php
$guard = app(FeatureGuard::class);

$guard->can($subscription, 'api_access');          // Toggle
$guard->consume($subscription, 'monthly_emails', 10); // Consumable
$guard->limit($subscription, 'max_users');         // الحد الأقصى
$guard->remaining($subscription, 'monthly_emails');  // المتبقي
$guard->usage($subscription, 'monthly_emails');      // المستخدم
```

### UsageTracker

```php
$tracker->track($subscription, $feature, $units);   // تسجيل استخدام
$tracker->getUsage($subscription, $feature);         // قراءة (مع cache)
$tracker->reset($subscription);                       // إعادة تعيين
$tracker->clearCache();                               // مسح cache
```

### PricingCalculator

```php
$calculator->calculateProratedAmount($subscription, $newPlan); // المبلغ المتناسب
$calculator->calculateTotal($plan, $trialDays);                 // السعر الإجمالي
$calculator->dailyRate($plan);                                  // السعر اليومي
```

---

## 6. محلّلات الميزات (Feature Resolvers)

### DefaultFeatureResolver (الافتراضي)

1. يجلب قيمة الميزة من خطة الاشتراك الأساسي
2. إذا `unlimited` ← يعيدها فورًا
3. يجمع قيم الـ Add-ons النشطة
4. إذا أي Add-on يحمل `unlimited` ← المجموع غير محدود

### HierarchicalFeatureResolver (هرمي)

يمتد عن الافتراضي + تجاوزات المستأجر:

```php
$resolver->setTenantOverrides('tenant_1', [
    'max_users' => '999',
    'api_access' => 'true',
]);
```

---

## 7. التريت HasSubscriptions

```php
use Salehye\Subscription\Traits\HasSubscriptions;

class User extends Authenticatable implements HasSubscriptionsContract
{
    use HasSubscriptions;
}
```

الدوال المتاحة:

| الدالة                                    | الوصف                       |
| ----------------------------------------- | --------------------------- |
| `$user->subscriptions()`                  | جميع الاشتراكات (MorphMany) |
| `$user->activeSubscription()`             | الاشتراك الأساسي النشط      |
| `$user->hasActiveSubscription()`          | هل يوجد اشتراك نشط؟         |
| `$user->subscribeTo($plan, ...)`          | الاشتراك في خطة             |
| `$user->canAccessFeature($slug)`          | هل يمكن الوصول لميزة؟       |
| `$user->consumeFeature($slug, $units)`    | استهلاك وحدات               |
| `$user->remainingFeature($slug)`          | الوحدات المتبقية            |
| `$user->cancelSubscription($immediately)` | إلغاء الاشتراك              |
| `$user->getTenantId()`                    | معرف المستأجر               |

---

## 8. الأحداث (Events)

| الحدث                   | يُطلق عند       | البيانات                                               |
| ----------------------- | --------------- | ------------------------------------------------------ |
| `SubscriptionStarted`   | إنشاء/تجديد     | `$subscription`                                        |
| `SubscriptionRenewed`   | تجديد           | `$subscription`                                        |
| `SubscriptionCancelled` | إلغاء           | `$subscription`, `$immediately`                        |
| `PlanChanged`           | تبديل خطة       | `$subscription`, `$oldPlan`, `$newPlan`, `$prorated`   |
| `FeatureConsumed`       | استهلاك ميزة    | `$subscription`, `$feature`, `$units`, `$totalUsed`    |
| `FeatureLimitReached`   | وصول حد الميزة  | `$subscription`, `$feature`, `$limit`, `$currentUsage` |
| `SubscriptionExpired`   | انتهاء الاشتراك | `$subscription`                                        |

## 9. المستمعون (Listeners)

`SendSubscriptionNotification` — مستمع افتراضي (ShouldQueue) يسجل الأحداث في الـ Log.

## 10. الميدلوير (Middleware)

```php
// 403 إذا الميزة غير متاحة
Route::middleware('subscription.feature:api_access')->group(function () { ... });

// Redirect إذا الميزة غير متاحة
Route::middleware('subscription.feature:advanced_reports,redirect')->group(function () { ... });
```

## 11. أوامر Artisan

```bash
php artisan subscription:expire              # إنهاء المنتهية
php artisan subscription:expire --dry-run    # محاكاة
php artisan subscription:reset-usage         # إعادة تعيين الاستخدام
php artisan subscription:invoices:generate   # توليد الفواتير الدورية
```

## 12. الـ Facade

```php
\Subscription::subscribe($user, $plan);
\Subscription::cancel($subscription);
\Subscription::renew($subscription);
\Subscription::switchPlan($subscription, $newPlan);
// ... إلخ
```

## 13. الدوال المساعدة (Helpers)

```php
subscription();   // app(SubscriptionManager::class)
featureGuard();   // app(FeatureGuard::class)
```

## 14. البذور (Seeders)

### PlanSeeder

| الخطة                 | الدورة        | السعر   | التجربة |
| --------------------- | ------------- | ------- | ------- |
| 🆓 Free               | مدى الحياة    | $0.00   | 0       |
| 🔵 Basic              | شهري          | $9.99   | 7       |
| 🟣 Pro                | شهري          | $29.99  | 7       |
| 🏢 Enterprise         | سنوي          | $299.99 | 14      |
| 💾 Extra Storage 10GB | شهري (Add-on) | $4.99   | 0       |
| ⭐ Premium Support    | شهري (Add-on) | $19.99  | 0       |

### FeatureSeeder

| الميزة             | النوع      | Free  | Basic   | Pro     | Enterprise |
| ------------------ | ---------- | ----- | ------- | ------- | ---------- |
| `max_users`        | limit      | 3     | 10      | 50      | unlimited  |
| `max_storage`      | consumable | 100MB | 1,000MB | 5,000MB | unlimited  |
| `api_access`       | toggle     | ❌    | ✅      | ✅      | ✅         |
| `advanced_reports` | toggle     | ❌    | ❌      | ✅      | ✅         |
| `priority_support` | toggle     | ❌    | ❌      | ❌      | ✅         |
| `max_projects`     | limit      | 1     | 5       | 20      | unlimited  |
| `monthly_emails`   | consumable | 100   | 5,000   | 50,000  | unlimited  |

---

</div>
