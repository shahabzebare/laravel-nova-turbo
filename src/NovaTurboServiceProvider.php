<?php

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
     * Merges cached static metadata with real-time authorization checks
     * for the currently loaded resources.
     */
    protected function overrideResourceMetadata(): void
    {
        $cache = $this->app->make(MetadataCache::class);

        // Only override if cache exists
        if (!$cache->exists()) {
            return;
        }

        Nova::provideToScript([
            'resources' => function ($request) use ($cache) {
                $cachedMetadata = $cache->getResourceMetadata();

                // Fallback to Nova's default if cache is empty
                if (empty($cachedMetadata)) {
                    return Nova::resourceInformation($request);
                }

                // Get real-time authorization for LOADED resources only
                // This is fast because we only check 1-3 resources, not all 43
                $liveResourceInfo = Nova::resourceInformation($request);
                $liveAuthMap = [];
                foreach ($liveResourceInfo as $info) {
                    $liveAuthMap[$info['uriKey']] = $info['authorizedToCreate'] ?? false;
                }

                // Merge cached metadata with live authorization
                return array_map(function ($cached) use ($liveAuthMap) {
                    // Use live authorization if available, otherwise default to false (safer)
                    $cached['authorizedToCreate'] = $liveAuthMap[$cached['uriKey']] ?? false;
                    return $cached;
                }, $cachedMetadata);
            },
        ]);
    }
}
