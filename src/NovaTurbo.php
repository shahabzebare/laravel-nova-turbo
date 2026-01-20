<?php

namespace Shahabzebare\NovaTurbo;

/**
 * Facade for registering external Nova resources.
 *
 * Use this to register resources that are not in the standard app/Nova directory,
 * such as resources from modules or packages.
 *
 * @example
 * NovaTurbo::resources([
 *     \Modules\Order\Nova\Order::class,
 *     \Modules\Customer\Nova\Customer::class,
 * ]);
 */
class NovaTurbo
{
    /**
     * External resources registered via facade.
     *
     * @var array<int, class-string<\Laravel\Nova\Resource>>
     */
    protected static array $externalResources = [];

    /**
     * Register external resources to be included in scanning.
     *
     * @param  array<int, class-string<\Laravel\Nova\Resource>>  $resources
     */
    public static function resources(array $resources): void
    {
        static::$externalResources = array_unique(
            array_merge(static::$externalResources, $resources)
        );
    }

    /**
     * Get all registered external resources.
     *
     * @return array<int, class-string<\Laravel\Nova\Resource>>
     */
    public static function getExternalResources(): array
    {
        return static::$externalResources;
    }

    /**
     * Clear all registered external resources.
     */
    public static function clearExternalResources(): void
    {
        static::$externalResources = [];
    }
}
