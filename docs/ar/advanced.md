<div dir="rtl">

# سيناريوهات متقدمة

## السيناريو 1: تسجيل مستخدم جديد مع خطة مجانية

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    $user = User::create([...]);

    $freePlan = Plan::where('slug', 'free')->first();
    $user->subscribeTo($freePlan);

    Mail::to($user)->send(new WelcomeMail($user));
});
```

## السيناريو 2: تبديل الخطة مع المحاسبة

```php
$subscription = $user->activeSubscription();
$newPlan = Plan::where('slug', 'enterprise')->first();

$calculator = app(PricingCalculator::class);
$proratedAmount = $calculator->calculateProratedAmount($subscription, $newPlan);

// أنشئ فاتورة في نظام الدفع
$invoice = $user->charge($proratedAmount);

\Subscription::switchPlan($subscription, $newPlan);
```

## السيناريو 3: التحقق من الميزة في Controller

```php
class ReportController extends Controller
{
    public function advanced()
    {
        $user = auth()->user();

        if (!$user->canAccessFeature('advanced_reports')) {
            return redirect()->route('reports.basic')
                ->with('error', 'قم بترقية خطتك للوصول إلى التقارير المتقدمة.');
        }

        return view('reports.advanced');
    }
}
```

## السيناريو 4: نظام الحد الأقصى للمستخدمين

```php
class TeamController extends Controller
{
    public function addMember(Team $team, User $user)
    {
        $activeSubscription = auth()->user()->activeSubscription();
        $maxUsers = app(FeatureGuard::class)->limit($activeSubscription, 'max_users');
        $currentUsers = $team->users()->count();

        if ($currentUsers >= $maxUsers) {
            return back()->with('error', "لقد وصلت للحد الأقصى ({$maxUsers}) من المستخدمين.");
        }

        $team->users()->attach($user);
        return back()->with('success', 'تمت إضافة العضو بنجاح.');
    }
}
```

## السيناريو 5: إلغاء الحساب مع الإلغاء الفوري للاشتراك

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

## الاستثناءات (Exceptions)

| الاستثناء                       | يُرمى عندما                  | الخصائص                              |
| ------------------------------- | ---------------------------- | ------------------------------------ |
| `InvalidPlanException`          | الخطة غير نشطة أو غير موجودة | `$message`                           |
| `FeatureLimitExceededException` | تجاوز حد الميزة              | `$featureSlug`、`$limit`、`$current` |
| `SubscriptionNotFoundException` | الاشتراك غير موجود           | `$message`                           |

### مثال على معالجة الاستثناءات:

```php
use Salehye\Subscription\Exceptions\FeatureLimitExceededException;
use Salehye\Subscription\Exceptions\InvalidPlanException;

try {
    $user->consumeFeature('monthly_emails', 100);
} catch (FeatureLimitExceededException $e) {
    return response()->json([
        'error' => "تم تجاوز الحد لميزة '{$e->featureSlug}'",
        'limit' => $e->limit,
        'current' => $e->current,
    ], 403);
} catch (InvalidPlanException $e) {
    return response()->json([
        'error' => 'الخطة غير متاحة للاشتراك.',
    ], 400);
}
```

---

## خريطة الطريق (Roadmap)

- [ ] إشعارات البريد الإلكتروني
- [ ] التجديد التلقائي عبر Queue
- [ ] الخطط المجمعة (Bundles)
- [ ] تسعير متدرج (Tiered Pricing)
- [ ] إحصائيات متقدمة

---

</div>
