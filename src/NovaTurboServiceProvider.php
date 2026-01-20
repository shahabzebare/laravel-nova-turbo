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
                $metadata = $cache->getResourceMetadata();

                // Fallback to Nova's default if cache is empty
                if (empty($metadata)) {
                    return Nova::resourceInformation($request);
                }

                return $metadata;
            },
        ]);
    }
}
