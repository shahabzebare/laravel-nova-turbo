<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo;

use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;
use Shahabzebare\NovaTurbo\Commands\TurboCacheCommand;
use Shahabzebare\NovaTurbo\Services\MetadataCache;
use Shahabzebare\NovaTurbo\Services\RelationshipMapper;
use Shahabzebare\NovaTurbo\Services\ResourceScanner;

class NovaTurboServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nova-turbo.php', 'nova-turbo');

        $this->app->singleton(MetadataCache::class);
        $this->app->singleton(ResourceScanner::class);
        $this->app->singleton(RelationshipMapper::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/nova-turbo.php' => config_path('nova-turbo.php'),
        ], 'nova-turbo-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                TurboCacheCommand::class,
            ]);
        }

        // Use Nova::serving for proper timing (after Nova initializes)
        Nova::serving(function (ServingNova $event) {
            $this->overrideResourceMetadata();
        });
    }

    /**
     * Override Nova's resource metadata with cached data.
     *
     * Uses ONLY cached metadata - no live resource loading for authorization.
     * This is the key to performance: we skip Nova::resourceInformation() entirely.
     */
    protected function overrideResourceMetadata(): void
    {
        $cache = $this->app->make(MetadataCache::class);

        // Only override if cache exists
        if (! $cache->exists()) {
            return;
        }

        $cachedMetadata = $cache->getResourceMetadata();

        // Don't override if cache is empty
        if (empty($cachedMetadata)) {
            return;
        }

        // Provide cached metadata directly - NO live authorization check
        // This avoids loading all resource classes just for metadata
        Nova::provideToScript([
            'resources' => $cachedMetadata,
        ]);
    }
}
