<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Traits;

use Illuminate\Http\Request;
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

        // In development mode with auto-refresh, skip lazy loading
        if ($this->shouldAutoRefresh()) {
            parent::resources();

            return;
        }

        // Get cached relationships
        $cache = app(MetadataCache::class);
        $relationships = $cache->getRelationships();

        // No cache = load all (cache hasn't been generated yet)
        if (empty($relationships)) {
            parent::resources();

            return;
        }

        // Extract resource key from request (works for both page and API requests)
        $resource = $this->extractResourceKey($request);

        // No resource = dashboard/home, load all for menu
        if (empty($resource)) {
            parent::resources();

            return;
        }

        // Resource not in cache = load all (safety fallback)
        if (! isset($relationships[$resource])) {
            parent::resources();

            return;
        }

        // Lazy load only needed resources
        $this->lazyLoadResources($request, $resource, $relationships[$resource]);
    }

    /**
     * Extract resource key from the request (both page and API routes).
     */
    protected function extractResourceKey(Request $request): ?string
    {
        // First try route parameter (for page requests)
        $resource = $request->route()->parameter('resource');
        if ($resource) {
            return $resource;
        }

        // For API requests, extract from path: nova-api/{resource}/...
        $path = $request->path();
        if (Str::startsWith($path, 'nova-api/')) {
            $segments = explode('/', $path);

            // nova-api/{resource} → index 1 is the resource
            return $segments[1] ?? null;
        }

        return null;
    }

    /**
     * Lazy load resources based on the request type.
     *
     * @param  array<int, class-string>  $relatedResources
     */
    protected function lazyLoadResources(Request $request, string $resource, array $relatedResources): void
    {
        $routeName = $request->route()?->getName();

        // Detail/Attach/Edit pages need all related resources
        if (in_array($routeName, ['nova.pages.detail', 'nova.pages.attach', 'nova.pages.edit'])) {
            Nova::resources($relatedResources);

            return;
        }

        // API requests need all related resources (for filters, actions, etc.)
        if (Str::startsWith($request->path(), 'nova-api')) {
            Nova::resources($relatedResources);

            return;
        }

        // Index/Create pages only need the current resource
        Nova::resources([$relatedResources[0]]);
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
