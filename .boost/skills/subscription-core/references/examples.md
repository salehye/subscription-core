# Subscription Core — Examples Reference

## Complete Plan Setup

```php
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Models\Feature;

// Create plans
$basic = Plan::create([
    'name' => 'Basic',
    'slug' => 'basic',
    'description' => 'Basic plan for starters',
    'billing_cycle' => 'monthly',
    'price' => 9.99,
    'trial_days' => 7,
    'is_active' => true,
    'sort_order' => 1,
]);

$pro = Plan::create([
    'name' => 'Pro',
    'slug' => 'pro',
    'billing_cycle' => 'yearly',
    'price' => 99.99,
    'trial_days' => 14,
    'is_active' => true,
    'sort_order' => 2,
]);

// Create features
$apiAccess = Feature::create(['name' => 'API Access', 'slug' => 'api_access', 'type' => 'toggle']);
$monthlyEmails = Feature::create(['name' => 'Monthly Emails', 'slug' => 'monthly_emails', 'type' => 'consumable']);
$maxUsers = Feature::create(['name' => 'Max Users', 'slug' => 'max_users', 'type' => 'limit']);

// Attach features with values
$basic->features()->attach([
    $apiAccess->id => ['value' => 'false'],
    $monthlyEmails->id => ['value' => '1000'],
    $maxUsers->id => ['value' => '5'],
]);

$pro->features()->attach([
    $apiAccess->id => ['value' => 'true'],
    $monthlyEmails->id => ['value' => 'unlimited'],
    $maxUsers->id => ['value' => 'unlimited'],
]);
```

## Multi-Tenant SaaS Example

```php
// config/subscription.php
'multi_tenancy' => [
    'enabled' => true,
    'tenant_column' => 'tenant_id',
],

// Team model
class Team extends Authenticatable
{
    use HasSubscriptions;

    public function getTenantId(): ?string
    {
        return (string) $this->id;
    }
}

// Subscribe team
$team = Team::find(1);
$subscription = $team->subscribeTo($proPlan, trialDays: 30);

// Check feature within tenant context
if ($team->canAccessFeature('api_access')) {
    // Tenant has API access
}
```

## Subscription with Add-ons

```php
// Main subscription
$mainSubscription = $user->subscribeTo($basicPlan);

// Attach add-on
$addonPlan = Plan::where('slug', 'extra-storage')->first();
\Subscription::attachAddon($mainSubscription, $addonPlan);

// Add-on features auto-aggregate with primary
$user->canAccessFeature('extra_storage'); // Checks primary + addons
```

## Handling Events

```php
// In EventServiceProvider
use Salehye\Subscription\Events\SubscriptionStarted;
use Salehye\Subscription\Events\SubscriptionCancelled;
use Salehye\Subscription\Events\FeatureLimitReached;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SubscriptionStarted::class => [
            SendWelcomeEmail::class,
        ],
        SubscriptionCancelled::class => [
            CancelExternalServices::class,
        ],
        FeatureLimitReached::class => [
            NotifyUserAboutLimit::class,
        ],
    ];
}
```

## Using Middleware with Parameters

```php
// web.php
Route::middleware('subscription.feature:api_access')->group(function () {
    Route::get('/api/keys', [ApiKeyController::class, 'index']);
    Route::post('/api/keys', [ApiKeyController::class, 'store']);
});

// Redirect to upgrade page instead of 403
Route::middleware('subscription.feature:advanced_reports,redirect')->group(function () {
    Route::get('/reports/advanced', [ReportController::class, 'advanced']);
});
```

## Command Usage in Production

```bash
# Daily cron for expiration
* * * * * cd /project && php artisan subscription:expire >> /dev/null 2>&1

# Daily reset of usage
0 0 * * * cd /project && php artisan subscription:reset-usage >> /dev/null 2>&1

# Monthly invoice generation
0 0 1 * * cd /project && php artisan subscription:invoices:generate >> /dev/null 2>&1
```

## Custom Feature Resolver

```php
namespace App\Resolvers;

use Salehye\Subscription\Contracts\FeatureResolver;
use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Models\Subscription;

class CustomFeatureResolver implements FeatureResolver
{
    public function resolve(Subscription $subscription, Feature $feature): string
    {
        // Check subscription metadata first
        if ($overrides = $subscription->metadata['features'][$feature->slug] ?? null) {
            return $overrides;
        }

        // Fall back to plan pivot
        $pivotValue = $subscription->plan
            ->features()
            ->where('features.id', $feature->id)
            ->first()?->pivot->value;

        return $pivotValue ?? 'false';
    }

    public function canAccess(Subscription $subscription, Feature $feature): bool
    {
        $value = $this->resolve($subscription, $feature);
        return $value === 'true' || $value === 'unlimited';
    }

    public function getLimit(Subscription $subscription, Feature $feature): ?int
    {
        $value = $this->resolve($subscription, $feature);
        return $value === 'unlimited' ? null : (int) $value;
    }
}
```

## Testing with Package

```php
// tests/Feature/SubscriptionTest.php
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Tests\TestCase;

class SubscriptionTest extends TestCase
{
    /** @test */
    public function user_can_subscribe_to_plan()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['is_active' => true]);

        $subscription = $user->subscribeTo($plan);

        $this->assertTrue($user->hasActiveSubscription());
        $this->assertEquals($plan->id, $subscription->plan_id);
    }

    /** @test */
    public function feature_access_works_with_toggle()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $feature = Feature::factory()->create(['type' => 'toggle']);
        $plan->features()->attach($feature->id, ['value' => 'true']);

        $user->subscribeTo($plan);

        $this->assertTrue($user->canAccessFeature($feature->slug));
    }
}
```
