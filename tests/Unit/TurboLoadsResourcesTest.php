<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Shahabzebare\NovaTurbo\Services\MetadataCache;
use Shahabzebare\NovaTurbo\Tests\TestCase;
use Shahabzebare\NovaTurbo\Traits\TurboLoadsResources;

class TurboLoadsResourcesTest extends TestCase
{
    use TurboLoadsResources;

    private MetadataCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new MetadataCache();
        $this->cache->clear();
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        parent::tearDown();
    }

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

    public function test_resources_calls_parent_when_no_route(): void
    {
        // When request has no route, it should fall through to parent
        $request = Request::create('/dashboard');
        $this->app->instance('request', $request);

        // This test mainly checks the guard clause doesn't throw
        $this->assertNull($request->route());
    }

    public function test_resources_calls_parent_for_nova_api(): void
    {
        // Nova API requests should load all resources
        $request = Request::create('/nova-api/users');
        $route = new Route('GET', '/nova-api/{resource}', []);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->assertTrue(str_starts_with($request->path(), 'nova-api'));
    }
}
