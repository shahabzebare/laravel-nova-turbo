<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo;

use Illuminate\Cache\Events\CacheCleared;
use Illuminate\Support\Facades\Event;
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

            // Listen for cache:cleared event to regenerate turbo cache
            $this->registerCacheClearedListener();
        }

        // Use Nova::serving for proper timing (after Nova initializes)
        Nova::serving(function (ServingNova $event) {
            $this->overrideResourceMetadata();
        });
    }

    /**
     * Register listener for cache:cleared event to auto-regenerate turbo cache.
     */
    protected function registerCacheClearedListener(): void
    {
        if (! config('nova-turbo.regenerate_on_cache_clear', true)) {
            return;
        }

        Event::listen(CacheCleared::class, function (CacheCleared $event) {
            // Only regenerate if a cache existed before
            $cache = $this->app->make(MetadataCache::class);
            if ($cache->exists()) {
                $this->app->make(\Illuminate\Console\Kernel::class)
                    ->call('nova:turbo-cache');
            }
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

        // Only override if cache is valid (exists and version matches)
        if (! $cache->isValid()) {
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
