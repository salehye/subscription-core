<?php

declare(strict_types=1);

namespace Salehye\Subscription\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Salehye\Subscription\Models\Plan;

class ValidPlan implements ValidationRule
{
    /**
     * @param  bool  $requireActive  Whether the plan must be active
     */
    public function __construct(
        protected bool $requireActive = true,
    ) {
    }

    /**
     * Validate that the given value is a valid plan ID.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail('The :attribute must be a valid plan ID.');

            return;
        }

        $query = Plan::query()->where('id', (int) $value);

        if ($this->requireActive) {
            $query->where('is_active', true);
        }

        if (!$query->exists()) {
            $fail(
                $this->requireActive
                ? 'The selected plan is not active or does not exist.'
                : 'The selected plan does not exist.'
            );
        }
    }
}
