# Usage Guide

## 1. Prepare Your Model

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Salehye\Subscription\Contracts\HasSubscriptions as HasSubscriptionsContract;
use Salehye\Subscription\Traits\HasSubscriptions;

class User extends Authenticatable implements HasSubscriptionsContract
{
    use HasSubscriptions;

    // (Optional) For multi-tenancy
    public function getTenantId(): ?string
    {
        return $this->tenant_id;
    }
}
```

> **⚠️ Important:** Your model must implement `HasSubscriptionsContract` **and** use the `HasSubscriptions` trait.

---

## 2. Create Plans & Features

```php
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Models\Feature;

// Create a plan
$plan = Plan::create([
    'name' => 'Pro Monthly',
    'slug' => 'pro-monthly',
    'billing_cycle' => 'monthly',
    'price' => 29.99,
    'trial_days' => 7,
    'is_active' => true,
    'sort_order' => 1,
]);

// Create features
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

// Attach features to plan
$plan->features()->attach([
    $apiAccess->id      => ['value' => 'true'],
    $monthlyEmails->id  => ['value' => '1000'],
    $maxUsers->id       => ['value' => '10'],
]);
```

---

## 3. Subscribe

```php
$user = User::find(1);
$plan = Plan::where('slug', 'pro-monthly')->first();

// Via Facade
$subscription = \Subscription::subscribe($user, $plan);

// Via Trait (easier)
$subscription = $user->subscribeTo($plan);

// With options
$subscription = $user->subscribeTo(
    plan: $plan,
    trialDays: 14,
    autoRenew: true,
    metadata: ['promo_code' => 'WELCOME2025'],
);
```

---

## 4. Manage Subscription

```php
// Cancel at period end
$user->cancelSubscription();
// or:
\Subscription::cancel($subscription);

// Cancel immediately
$user->cancelSubscription(true);

// Renew
\Subscription::renew($subscription);

// Switch plan
\Subscription::switchPlan($subscription, $newPlan, prorate: true);

// Pause / Resume
\Subscription::pause($subscription);
\Subscription::resume($subscription);
```

---

## 5. Feature Access

```php
if ($user->canAccessFeature('api_access')) {
    // User has API access
}

$remainingEmails = $user->remainingFeature('monthly_emails');

try {
    $user->consumeFeature('monthly_emails', 10);
} catch (\Salehye\Subscription\Exceptions\FeatureLimitExceededException $e) {
    // Limit exceeded
}
```

---

## 6. FeatureGuard

```php
$guard = app(FeatureGuard::class);

$guard->can($subscription, 'api_access');           // Toggle
$guard->consume($subscription, 'monthly_emails', 5); // Consume
$guard->limit($subscription, 'max_users');          // Max limit
$guard->remaining($subscription, 'monthly_emails');  // Remaining
$guard->usage($subscription, 'monthly_emails');      // Used
```

---

## 7. Add-ons

```php
$subscription = $user->activeSubscription();
$addon = \Subscription::attachAddon($subscription, $extraStoragePlan);

// Feature values are aggregated automatically
$guard->limit($subscription->fresh(), 'max_storage'); // Original + addon storage
```

---

## 8. Middleware

```php
// 403 if feature not available
Route::middleware('subscription.feature:api_access')->group(function () {
    Route::get('/api/export', [ExportController::class, 'index']);
});

// Redirect if feature not available
Route::middleware('subscription.feature:advanced_reports,redirect')->group(function () {
    Route::get('/reports/advanced', [ReportController::class, 'advanced']);
});
```

---

## 9. Trial Period

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
    // User is still in trial period
}

// Override trial days
$subscription = $user->subscribeTo($plan, trialDays: 30);
```

---

## 10. Scheduler

Add to `routes/console.php`:

```php
Schedule::command('subscription:expire')->daily();
Schedule::command('subscription:reset-usage')->daily();
Schedule::command('subscription:invoices:generate')->daily();
```

---
