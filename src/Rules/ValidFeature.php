<?php

declare(strict_types=1);

namespace Salehye\Subscription\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Salehye\Subscription\Models\Feature;

class ValidFeature implements ValidationRule
{
    /**
     * @param  string|null  $type  Require a specific feature type (toggle, consumable, limit)
     */
    public function __construct(
        protected ?string $type = null,
    ) {
    }

    /**
     * Validate that the given value is a valid feature slug.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || empty($value)) {
            $fail('The :attribute must be a valid feature slug.');

            return;
        }

        $query = Feature::query()->where('slug', $value);

        if ($this->type !== null) {
            $query->where('type', $this->type);
        }

        if (!$query->exists()) {
            $message = 'The feature ":value" does not exist';

            if ($this->type !== null) {
                $message .= " or is not of type '{$this->type}'";
            }

            $fail($message . '.');
        }
    }
}
