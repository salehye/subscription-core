# Database Schema

## 🗄 Table Structure

### `plans` Table

| Column          | Type                    | Description                     |
| --------------- | ----------------------- | ------------------------------- |
| `id`            | bigint (PK)             | ID                              |
| `name`          | string                  | Plan name                       |
| `slug`          | string (unique)         | Unique slug                     |
| `description`   | text (nullable)         | Description                     |
| `billing_cycle` | string                  | `monthly`, `yearly`, `lifetime` |
| `price`         | decimal(13,2)           | Price                           |
| `trial_days`    | integer                 | Trial days                      |
| `is_active`     | boolean                 | Is plan active                  |
| `tenant_id`     | string (nullable)       | Tenant ID                       |
| `sort_order`    | integer                 | Display order                   |
| `metadata`      | json (nullable)         | Extra data                      |
| `created_at`    | timestamp               | Created at                      |
| `updated_at`    | timestamp               | Updated at                      |
| `deleted_at`    | timestamp (soft delete) | Deleted at                      |

### `features` Table

| Column        | Type                    | Description                     |
| ------------- | ----------------------- | ------------------------------- |
| `id`          | bigint (PK)             | ID                              |
| `name`        | string                  | Feature name                    |
| `slug`        | string (unique)         | Unique slug                     |
| `type`        | string                  | `toggle`, `consumable`, `limit` |
| `description` | text (nullable)         | Description                     |
| `metadata`    | json (nullable)         | Extra data                      |
| `created_at`  | timestamp               | Created at                      |
| `updated_at`  | timestamp               | Updated at                      |
| `deleted_at`  | timestamp (soft delete) | Deleted at                      |

### `plan_feature` (Pivot) Table

| Column       | Type        | Description                 |
| ------------ | ----------- | --------------------------- |
| `plan_id`    | bigint (FK) | Plan ID                     |
| `feature_id` | bigint (FK) | Feature ID                  |
| `value`      | string      | Feature value for this plan |
| `created_at` | timestamp   | Created at                  |
| `updated_at` | timestamp   | Updated at                  |

> **Note:** `value` can be `true`/`false` for Toggle, a number for Consumable/Limit, or `unlimited`.

### `subscriptions` Table

| Column                   | Type                  | Description                                                       |
| ------------------------ | --------------------- | ----------------------------------------------------------------- |
| `id`                     | bigint (PK)           | ID                                                                |
| `subscriber_type`        | string                | Morph type                                                        |
| `subscriber_id`          | string                | Morph ID                                                          |
| `tenant_id`              | string (nullable)     | Tenant ID                                                         |
| `plan_id`                | bigint (FK)           | Plan ID                                                           |
| `type`                   | string                | `primary` or `addon`                                              |
| `parent_subscription_id` | bigint (FK, nullable) | Parent subscription                                               |
| `starts_at`              | timestamp             | Start date                                                        |
| `ends_at`                | timestamp (nullable)  | End date                                                          |
| `trial_ends_at`          | timestamp (nullable)  | Trial end date                                                    |
| `status`                 | string                | `active`, `canceled`, `expired`, `suspended`, `paused`, `pending` |
| `canceled_at`            | timestamp (nullable)  | Canceled at                                                       |
| `auto_renew`             | boolean               | Auto renew                                                        |
| `metadata`               | json (nullable)       | Extra data                                                        |
| `created_at`             | timestamp             | Created at                                                        |
| `updated_at`             | timestamp             | Updated at                                                        |

**Indexes:**

- `[subscriber_type, subscriber_id, status]` — Quick subscriber lookup
- `[ends_at]` — Find expired subscriptions

### `subscription_usage` Table

| Column            | Type                 | Description     |
| ----------------- | -------------------- | --------------- |
| `id`              | bigint (PK)          | ID              |
| `subscription_id` | bigint (FK)          | Subscription ID |
| `feature_id`      | bigint (FK)          | Feature ID      |
| `used`            | integer              | Amount used     |
| `period_start`    | timestamp            | Period start    |
| `period_end`      | timestamp (nullable) | Period end      |
| `created_at`      | timestamp            | Created at      |
| `updated_at`      | timestamp            | Updated at      |

---

## 🔗 Relationships

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
