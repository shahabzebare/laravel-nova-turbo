<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Tests\Feature;

use Shahabzebare\NovaTurbo\NovaTurbo;
use Shahabzebare\NovaTurbo\NovaTurboServiceProvider;
use Shahabzebare\NovaTurbo\Services\MetadataCache;
use Shahabzebare\NovaTurbo\Tests\Fixtures\Nova\PostResource;
use Shahabzebare\NovaTurbo\Tests\Fixtures\Nova\UserResource;
use Shahabzebare\NovaTurbo\Tests\TestCase;

class TurboCacheCommandTest extends TestCase
{
    private MetadataCache $cache;

    /**
     * Load the service provider to register the command.
     */
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
        NovaTurbo::clearExternalResources();
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        NovaTurbo::clearExternalResources();
        parent::tearDown();
    }

    public function test_command_clears_cache_with_option(): void
    {
        // Create some cache first
        $this->cache->store(['test' => []], []);
        $this->assertTrue($this->cache->exists());

        $this->artisan('nova:turbo-cache', ['--clear' => true])
            ->assertSuccessful();

        $this->assertFalse($this->cache->exists());
    }

    public function test_command_fails_when_no_resources_found(): void
    {
        $this->app['config']->set('nova-turbo.resource_paths', []);

        $this->artisan('nova:turbo-cache')
            ->assertFailed();
    }

    public function test_command_generates_cache_with_resources(): void
    {
        NovaTurbo::resources([UserResource::class, PostResource::class]);

        $this->artisan('nova:turbo-cache')
            ->assertSuccessful();

        $this->assertTrue($this->cache->exists());
        $data = $this->cache->get();

        $this->assertArrayHasKey('relationships', $data);
        $this->assertArrayHasKey('metadata', $data);
        $this->assertArrayHasKey('generated_at', $data);
    }

    public function test_command_caches_resource_metadata(): void
    {
        NovaTurbo::resources([UserResource::class]);

        $this->artisan('nova:turbo-cache')
            ->assertSuccessful();

        $metadata = $this->cache->getResourceMetadata();

        $this->assertCount(1, $metadata);
        $this->assertEquals('users', $metadata[0]['uriKey']);
        $this->assertEquals('Users', $metadata[0]['label']);
    }

    public function test_command_caches_relationships(): void
    {
        NovaTurbo::resources([PostResource::class]);

        $this->artisan('nova:turbo-cache')
            ->assertSuccessful();

        $relationships = $this->cache->getRelationships();

        $this->assertArrayHasKey('posts', $relationships);
        // PostResource has HasMany to CommentResource
        $this->assertCount(2, $relationships['posts']);
    }
}
