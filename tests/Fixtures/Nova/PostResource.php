<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Tests\Fixtures\Nova;

use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Resource;

/**
 * Fake Nova resource with relationships for testing.
 */
class PostResource extends Resource
{
    public static $model = \Shahabzebare\NovaTurbo\Tests\Fixtures\Models\Post::class;

    public static function uriKey(): string
    {
        return 'posts';
    }

    public static function label(): string
    {
        return 'Posts';
    }

    public static function singularLabel(): string
    {
        return 'Post';
    }

    /**
     * Get the fields displayed by the resource.
     */
    public function fields($request): array
    {
        return [
            HasMany::make('Comments', 'comments', CommentResource::class),
        ];
    }
}
