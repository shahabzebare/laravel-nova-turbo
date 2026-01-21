<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Nova\Actions\ActionResource;
use Laravel\Nova\Resource;
use ReflectionClass;
use Shahabzebare\NovaTurbo\NovaTurbo;
use Symfony\Component\Finder\Finder;

/**
 * Scans directories to discover Nova resource classes.
 */
class ResourceScanner
{
    /**
     * Scan all configured paths for Nova resources.
     *
     * @return Collection<string, class-string<resource>>
     */
    public function scan(): Collection
    {
        $resources = collect();

        foreach (config('nova-turbo.resource_paths', []) as $path) {
            if (is_dir($path)) {
                $resources = $resources->merge($this->scanPath($path));
            }
        }

        // Also include externally registered resources
        foreach (NovaTurbo::getExternalResources() as $resource) {
            $resources[$resource::uriKey()] = $resource;
        }

        return $resources;
    }

    /**
     * Scan a specific path for Nova resources.
     *
     * @return Collection<string, class-string<resource>>
     */
    protected function scanPath(string $path): Collection
    {
        $namespace = app()->getNamespace();
        $resources = collect();

        foreach ((new Finder)->in($path)->files()->name('*.php') as $file) {
            $class = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($file->getPathname(), app_path().DIRECTORY_SEPARATOR)
            );

            if (! class_exists($class)) {
                continue;
            }

            if ($this->isValidResource($class)) {
                $uriKey = $class::uriKey();
                $resources[$uriKey] = $class;
            }
        }

        return $resources;
    }

    /**
     * Check if a class is a valid Nova resource.
     */
    protected function isValidResource(string $class): bool
    {
        try {
            $reflection = new ReflectionClass($class);

            return is_subclass_of($class, Resource::class)
                && ! $reflection->isAbstract()
                && ! is_subclass_of($class, ActionResource::class);
        } catch (\Throwable) {
            return false;
        }
    }
}
