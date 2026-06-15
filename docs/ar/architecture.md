<div dir="rtl">

# الهيكلة والمعمارية

## 🏗 الهيكلة العامة للمجلدات

```
src/
├── Commands/
│   ├── CreateRecurringInvoices.php    # توليد الفواتير الدورية
│   ├── ExpireSubscriptions.php         # إنهاء الاشتراكات المنتهية
│   └── ResetUsage.php                  # إعادة تعيين الاستخدام
├── Contracts/
│   ├── FeatureResolver.php             # واجهة محلل الميزات
│   ├── HasSubscriptions.php            # واجهة الموديل القابل للاشتراك
│   ├── PlanRepository.php              # واجهة مستودع الخطط
│   ├── SubscriptionManager.php         # واجهة مدير الاشتراكات
│   └── TenantResolver.php              # واجهة حل المستأجر
├── Enums/
│   ├── BillingCycle.php                # دورة الفوترة
│   ├── FeatureType.php                 # نوع الميزة
│   └── SubscriptionStatus.php          # حالة الاشتراك
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

## 🧱 النمط المعماري (Design Pattern)

الحزمة تتبع نمط **العقود (Contracts/Interfaces)** مع **حقن التبعية (Dependency Injection)**:

```
Contracts (واجهات)
    ↑
    ├── Repositories ← Models (Eloquent)
    ├── Services ← Services Implementation
    └── Resolvers ← Resolver Implementation
```

### تدفق البيانات

```
1. Controller / Artisan Command
       ↓
2. Facade / Helper / Trait
       ↓
3. SubscriptionManager (واجهة)
       ↓
4. SubscriptionManagerImpl (تنفيذ)
       ↓
5. Repositories ← Models ← Database
       ↓
6. Events → Listeners (إشعارات)
```

---

## 🔌 Service Provider

`SubscriptionServiceProvider` يقوم بالتالي:

- **register()**: يربط جميع العقود (Contracts) بالتنفيذات (Implementations)
- **boot()**:
  - ينشر ملف الإعدادات والهجرات
  - يسجل alias للميدلوير
  - يسجل الأوامر
  - يسجل الأحداث والمستمعين
  - يسجل Facade

---

</div>
