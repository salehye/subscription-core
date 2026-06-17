<?php

declare(strict_types=1);

namespace Salehye\Subscription;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Salehye\Subscription\Commands\CreateRecurringInvoices;
use Salehye\Subscription\Commands\ExpireSubscriptions;
use Salehye\Subscription\Commands\ResetUsage;
use Salehye\Subscription\Contracts\FeatureResolver;
use Salehye\Subscription\Contracts\PlanRepository;
use Salehye\Subscription\Contracts\SubscriptionManager;
use Salehye\Subscription\Events\FeatureConsumed;
use Salehye\Subscription\Events\FeatureLimitReached;
use Salehye\Subscription\Events\PlanChanged;
use Salehye\Subscription\Events\SubscriptionCancelled;
use Salehye\Subscription\Events\SubscriptionExpired;
use Salehye\Subscription\Events\SubscriptionRenewed;
use Salehye\Subscription\Events\SubscriptionStarted;
use Salehye\Subscription\Listeners\SendSubscriptionNotification;
use Salehye\Subscription\Middleware\CheckFeatureAccess;
use Salehye\Subscription\Repositories\EloquentPlanRepository;
use Salehye\Subscription\Repositories\EloquentSubscriptionRepository;
use Salehye\Subscription\Resolvers\DefaultFeatureResolver;
use Salehye\Subscription\Services\FeatureGuard;
use Salehye\Subscription\Services\PricingCalculator;
use Salehye\Subscription\Services\SubscriptionManagerImpl;
use Salehye\Subscription\Services\UsageTracker;

class SubscriptionServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/subscription.php',
            'subscription',
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../config/subscription_seeder.php',
            'subscription_seeder',
        );

        // Bind contracts to implementations
        $this->app->singleton(SubscriptionManager::class, function (Container $app) {
            return new SubscriptionManagerImpl(
                $app->make(EloquentSubscriptionRepository::class),
                $app->make(UsageTracker::class),
            );
        });

        $this->app->singleton(PlanRepository::class, function (Container $app) {
            $planClass = config('subscription.models.plan', Models\Plan::class);

            return new EloquentPlanRepository(new $planClass());
        });

        $this->app->singleton(FeatureResolver::class, function (Container $app) {
            $resolverClass = config('subscription.feature_resolver', DefaultFeatureResolver::class);

            return new $resolverClass();
        });

        // Bind services
        $this->app->singleton(EloquentSubscriptionRepository::class, function (Container $app) {
            $subscriptionClass = config('subscription.models.subscription', Models\Subscription::class);
            $usageClass = config('subscription.models.subscription_usage', Models\SubscriptionUsage::class);

            return new EloquentSubscriptionRepository(
                new $subscriptionClass(),
                new $usageClass(),
            );
        });

        $this->app->singleton(UsageTracker::class, function (Container $app) {
            return new UsageTracker(
                $app->make(EloquentSubscriptionRepository::class),
            );
        });

        $this->app->singleton(FeatureGuard::class, function (Container $app) {
            return new FeatureGuard(
                $app->make(FeatureResolver::class),
                $app->make(UsageTracker::class),
            );
        });

        $this->app->singleton(PricingCalculator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load migrations
        // $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Publish migrations
        $this->publishesMigrations([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'subscription-migrations');

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/subscription.php' => config_path('subscription.php'),
        ], 'subscription-config');

        // Publish seeder config
        $this->publishes([
            __DIR__ . '/../config/subscription_seeder.php' => config_path('subscription_seeder.php'),
        ], 'subscription-seeder-config');

        // Publish seeders
        $this->publishes([
            __DIR__ . '/../database/seeders' => database_path('seeders/SalehyeSubscription'),
        ], 'subscription-seeders');

        // Register middleware
        $this->app->make('router')->aliasMiddleware('subscription.feature', CheckFeatureAccess::class);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireSubscriptions::class,
                ResetUsage::class,
                CreateRecurringInvoices::class,
            ]);
        }

        // Register events and listeners
        if (config('subscription.auto_register_events', true)) {
            $this->registerEvents();
        }
    }

    /**
     * Register event listeners for subscription events.
     */
    protected function registerEvents(): void
    {
        $this->app->make('events')->listen(
            SubscriptionStarted::class,
            [SendSubscriptionNotification::class, 'handleSubscriptionStarted'],
        );

        $this->app->make('events')->listen(
            SubscriptionRenewed::class,
            [SendSubscriptionNotification::class, 'handleSubscriptionStarted'],
        );

        $this->app->make('events')->listen(
            SubscriptionCancelled::class,
            [SendSubscriptionNotification::class, 'handleSubscriptionCancelled'],
        );

        $this->app->make('events')->listen(
            SubscriptionExpired::class,
            [SendSubscriptionNotification::class, 'handleSubscriptionExpired'],
        );
    }
}
