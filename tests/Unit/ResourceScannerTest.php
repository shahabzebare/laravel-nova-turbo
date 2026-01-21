<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Tests\Unit;

use Shahabzebare\NovaTurbo\NovaTurbo;
use Shahabzebare\NovaTurbo\Services\ResourceScanner;
use Shahabzebare\NovaTurbo\Tests\Fixtures\Nova\UserResource;
use Shahabzebare\NovaTurbo\Tests\TestCase;

class ResourceScannerTest extends TestCase
{
    private ResourceScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanner = new ResourceScanner;
        NovaTurbo::clearExternalResources();
    }

    protected function tearDown(): void
    {
        NovaTurbo::clearExternalResources();
        parent::tearDown();
    }

    public function test_scan_returns_collection(): void
    {
        $resources = $this->scanner->scan();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resources);
    }

    public function test_scan_returns_empty_when_no_paths_configured(): void
    {
        $this->app['config']->set('nova-turbo.resource_paths', []);

        $resources = $this->scanner->scan();

        $this->assertCount(0, $resources);
    }

    public function test_scan_includes_external_resources(): void
    {
        NovaTurbo::resources([UserResource::class]);

        $resources = $this->scanner->scan();

        $this->assertTrue($resources->contains(UserResource::class));
        $this->assertArrayHasKey('users', $resources->toArray());
    }

    public function test_external_resources_use_uri_key_method(): void
    {
        NovaTurbo::resources([UserResource::class]);

        $resources = $this->scanner->scan();

        // Should use the uriKey() method, not a calculated one
        $this->assertEquals(UserResource::class, $resources['users']);
    }

    public function test_scan_handles_invalid_path_gracefully(): void
    {
        $this->app['config']->set('nova-turbo.resource_paths', [
            '/non/existent/path',
        ]);

        $resources = $this->scanner->scan();

        $this->assertCount(0, $resources);
    }
}
