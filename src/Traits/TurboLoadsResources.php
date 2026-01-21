<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Traits;

use Illuminate\Support\Str;
use Laravel\Nova\Nova;
use Shahabzebare\NovaTurbo\Services\MetadataCache;

/**
 * Trait to enable lazy loading of Nova resources.
 *
 * Add this trait to your NovaServiceProvider to enable turbo mode:
 *
 * @example
 * class NovaServiceProvider extends NovaApplicationServiceProvider
 * {
 *     use \Shahabzebare\NovaTurbo\Traits\TurboLoadsResources;
 * }
 */
trait TurboLoadsResources
{
    /**
     * Override the resources method to enable lazy loading.
     */
    protected function resources(): void
    {
        $request = request();

        // Safety: no route available = early boot, console, etc.
        if ($request->route() === null) {
            parent::resources();

            return;
        }

        // API requests need all resources (filters, actions, etc.)
        if (Str::startsWith($request->path(), 'nova-api')) {
            parent::resources();

            return;
        }

        // Get resource key from route
        $resource = $request->route()->parameter('resource');

        // No resource = dashboard/home, load all for menu
        if (empty($resource)) {
            parent::resources();

            return;
        }

        // In development mode with auto-refresh, skip lazy loading
        if ($this->shouldAutoRefresh()) {
            parent::resources();

            return;
        }

        // Get cached relationships
        $cache = app(MetadataCache::class);
        $relationships = $cache->getRelationships();

        // Cache miss or resource not found = load all
        if (empty($relationships) || ! isset($relationships[$resource])) {
            parent::resources();

            return;
        }

        // Lazy load only needed resources
        $routeName = $request->route()?->getName();

        if (in_array($routeName, ['nova.pages.detail', 'nova.pages.attach', 'nova.pages.edit'])) {
            // Detail/Attach/Edit pages need related resources
            Nova::resources($relationships[$resource]);
        } else {
            // Index/Create pages only need the current resource
            Nova::resources([$relationships[$resource][0]]);
        }
    }

    /**
     * Determine if we should auto-refresh (skip lazy loading).
     */
    protected function shouldAutoRefresh(): bool
    {
        return app()->environment('local')
            && config('nova-turbo.auto_refresh_in_dev', true);
    }
}
