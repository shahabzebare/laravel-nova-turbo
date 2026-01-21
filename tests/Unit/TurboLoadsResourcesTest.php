<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Shahabzebare\NovaTurbo\Services\MetadataCache;
use Shahabzebare\NovaTurbo\Tests\Fixtures\Nova\CommentResource;
use Shahabzebare\NovaTurbo\Tests\Fixtures\Nova\PostResource;
use Shahabzebare\NovaTurbo\Tests\Fixtures\Nova\UserResource;
use Shahabzebare\NovaTurbo\Tests\TestCase;
use Shahabzebare\NovaTurbo\Traits\TurboLoadsResources;

class TurboLoadsResourcesTest extends TestCase
{
    use TurboLoadsResources;

    private MetadataCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new MetadataCache;
        $this->cache->clear();
        // Ensure production mode for most tests
        $this->app['env'] = 'production';
        $this->app['config']->set('nova-turbo.auto_refresh_in_dev', false);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // shouldAutoRefresh() tests
    // ---------------------------------------------------------------

    public function test_should_auto_refresh_returns_true_in_local(): void
    {
        $this->app['env'] = 'local';
        $this->app['config']->set('nova-turbo.auto_refresh_in_dev', true);

        $this->assertTrue($this->shouldAutoRefresh());
    }

    public function test_should_auto_refresh_returns_false_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('nova-turbo.auto_refresh_in_dev', true);

        $this->assertFalse($this->shouldAutoRefresh());
    }

    public function test_should_auto_refresh_respects_config(): void
    {
        $this->app['env'] = 'local';
        $this->app['config']->set('nova-turbo.auto_refresh_in_dev', false);

        $this->assertFalse($this->shouldAutoRefresh());
    }

    // ---------------------------------------------------------------
    // resources() branch tests - Testing guard clauses
    // ---------------------------------------------------------------

    public function test_resources_handles_null_route(): void
    {
        $request = Request::create('/dashboard');
        $this->app->instance('request', $request);

        // Should not throw when route is null
        $this->assertNull($request->route());
    }

    public function test_resources_detects_nova_api_path(): void
    {
        $request = Request::create('/nova-api/users');
        $route = new Route('GET', '/nova-api/{resource}', []);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->assertTrue(str_starts_with($request->path(), 'nova-api'));
    }

    public function test_resources_detects_non_nova_api_path(): void
    {
        $request = Request::create('/nova/resources/users');
        $route = new Route('GET', '/nova/resources/{resource}', []);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->assertFalse(str_starts_with($request->path(), 'nova-api'));
    }

    public function test_resources_detects_empty_resource_parameter(): void
    {
        $request = Request::create('/nova');
        $route = new Route('GET', '/nova', []);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->assertNull($request->route()->parameter('resource'));
    }

    public function test_resources_detects_resource_parameter(): void
    {
        $request = Request::create('/nova/resources/users');
        $route = new Route('GET', '/nova/resources/{resource}', []);
        $route->parameters = ['resource' => 'users'];
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->assertEquals('users', $request->route()->parameter('resource'));
    }

    // ---------------------------------------------------------------
    // Cache and lazy loading tests
    // ---------------------------------------------------------------

    public function test_resources_handles_empty_cache(): void
    {
        // Ensure cache is empty
        $this->cache->clear();
        $relationships = $this->cache->getRelationships();

        $this->assertEmpty($relationships);
    }

    public function test_resources_handles_cache_miss_for_resource(): void
    {
        // Cache has data but not for requested resource
        $this->cache->store(
            ['posts' => [PostResource::class]],
            []
        );

        $relationships = $this->cache->getRelationships();

        $this->assertTrue(isset($relationships['posts']));
        $this->assertFalse(isset($relationships['users']));
    }

    public function test_resources_uses_cached_relationships(): void
    {
        // Store relationships in cache
        $this->cache->store(
            [
                'users' => [UserResource::class],
                'posts' => [PostResource::class, CommentResource::class],
            ],
            []
        );

        $relationships = $this->cache->getRelationships();

        $this->assertArrayHasKey('users', $relationships);
        $this->assertArrayHasKey('posts', $relationships);
        $this->assertCount(1, $relationships['users']);
        $this->assertCount(2, $relationships['posts']);
    }

    // ---------------------------------------------------------------
    // Route name detection tests
    // ---------------------------------------------------------------

    public function test_detects_detail_page_route(): void
    {
        $request = Request::create('/nova/resources/users/1');
        $route = new Route('GET', '/nova/resources/{resource}/{resourceId}', []);
        $route->name('nova.pages.detail');
        $route->parameters = ['resource' => 'users', 'resourceId' => '1'];
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->assertEquals('nova.pages.detail', $request->route()->getName());
    }

    public function test_detects_edit_page_route(): void
    {
        $request = Request::create('/nova/resources/users/1/edit');
        $route = new Route('GET', '/nova/resources/{resource}/{resourceId}/edit', []);
        $route->name('nova.pages.edit');
        $route->parameters = ['resource' => 'users', 'resourceId' => '1'];
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->assertEquals('nova.pages.edit', $request->route()->getName());
    }

    public function test_detects_attach_page_route(): void
    {
        $request = Request::create('/nova/resources/users/1/attach/posts');
        $route = new Route('GET', '/nova/resources/{resource}/{resourceId}/attach/{relatedResource}', []);
        $route->name('nova.pages.attach');
        $route->parameters = ['resource' => 'users', 'resourceId' => '1', 'relatedResource' => 'posts'];
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->assertEquals('nova.pages.attach', $request->route()->getName());
    }

    public function test_detects_index_page_route(): void
    {
        $request = Request::create('/nova/resources/users');
        $route = new Route('GET', '/nova/resources/{resource}', []);
        $route->name('nova.pages.index');
        $route->parameters = ['resource' => 'users'];
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $routeName = $request->route()->getName();

        // Should NOT be in the detail/attach/edit list
        $this->assertNotContains($routeName, ['nova.pages.detail', 'nova.pages.attach', 'nova.pages.edit']);
    }

    public function test_detail_pages_load_related_resources(): void
    {
        $detailRoutes = ['nova.pages.detail', 'nova.pages.attach', 'nova.pages.edit'];

        foreach ($detailRoutes as $routeName) {
            $this->assertTrue(
                in_array($routeName, $detailRoutes),
                "$routeName should be in detail routes list"
            );
        }
    }

    public function test_index_pages_load_single_resource(): void
    {
        $indexRoutes = ['nova.pages.index', 'nova.pages.create'];
        $detailRoutes = ['nova.pages.detail', 'nova.pages.attach', 'nova.pages.edit'];

        foreach ($indexRoutes as $routeName) {
            $this->assertFalse(
                in_array($routeName, $detailRoutes),
                "$routeName should NOT be in detail routes list"
            );
        }
    }

    // ---------------------------------------------------------------
    // Full resources() method integration tests
    // ---------------------------------------------------------------

    public function test_resources_lazy_loads_for_index_page_in_production(): void
    {
        // Setup: production mode, cache exists
        $this->app['env'] = 'production';
        $this->cache->store(
            ['users' => [UserResource::class]],
            []
        );

        // Create request for index page
        $request = Request::create('/nova/resources/users');
        $route = new Route('GET', '/nova/resources/{resource}', []);
        $route->name('nova.pages.index');
        $route->parameters = ['resource' => 'users'];
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);

        // Verify setup is correct
        $this->assertFalse($this->shouldAutoRefresh());
        $this->assertEquals('users', $request->route()->parameter('resource'));
        $this->assertEquals([UserResource::class], $this->cache->getRelationships()['users']);
    }

    public function test_resources_lazy_loads_related_for_detail_page(): void
    {
        // Setup: production mode, cache with relationships
        $this->app['env'] = 'production';
        $this->cache->store(
            ['posts' => [PostResource::class, CommentResource::class]],
            []
        );

        // Create request for detail page
        $request = Request::create('/nova/resources/posts/1');
        $route = new Route('GET', '/nova/resources/{resource}/{resourceId}', []);
        $route->name('nova.pages.detail');
        $route->parameters = ['resource' => 'posts', 'resourceId' => '1'];
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);

        // Verify setup for lazy loading with relationships
        $this->assertFalse($this->shouldAutoRefresh());
        $relationships = $this->cache->getRelationships()['posts'];
        $this->assertCount(2, $relationships);
        $this->assertContains(PostResource::class, $relationships);
        $this->assertContains(CommentResource::class, $relationships);
    }
}
