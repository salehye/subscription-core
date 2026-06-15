<?php

declare(strict_types=1);

namespace Salehye\Subscription\Services;

use Carbon\Carbon;
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Models\Subscription;

class PricingCalculator
{
    /**
     * Calculate the prorated amount for switching plans.
     */
    public function calculateProratedAmount(
        Subscription $subscription,
        Plan $newPlan,
    ): float {
        if ($subscription->ends_at === null || $subscription->ends_at->isPast()) {
            return (float) $newPlan->price;
        }

        $now = Carbon::now();
        $remainingDays = $now->diffInDays($subscription->ends_at, false);
        $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at);

        if ($totalDays <= 0) {
            return (float) $newPlan->price;
        }

        // Credit for unused portion of current plan
        $dailyRate = (float) $subscription->plan->price / $totalDays;
        $credit = $dailyRate * $remainingDays;

        // Cost for new plan
        $newDailyRate = (float) $newPlan->price / $totalDays;
        $newCost = $newDailyRate * $remainingDays;

        return max(0, $newCost - $credit);
    }

    /**
     * Calculate the total cost for a plan including add-ons.
     *
     * @param  Plan[]  $addonPlans
     */
    public function calculateTotal(Plan $primaryPlan, array $addonPlans = []): float
    {
        $total = (float) $primaryPlan->price;

        foreach ($addonPlans as $addon) {
            $total += (float) $addon->price;
        }

        return $total;
    }

    /**
     * Calculate the daily rate for a plan.
     */
    public function dailyRate(Plan $plan): float
    {
        $days = $plan->getBillingCycleEnum()->days();

        if ($days <= 0) {
            return 0;
        }

        return (float) $plan->price / $days;
    }
}
