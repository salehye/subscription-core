<div dir="rtl">

# قاعدة البيانات

## 🗄 هيكل الجداول

### جدول `plans`

| العمود          | النوع                   | الوصف                           |
| --------------- | ----------------------- | ------------------------------- |
| `id`            | bigint (PK)             | المعرف                          |
| `name`          | string                  | اسم الخطة                       |
| `slug`          | string (unique)         | المعرف النصي الفريد             |
| `description`   | text (nullable)         | الوصف                           |
| `billing_cycle` | string                  | `monthly`、`yearly`、`lifetime` |
| `price`         | decimal(13,2)           | السعر                           |
| `trial_days`    | integer                 | أيام الفترة التجريبية           |
| `is_active`     | boolean                 | هل الخطة مفعّلة                 |
| `tenant_id`     | string (nullable)       | معرف المستأجر (للتعددية)        |
| `sort_order`    | integer                 | ترتيب العرض                     |
| `metadata`      | json (nullable)         | بيانات إضافية                   |
| `created_at`    | timestamp               | تاريخ الإنشاء                   |
| `updated_at`    | timestamp               | تاريخ التحديث                   |
| `deleted_at`    | timestamp (soft delete) | تاريخ الحذف الناعم              |

### جدول `features`

| العمود        | النوع                   | الوصف                           |
| ------------- | ----------------------- | ------------------------------- |
| `id`          | bigint (PK)             | المعرف                          |
| `name`        | string                  | اسم الميزة                      |
| `slug`        | string (unique)         | المعرف النصي الفريد             |
| `type`        | string                  | `toggle`、`consumable`、`limit` |
| `description` | text (nullable)         | الوصف                           |
| `metadata`    | json (nullable)         | بيانات إضافية                   |
| `created_at`  | timestamp               | تاريخ الإنشاء                   |
| `updated_at`  | timestamp               | تاريخ التحديث                   |
| `deleted_at`  | timestamp (soft delete) | تاريخ الحذف الناعم              |

### جدول `plan_feature` (Pivot)

| العمود       | النوع       | الوصف                  |
| ------------ | ----------- | ---------------------- |
| `plan_id`    | bigint (FK) | معرف الخطة             |
| `feature_id` | bigint (FK) | معرف الميزة            |
| `value`      | string      | قيمة الميزة لهذه الخطة |
| `created_at` | timestamp   | تاريخ الإنشاء          |
| `updated_at` | timestamp   | تاريخ التحديث          |

> **ملاحظة:** `value` يمكن أن تكون `true`/`false` للميزات من نوع Toggle、رقم للميزات من نوع Consumable/Limit、أو `unlimited` للدلالة على عدم وجود حد.

### جدول `subscriptions`

| العمود                   | النوع                 | الوصف                                                             |
| ------------------------ | --------------------- | ----------------------------------------------------------------- |
| `id`                     | bigint (PK)           | المعرف                                                            |
| `subscriber_type`        | string                | نوع الموديل المشترك (Morph)                                       |
| `subscriber_id`          | string                | معرف الموديل المشترك (Morph)                                      |
| `tenant_id`              | string (nullable)     | معرف المستأجر                                                     |
| `plan_id`                | bigint (FK)           | معرف الخطة                                                        |
| `type`                   | string                | `primary` أو `addon`                                              |
| `parent_subscription_id` | bigint (FK, nullable) | الاشتراك الأب (للإضافات)                                          |
| `starts_at`              | timestamp             | تاريخ البداية                                                     |
| `ends_at`                | timestamp (nullable)  | تاريخ النهاية                                                     |
| `trial_ends_at`          | timestamp (nullable)  | تاريخ انتهاء الفترة التجريبية                                     |
| `status`                 | string                | `active`、`canceled`、`expired`、`suspended`、`paused`、`pending` |
| `canceled_at`            | timestamp (nullable)  | تاريخ الإلغاء                                                     |
| `auto_renew`             | boolean               | التجديد التلقائي                                                  |
| `metadata`               | json (nullable)       | بيانات إضافية                                                     |
| `created_at`             | timestamp             | تاريخ الإنشاء                                                     |
| `updated_at`             | timestamp             | تاريخ التحديث                                                     |

**الفهارس:**

- `[subscriber_type, subscriber_id, status]` — للبحث السريع عن اشتراكات المشترك
- `[ends_at]` — للبحث عن الاشتراكات المنتهية

### جدول `subscription_usage`

| العمود            | النوع                | الوصف            |
| ----------------- | -------------------- | ---------------- |
| `id`              | bigint (PK)          | المعرف           |
| `subscription_id` | bigint (FK)          | معرف الاشتراك    |
| `feature_id`      | bigint (FK)          | معرف الميزة      |
| `used`            | integer              | المقدار المستخدم |
| `period_start`    | timestamp            | بداية الدورة     |
| `period_end`      | timestamp (nullable) | نهاية الدورة     |
| `created_at`      | timestamp            | تاريخ الإنشاء    |
| `updated_at`      | timestamp            | تاريخ التحديث    |

---

## 🔗 العلاقات بين الجداول

```
plans ──── plan_feature ──── features
  │
  └──── subscriptions ──── subscription_usage
           │                       │
           └── (self) addons       └── features
```

- **plans** → **plan_feature** → **features** (Many-to-Many)
- **plans** → **subscriptions** (One-to-Many)
- **subscriptions** → **subscription_usage** (One-to-Many)
- **subscriptions** → **subscriptions** (Self-referential for addons)
- **subscription_usage** → **features** (Many-to-One)

---

</div>
