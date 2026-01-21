<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Tests\Unit;

use Shahabzebare\NovaTurbo\NovaTurboServiceProvider;
use Shahabzebare\NovaTurbo\Services\MetadataCache;
use Shahabzebare\NovaTurbo\Services\RelationshipMapper;
use Shahabzebare\NovaTurbo\Services\ResourceScanner;
use Shahabzebare\NovaTurbo\Tests\TestCase;

class NovaTurboServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            NovaTurboServiceProvider::class,
        ];
    }

    public function test_service_provider_registers_services(): void
    {
        $this->assertTrue($this->app->bound(MetadataCache::class));
        $this->assertTrue($this->app->bound(ResourceScanner::class));
        $this->assertTrue($this->app->bound(RelationshipMapper::class));
    }

    public function test_services_are_singletons(): void
    {
        $cache1 = $this->app->make(MetadataCache::class);
        $cache2 = $this->app->make(MetadataCache::class);

        $this->assertSame($cache1, $cache2);
    }

    public function test_config_is_merged(): void
    {
        $this->assertNotNull(config('nova-turbo'));
        $this->assertArrayHasKey('auto_refresh_in_dev', config('nova-turbo'));
        $this->assertArrayHasKey('resource_paths', config('nova-turbo'));
    }

    public function test_command_is_registered(): void
    {
        $commands = $this->app['Illuminate\Contracts\Console\Kernel']->all();

        $this->assertArrayHasKey('nova:turbo-cache', $commands);
    }
}
