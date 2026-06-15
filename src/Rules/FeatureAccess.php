<?php

declare(strict_types=1);

namespace Salehye\Subscription\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Salehye\Subscription\Contracts\HasSubscriptions;

class FeatureAccess implements ValidationRule
{
    /**
     * @param  HasSubscriptions  $subscriber  The subscriber to check access for
     * @param  string  $featureSlug  The feature slug to check
     */
    public function __construct(
        protected HasSubscriptions $subscriber,
        protected string $featureSlug,
    ) {
    }

    /**
     * Validate that the subscriber can access the given feature.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->subscriber->canAccessFeature($this->featureSlug)) {
            $fail("You need an upgraded plan to access '{$this->featureSlug}'.");
        }
    }
}
