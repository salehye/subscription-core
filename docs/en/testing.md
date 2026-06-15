# Testing

## Running Tests

```bash
composer test
# or:
vendor/bin/phpunit
```

## Test Structure

```
tests/
├── TestCase.php                    # Base setup (SQLite in-memory)
├── TestUser.php                    # Test user model
├── Unit/
│   ├── PlanTest.php               # 6 plan tests
│   └── FeatureTest.php            # 5 feature tests
└── Feature/
    ├── SubscriptionTest.php       # 15 subscription tests
    ├── FeatureGuardTest.php       # 9 feature guard tests
    └── CommandTest.php            # 4 command tests
```

**Total: 40 tests — 75 assertions ✅**

## Test Coverage

### Unit Tests

| Test          | Description                                                                           |
| ------------- | ------------------------------------------------------------------------------------- |
| `PlanTest`    | Create plan, BillingCycle, days, Lifetime, attach features, Active scope, Soft Delete |
| `FeatureTest` | Toggle, Consumable, Limit, attach to plan, Soft Delete                                |

### Feature Tests

| Test               | Description                                                                                          |
| ------------------ | ---------------------------------------------------------------------------------------------------- |
| `SubscriptionTest` | Subscribe, dates, Trial, inactive plan, cancel, cancel immediate, renew, switch plan, Add-on, events |
| `FeatureGuardTest` | Toggle, disabled feature, Consume, limit exceeded, remaining, unlimited, events, usage reset         |
| `CommandTest`      | Expire, Dry-run, no expired, generate invoices                                                       |

## Writing Additional Tests

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
