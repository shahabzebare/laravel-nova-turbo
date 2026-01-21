<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shahabzebare\NovaTurbo\NovaTurbo;

class NovaTurboTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        NovaTurbo::clearExternalResources();
    }

    protected function tearDown(): void
    {
        NovaTurbo::clearExternalResources();
        parent::tearDown();
    }

    public function test_resources_registers_external_resources(): void
    {
        NovaTurbo::resources(['App\\Nova\\Order', 'App\\Nova\\Customer']);

        $resources = NovaTurbo::getExternalResources();

        $this->assertCount(2, $resources);
        $this->assertContains('App\\Nova\\Order', $resources);
        $this->assertContains('App\\Nova\\Customer', $resources);
    }

    public function test_get_external_resources_returns_registered(): void
    {
        $this->assertEquals([], NovaTurbo::getExternalResources());

        NovaTurbo::resources(['App\\Nova\\User']);

        $this->assertEquals(['App\\Nova\\User'], NovaTurbo::getExternalResources());
    }

    public function test_clear_external_resources_empties_array(): void
    {
        NovaTurbo::resources(['App\\Nova\\User', 'App\\Nova\\Post']);
        $this->assertCount(2, NovaTurbo::getExternalResources());

        NovaTurbo::clearExternalResources();

        $this->assertCount(0, NovaTurbo::getExternalResources());
    }

    public function test_resources_merges_and_deduplicates(): void
    {
        NovaTurbo::resources(['App\\Nova\\User', 'App\\Nova\\Post']);
        NovaTurbo::resources(['App\\Nova\\Post', 'App\\Nova\\Order']);

        $resources = NovaTurbo::getExternalResources();

        $this->assertCount(3, $resources);
        $this->assertContains('App\\Nova\\User', $resources);
        $this->assertContains('App\\Nova\\Post', $resources);
        $this->assertContains('App\\Nova\\Order', $resources);
    }

    public function test_resources_handles_empty_array(): void
    {
        NovaTurbo::resources([]);

        $this->assertEquals([], NovaTurbo::getExternalResources());
    }

    public function test_multiple_registrations_maintain_order(): void
    {
        NovaTurbo::resources(['App\\Nova\\First']);
        NovaTurbo::resources(['App\\Nova\\Second']);
        NovaTurbo::resources(['App\\Nova\\Third']);

        $resources = NovaTurbo::getExternalResources();

        $this->assertEquals('App\\Nova\\First', $resources[0]);
        $this->assertEquals('App\\Nova\\Second', $resources[1]);
        $this->assertEquals('App\\Nova\\Third', $resources[2]);
    }
}
