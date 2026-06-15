<?php

declare(strict_types=1);

namespace Salehye\Subscription\Enums;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case Lifetime = 'lifetime';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
            self::Lifetime => 'Lifetime',
        };
    }

    public function days(): int
    {
        return match ($this) {
            self::Monthly => config('subscription.billing.monthly_days', 30),
            self::Yearly => config('subscription.billing.yearly_days', 365),
            self::Lifetime => config('subscription.billing.lifetime_days', 36500),
        };
    }
}
