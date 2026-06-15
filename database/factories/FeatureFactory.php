<?php

declare(strict_types=1);

namespace Salehye\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Salehye\Subscription\Enums\FeatureType;
use Salehye\Subscription\Models\Feature;

class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'type' => fake()->randomElement([
                FeatureType::Toggle->value,
                FeatureType::Consumable->value,
                FeatureType::Limit->value,
            ]),
            'description' => fake()->sentence(),
        ];
    }

    public function toggle(): static
    {
        return $this->state(fn() => [
            'type' => FeatureType::Toggle->value,
            'name' => 'API Access',
            'slug' => 'api_access',
        ]);
    }

    public function consumable(): static
    {
        return $this->state(fn() => [
            'type' => FeatureType::Consumable->value,
            'name' => 'Monthly Emails',
            'slug' => 'monthly_emails',
        ]);
    }

    public function limit(): static
    {
        return $this->state(fn() => [
            'type' => FeatureType::Limit->value,
            'name' => 'Max Users',
            'slug' => 'max_users',
        ]);
    }
}
