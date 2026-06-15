<?php

declare(strict_types=1);

namespace Salehye\Subscription\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Canceled = 'canceled';
    case Expired = 'expired';
    case Suspended = 'suspended';
    case Paused = 'paused';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Canceled => 'Canceled',
            self::Expired => 'Expired',
            self::Suspended => 'Suspended',
            self::Paused => 'Paused',
            self::Pending => 'Pending',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::Active,
            self::Paused,
        ], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Expired,
            self::Canceled,
        ], true);
    }
}
