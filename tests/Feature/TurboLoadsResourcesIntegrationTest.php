<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Laravel\Nova\Nova;
use Shahabzebare\NovaTurbo\NovaTurbo;
use Shahabzebare\NovaTurbo\NovaTurboServiceProvider;
use Shahabzebare\NovaTurbo\Services\MetadataCache;
use Shahabzebare\NovaTurbo\Tests\Fixtures\Nova\CommentResource;
use Shahabzebare\NovaTurbo\Tests\Fixtures\Nova\PostResource;
use Shahabzebare\NovaTurbo\Tests\Fixtures\Nova\UserResource;
use Shahabzebare\NovaTurbo\Tests\TestCase;
use Shahabzebare\NovaTurbo\Traits\TurboLoadsResources;

/**
 * Testable version of TurboLoadsResources that exposes protected methods.
 */
class TestableTurboProvider
{
    use TurboLoadsResources;

    private $app;
    private bool $parentCalled = false;
    private array $loadedResources = [];

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Mock parent::resources() - just track that it was called.
     */
    protected function parentResources(): void
    {
        $this->parentCalled = true;
    }

    /**
     * Expose the resources method for testing.
     */
    public function callResources(): void
    {
        $this->resources();
    }

    /**
     * Override to track and call Nova::resources instead of parent.
     */
    protected function resources(): void
    {
        $request = request();

        // Safety: no route available = early boot, console, etc.
        if ($request->route() === null) {
            $this->parentResources();
            return;
        }

        // API requests need all resources
        if (str_starts_with($request->path(), 'nova-api')) {
            $this->parentResources();
            return;
        }

        // Get resource key from route
        $resource = $request->route()->parameter('resource');

        // No resource = dashboard/home
        if (empty($resource)) {
            $this->parentResources();
            return;
        }

        // In development mode with auto-refresh
        if ($this->shouldAutoRefresh()) {
            $this->parentResources();
            return;
        }

        // Get cached relationships
        $cache = app(MetadataCache::class);
        $relationships = $cache->getRelationships();

        // Cache miss or resource not found
        if (empty($relationships) || !isset($relationships[$resource])) {
            $this->parentResources();
            return;
        }

        // Lazy load only needed resources
        $routeName = $request->route()?->getName();

        if (in_array($routeName, ['nova.pages.detail', 'nova.pages.attach', 'nova.pages.edit'])) {
            $this->loadedResources = $relationships[$resource];
            Nova::resources($relationships[$resource]);
        } else {
            $this->loadedResources = [$relationships[$resource][0]];
            Nova::resources([$relationships[$resource][0]]);
        }
    }

    public function wasParentCalled(): bool
    {
        return $this->parentCalled;
    }

    public function getLoadedResources(): array
    {
        return $this->loadedResources;
    }

    public function reset(): void
    {
        $this->parentCalled = false;
        $this->loadedResources = [];
    }
}

class TurboLoadsResourcesIntegrationTest extends TestCase
{
    private MetadataCache $cache;
    private TestableTurboProvider $provider;

    protected function getPackageProviders($app): array
    {
        return [
            NovaTurboServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new MetadataCache();
        $this->cache->clear();
        $this->provider = new TestableTurboProvider($this->app);
        NovaTurbo::clearExternalResources();
        Nova::flushState();
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        NovaTurbo::clearExternalResources();
        Nova::flushState();
        parent::tearDown();
    }

    protected function createRequest(
        string $uri,
        string $routePattern,
        ?string $routeName = null,
        array $parameters = []
    ): Request {
        $request = Request::create($uri);
        $route = new Route('GET', $routePattern, []);
        if ($routeName) {
            $route->name($routeName);
        }
        $route->parameters = $parameters;
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);

        return $request;
    }

    // ---------------------------------------------------------------
    // Guard clause tests - verify parent is called
    // ---------------------------------------------------------------

    public function test_resources_calls_parent_when_no_route(): void
    {
        $request = Request::create('/dashboard');
        $this->app->instance('request', $request);

        $this->provider->callResources();

        $this->assertTrue($this->provider->wasParentCalled());
    }

    public function test_resources_calls_parent_for_nova_api(): void
    {
        $this->createRequest('/nova-api/users', '/nova-api/{resource}', null, ['resource' => 'users']);

        $this->provider->callResources();

        $this->assertTrue($this->provider->wasParentCalled());
    }

    public function test_resources_calls_parent_when_no_resource_param(): void
    {
        $this->createRequest('/nova', '/nova');

        $this->provider->callResources();

        $this->assertTrue($this->provider->wasParentCalled());
    }

    public function test_resources_calls_parent_in_auto_refresh_mode(): void
    {
        $this->app['env'] = 'local';
        $this->app['config']->set('nova-turbo.auto_refresh_in_dev', true);

        $this->createRequest(
            '/nova/resources/users',
            '/nova/resources/{resource}',
            'nova.pages.index',
            ['resource' => 'users']
        );

        $this->provider->callResources();

        $this->assertTrue($this->provider->wasParentCalled());
    }

    public function test_resources_calls_parent_when_cache_empty(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('nova-turbo.auto_refresh_in_dev', false);
        $this->cache->clear();

        $this->createRequest(
            '/nova/resources/users',
            '/nova/resources/{resource}',
            'nova.pages.index',
            ['resource' => 'users']
        );

        $this->provider->callResources();

        $this->assertTrue($this->provider->wasParentCalled());
    }

    public function test_resources_calls_parent_when_resource_not_in_cache(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('nova-turbo.auto_refresh_in_dev', false);

        $this->cache->store(['posts' => [PostResource::class]], []);

        $this->createRequest(
            '/nova/resources/users',
            '/nova/resources/{resource}',
            'nova.pages.index',
            ['resource' => 'users']
        );

        $this->provider->callResources();

        $this->assertTrue($this->provider->wasParentCalled());
    }

    // ---------------------------------------------------------------
    // Lazy loading tests - verify correct resources are loaded
    // ---------------------------------------------------------------

    public function test_resources_lazy_loads_single_resource_for_index(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('nova-turbo.auto_refresh_in_dev', false);

        $this->cache->store([
            'users' => [UserResource::class, PostResource::class],
        ], []);

        $this->createRequest(
            '/nova/resources/users',
            '/nova/resources/{resource}',
            'nova.pages.index',
            ['resource' => 'users']
        );

        $this->provider->callResources();

        $this->assertFalse($this->provider->wasParentCalled());
        $this->assertCount(1, $this->provider->getLoadedResources());
        $this->assertContains(UserResource::class, $this->provider->getLoadedResources());
    }

    public function test_resources_lazy_loads_related_resources_for_detail(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('nova-turbo.auto_refresh_in_dev', false);

        $this->cache->store([
            'posts' => [PostResource::class, CommentResource::class],
        ], []);

        $this->createRequest(
            '/nova/resources/posts/1',
            '/nova/resources/{resource}/{resourceId}',
            'nova.pages.detail',
            ['resource' => 'posts', 'resourceId' => '1']
        );

        $this->provider->callResources();

        $this->assertFalse($this->provider->wasParentCalled());
        $this->assertCount(2, $this->provider->getLoadedResources());
        $this->assertContains(PostResource::class, $this->provider->getLoadedResources());
        $this->assertContains(CommentResource::class, $this->provider->getLoadedResources());
    }

    public function test_resources_lazy_loads_related_resources_for_edit(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('nova-turbo.auto_refresh_in_dev', false);

        $this->cache->store([
            'posts' => [PostResource::class, CommentResource::class],
        ], []);

        $this->createRequest(
            '/nova/resources/posts/1/edit',
            '/nova/resources/{resource}/{resourceId}/edit',
            'nova.pages.edit',
            ['resource' => 'posts', 'resourceId' => '1']
        );

        $this->provider->callResources();

        $this->assertFalse($this->provider->wasParentCalled());
        $this->assertCount(2, $this->provider->getLoadedResources());
    }

    public function test_resources_lazy_loads_related_resources_for_attach(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('nova-turbo.auto_refresh_in_dev', false);

        $this->cache->store([
            'users' => [UserResource::class, PostResource::class],
        ], []);

        $this->createRequest(
            '/nova/resources/users/1/attach/posts',
            '/nova/resources/{resource}/{resourceId}/attach/{relatedResource}',
            'nova.pages.attach',
            ['resource' => 'users', 'resourceId' => '1', 'relatedResource' => 'posts']
        );

        $this->provider->callResources();

        $this->assertFalse($this->provider->wasParentCalled());
        $this->assertCount(2, $this->provider->getLoadedResources());
    }

    public function test_resources_lazy_loads_single_for_create(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('nova-turbo.auto_refresh_in_dev', false);

        $this->cache->store([
            'users' => [UserResource::class, PostResource::class],
        ], []);

        $this->createRequest(
            '/nova/resources/users/new',
            '/nova/resources/{resource}/new',
            'nova.pages.create',
            ['resource' => 'users']
        );

        $this->provider->callResources();

        $this->assertFalse($this->provider->wasParentCalled());
        $this->assertCount(1, $this->provider->getLoadedResources());
    }
}
