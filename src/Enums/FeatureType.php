<?php

declare(strict_types=1);

namespace Salehye\Subscription\Enums;

enum FeatureType: string
{
    case Toggle = 'toggle';
    case Consumable = 'consumable';
    case Limit = 'limit';

    public function label(): string
    {
        return match ($this) {
            self::Toggle => 'Toggle (On/Off)',
            self::Consumable => 'Consumable (Usage Based)',
            self::Limit => 'Limit (Dynamic Check)',
        };
    }
}
