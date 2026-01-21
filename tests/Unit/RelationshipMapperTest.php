<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Tests\Unit;

use Illuminate\Support\Collection;
use Shahabzebare\NovaTurbo\Services\RelationshipMapper;
use Shahabzebare\NovaTurbo\Tests\Fixtures\Nova\CommentResource;
use Shahabzebare\NovaTurbo\Tests\Fixtures\Nova\PostResource;
use Shahabzebare\NovaTurbo\Tests\Fixtures\Nova\UserResource;
use Shahabzebare\NovaTurbo\Tests\TestCase;

class RelationshipMapperTest extends TestCase
{
    private RelationshipMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new RelationshipMapper;
    }

    public function test_map_returns_array(): void
    {
        $resources = new Collection(['users' => UserResource::class]);

        $result = $this->mapper->map($resources);

        $this->assertIsArray($result);
    }

    public function test_map_includes_resource_itself(): void
    {
        $resources = new Collection(['users' => UserResource::class]);

        $result = $this->mapper->map($resources);

        $this->assertArrayHasKey('users', $result);
        $this->assertContains(UserResource::class, $result['users']);
    }

    public function test_map_includes_has_many_relationships(): void
    {
        $resources = new Collection(['posts' => PostResource::class]);

        $result = $this->mapper->map($resources);

        $this->assertArrayHasKey('posts', $result);
        // The PostResource has a HasMany to CommentResource
        $this->assertContains(PostResource::class, $result['posts']);
        $this->assertContains(CommentResource::class, $result['posts']);
    }

    public function test_map_resource_without_relationships_returns_only_self(): void
    {
        $resources = new Collection(['users' => UserResource::class]);

        $result = $this->mapper->map($resources);

        $this->assertArrayHasKey('users', $result);
        $this->assertCount(1, $result['users']);
        $this->assertEquals([UserResource::class], $result['users']);
    }

    public function test_map_handles_multiple_resources(): void
    {
        $resources = new Collection([
            'users' => UserResource::class,
            'posts' => PostResource::class,
        ]);

        $result = $this->mapper->map($resources);

        $this->assertArrayHasKey('users', $result);
        $this->assertArrayHasKey('posts', $result);
    }
}
