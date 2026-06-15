<?php

declare(strict_types=1);

namespace Salehye\Subscription\Tests\Unit;

use Salehye\Subscription\Enums\FeatureType;
use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Tests\TestCase;

class FeatureTest extends TestCase
{
    public function test_can_create_toggle_feature(): void
    {
        $feature = Feature::query()->create([
            'name' => 'API Access',
            'slug' => 'api_access',
            'type' => FeatureType::Toggle->value,
        ]);

        $this->assertNotNull($feature);
        $this->assertEquals(FeatureType::Toggle, $feature->getTypeEnum());
    }

    public function test_can_create_consumable_feature(): void
    {
        $feature = Feature::query()->create([
            'name' => 'Storage',
            'slug' => 'storage',
            'type' => FeatureType::Consumable->value,
        ]);

        $this->assertEquals(FeatureType::Consumable, $feature->getTypeEnum());
    }

    public function test_can_create_limit_feature(): void
    {
        $feature = Feature::query()->create([
            'name' => 'Max Users',
            'slug' => 'max_users',
            'type' => FeatureType::Limit->value,
        ]);

        $this->assertEquals(FeatureType::Limit, $feature->getTypeEnum());
    }

    public function test_feature_can_be_attached_to_plan(): void
    {
        $feature = Feature::query()->create([
            'name' => 'Max Projects',
            'slug' => 'max_projects',
            'type' => FeatureType::Limit->value,
        ]);

        $plan = \Salehye\Subscription\Models\Plan::query()->create([
            'name' => 'Test',
            'slug' => 'test',
            'billing_cycle' => 'monthly',
            'price' => 0,
            'sort_order' => 1,
        ]);

        $plan->features()->attach($feature->id, ['value' => '5']);

        $this->assertCount(1, $feature->plans);
        $this->assertEquals('5', $feature->plans->first()->pivot->value);
    }

    public function test_soft_delete(): void
    {
        $feature = Feature::query()->create([
            'name' => 'Temp',
            'slug' => 'temp',
            'type' => FeatureType::Toggle->value,
        ]);

        $feature->delete();

        $this->assertNotNull($feature->deleted_at);
    }
}
