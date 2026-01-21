<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Tests\Unit;

use Shahabzebare\NovaTurbo\Services\MetadataCache;
use Shahabzebare\NovaTurbo\Tests\TestCase;

class MetadataCacheTest extends TestCase
{
    private MetadataCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new MetadataCache;
        $this->cache->clear(); // Start fresh
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        parent::tearDown();
    }

    public function test_exists_returns_false_when_no_cache(): void
    {
        $this->assertFalse($this->cache->exists());
    }

    public function test_store_creates_valid_php_file(): void
    {
        $relationships = ['users' => ['App\\Nova\\User']];
        $metadata = [['uriKey' => 'users', 'label' => 'Users']];

        $this->cache->store($relationships, $metadata);

        $this->assertTrue($this->cache->exists());
        $this->assertFileExists($this->cache->getPath());
    }

    public function test_get_returns_stored_data(): void
    {
        $relationships = ['users' => ['App\\Nova\\User']];
        $metadata = [['uriKey' => 'users', 'label' => 'Users']];

        $this->cache->store($relationships, $metadata);
        $data = $this->cache->get();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('relationships', $data);
        $this->assertArrayHasKey('metadata', $data);
        $this->assertArrayHasKey('generated_at', $data);
    }

    public function test_get_relationships_returns_relationships_array(): void
    {
        $relationships = [
            'users' => ['App\\Nova\\User', 'App\\Nova\\Post'],
            'posts' => ['App\\Nova\\Post'],
        ];
        $metadata = [];

        $this->cache->store($relationships, $metadata);

        $this->assertEquals($relationships, $this->cache->getRelationships());
    }

    public function test_get_resource_metadata_returns_metadata_array(): void
    {
        $relationships = [];
        $metadata = [
            ['uriKey' => 'users', 'label' => 'Users', 'searchable' => true],
            ['uriKey' => 'posts', 'label' => 'Posts', 'searchable' => false],
        ];

        $this->cache->store($relationships, $metadata);

        $this->assertEquals($metadata, $this->cache->getResourceMetadata());
    }

    public function test_clear_removes_cache_file(): void
    {
        $this->cache->store(['users' => []], []);
        $this->assertTrue($this->cache->exists());

        $this->cache->clear();

        $this->assertFalse($this->cache->exists());
    }

    public function test_get_path_returns_correct_path(): void
    {
        $expectedPath = $this->app->bootstrapPath('cache/nova-turbo.php');
        $this->assertEquals($expectedPath, $this->cache->getPath());
    }

    public function test_get_returns_null_when_cache_does_not_exist(): void
    {
        $this->assertNull($this->cache->get());
    }

    public function test_get_relationships_returns_empty_array_when_no_cache(): void
    {
        $this->assertEquals([], $this->cache->getRelationships());
    }

    public function test_get_resource_metadata_returns_empty_array_when_no_cache(): void
    {
        $this->assertEquals([], $this->cache->getResourceMetadata());
    }
}
