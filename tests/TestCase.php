<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * We don't load the service provider because it requires Nova.
     * For unit tests, we test the services directly.
     */
    protected function getPackageProviders($app): array
    {
        return [];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('nova-turbo.auto_refresh_in_dev', true);
        $app['config']->set('nova-turbo.resource_paths', []);
    }
}
