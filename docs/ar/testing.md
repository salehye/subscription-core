<div dir="rtl">

# الاختبارات

## تشغيل الاختبارات

```bash
composer test
# أو:
vendor/bin/phpunit
```

## هيكل الاختبارات

```
tests/
├── TestCase.php                    # الإعداد الأساسي (SQLite in-memory)
├── TestUser.php                    # موديل مستخدم للاختبار
├── Unit/
│   ├── PlanTest.php               # 6 اختبارات للخطط
│   └── FeatureTest.php            # 5 اختبارات للميزات
└── Feature/
    ├── SubscriptionTest.php       # 15 اختبارًا للاشتراكات
    ├── FeatureGuardTest.php       # 9 اختبارات لحارس الميزات
    └── CommandTest.php            # 4 اختبارات للأوامر البرمجية
```

**إجمالي: 40 اختبارًا — 75 تأكيدًا ✅**

## تغطية الاختبارات

### Unit Tests

| الاختبار      | الوصف                                                                                 |
| ------------- | ------------------------------------------------------------------------------------- |
| `PlanTest`    | إنشاء خطة、BillingCycle、عدد الأيام、Lifetime、ربط الميزات、Active Scope、Soft Delete |
| `FeatureTest` | Toggle、Consumable、Limit、ربط بالخطة、Soft Delete                                    |

### Feature Tests

| الاختبار           | الوصف                                                                                     |
| ------------------ | ----------------------------------------------------------------------------------------- |
| `SubscriptionTest` | اشتراك、تواريخ、Trial、خطط غير نشطة、إلغاء、إلغاء فوري、تجديد、تبديل خطة、Add-on、أحداث   |
| `FeatureGuardTest` | Toggle、ميزة معطلة、Consume、تجاوز الحد、المتبقي、غير محدود、أحداث、إعادة تعيين الاستخدام |
| `CommandTest`      | Expire、Dry-run、لا يوجد منتهية、توليد الفواتير                                           |

## كتابة اختبارات إضافية

```php
use Salehye\Subscription\Tests\TestCase;
use Salehye\Subscription\Models\Plan;

class CustomTest extends TestCase
{
    public function test_user_can_subscribe_to_plan(): void
    {
        $user = $this->createUser();
        $plan = Plan::factory()->create(['is_active' => true]);

        $subscription = $user->subscribeTo($plan);

        $this->assertTrue($subscription->isActive());
        $this->assertEquals($plan->id, $subscription->plan_id);
    }
}
```

---

</div>
