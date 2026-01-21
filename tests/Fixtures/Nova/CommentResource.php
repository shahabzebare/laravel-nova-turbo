<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Tests\Fixtures\Nova;

use Laravel\Nova\Resource;

/**
 * Fake Nova resource for testing relationships.
 */
class CommentResource extends Resource
{
    public static $model = \Shahabzebare\NovaTurbo\Tests\Fixtures\Models\Comment::class;

    public static function uriKey(): string
    {
        return 'comments';
    }

    public static function label(): string
    {
        return 'Comments';
    }

    public static function singularLabel(): string
    {
        return 'Comment';
    }

    /**
     * Get the fields displayed by the resource.
     */
    public function fields($request): array
    {
        return [];
    }
}
