<?php

namespace Shahabzebare\NovaTurbo\Services;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\HasManyThrough;
use Laravel\Nova\Fields\MorphMany;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

/**
 * Maps Nova resources to their related resources.
 */
class RelationshipMapper
{
    /**
     * Build a map of resource URI keys to their related resource classes.
     *
     * @param  Collection<string, class-string<Resource>>  $resources
     * @return array<string, array<int, class-string<Resource>>>
     */
    public function map(Collection $resources): array
    {
        $map = [];

        foreach ($resources as $uriKey => $resourceClass) {
            $map[$uriKey] = $this->getRelatedResources($resourceClass);
        }

        return $map;
    }

    /**
     * Get the resource class and its related resources.
     *
     * @param  class-string<Resource>  $resourceClass
     * @return array<int, class-string<Resource>>
     */
    protected function getRelatedResources(string $resourceClass): array
    {
        $related = [$resourceClass];

        try {
            // Create a resource instance with a model
            $modelClass = $resourceClass::$model;
            $resource = new $resourceClass(new $modelClass());

            // Get fields and find relationships
            $request = app(NovaRequest::class);
            $fields = $resource->fields($request);

            foreach ($fields as $field) {
                if ($this->isRelationshipField($field) && isset($field->resourceClass)) {
                    $related[] = $field->resourceClass;
                }
            }
        } catch (\Throwable) {
            // If we can't instantiate the resource, just return itself
        }

        return array_values(array_unique($related));
    }

    /**
     * Check if a field is a "to many" relationship field.
     */
    protected function isRelationshipField(mixed $field): bool
    {
        return $field instanceof HasMany
            || $field instanceof MorphMany
            || $field instanceof BelongsToMany
            || $field instanceof HasManyThrough
            || $field instanceof MorphToMany;
    }
}
