<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Tests\Fixtures\Nova;

use Laravel\Nova\Resource;

/**
 * Fake Nova resource for testing.
 */
class UserResource extends Resource
{
    public static $model = \Shahabzebare\NovaTurbo\Tests\Fixtures\Models\User::class;

    public static function uriKey(): string
    {
        return 'users';
    }

    public static function label(): string
    {
        return 'Users';
    }

    public static function singularLabel(): string
    {
        return 'User';
    }

    /**
     * Get the fields displayed by the resource.
     */
    public function fields($request): array
    {
        return [];
    }
}
