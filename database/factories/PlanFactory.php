<?php

declare(strict_types=1);

namespace Salehye\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Salehye\Subscription\Models\Plan;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'billing_cycle' => fake()->randomElement(['monthly', 'yearly', 'lifetime']),
            'price' => fake()->randomFloat(2, 5, 200),
            'trial_days' => fake()->randomElement([0, 7, 14, 30]),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function monthly(): static
    {
        return $this->state(fn() => [
            'billing_cycle' => 'monthly',
            'trial_days' => 7,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn() => [
            'billing_cycle' => 'yearly',
            'trial_days' => 14,
        ]);
    }

    public function lifetime(): static
    {
        return $this->state(fn() => [
            'billing_cycle' => 'lifetime',
            'trial_days' => 0,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn() => [
            'is_active' => false,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn() => [
            'price' => 0,
            'trial_days' => 0,
        ]);
    }
}
