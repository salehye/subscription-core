<?php

declare(strict_types=1);

namespace Salehye\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;

class SubscriptionDatabaseSeeder extends Seeder
{
    protected array $seeders = [
        PlanSeeder::class,
        FeatureSeeder::class,
    ];

    public function run(): void
    {
        foreach ($this->seeders as $seeder) {
            $this->call($seeder);
        }
    }
}
