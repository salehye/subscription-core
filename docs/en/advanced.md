# Advanced Scenarios

## Scenario 1: Register New User with Free Plan

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    $user = User::create([...]);

    $freePlan = Plan::where('slug', 'free')->first();
    $user->subscribeTo($freePlan);

    Mail::to($user)->send(new WelcomeMail($user));
});
```

## Scenario 2: Switch Plan with Accounting

```php
$subscription = $user->activeSubscription();
$newPlan = Plan::where('slug', 'enterprise')->first();

$calculator = app(PricingCalculator::class);
$proratedAmount = $calculator->calculateProratedAmount($subscription, $newPlan);

// Create invoice in your payment system
$invoice = $user->charge($proratedAmount);

\Subscription::switchPlan($subscription, $newPlan);
```

## Scenario 3: Feature Check in Controller

```php
class ReportController extends Controller
{
    public function advanced()
    {
        $user = auth()->user();

        if (!$user->canAccessFeature('advanced_reports')) {
            return redirect()->route('reports.basic')
                ->with('error', 'Upgrade your plan to access advanced reports.');
        }

        return view('reports.advanced');
    }
}
```

## Scenario 4: Max Users Enforcement

```php
class TeamController extends Controller
{
    public function addMember(Team $team, User $user)
    {
        $activeSubscription = auth()->user()->activeSubscription();
        $maxUsers = app(FeatureGuard::class)->limit($activeSubscription, 'max_users');
        $currentUsers = $team->users()->count();

        if ($currentUsers >= $maxUsers) {
            return back()->with('error', "You've reached the maximum of {$maxUsers} users. Upgrade your plan.");
        }

        $team->users()->attach($user);
        return back()->with('success', 'Member added successfully.');
    }
}
```

## Scenario 5: Account Deletion with Immediate Cancel

```php
class UserController extends Controller
{
    public function deleteAccount(Request $request)
    {
        $user = auth()->user();

        DB::transaction(function () use ($user) {
            $user->cancelSubscription(true);
            $user->delete();
        });

        return redirect('/');
    }
}
```

---

## Exceptions

| Exception                       | Thrown When                   | Properties                           |
| ------------------------------- | ----------------------------- | ------------------------------------ |
| `InvalidPlanException`          | Plan is inactive or not found | `$message`                           |
| `FeatureLimitExceededException` | Feature limit exceeded        | `$featureSlug`, `$limit`, `$current` |
| `SubscriptionNotFoundException` | Subscription not found        | `$message`                           |

### Exception Handling Example:

```php
use Salehye\Subscription\Exceptions\FeatureLimitExceededException;
use Salehye\Subscription\Exceptions\InvalidPlanException;

try {
    $user->consumeFeature('monthly_emails', 100);
} catch (FeatureLimitExceededException $e) {
    return response()->json([
        'error' => "Feature '{$e->featureSlug}' limit exceeded",
        'limit' => $e->limit,
        'current' => $e->current,
    ], 403);
} catch (InvalidPlanException $e) {
    return response()->json([
        'error' => 'Plan is not available for subscription.',
    ], 400);
}
```

---

## Roadmap

- [ ] Email notifications (welcome, expiry reminders)
- [ ] Auto-renewal via Queue
- [ ] Plan bundles
- [ ] Tiered pricing
- [ ] Advanced analytics & reports

---
