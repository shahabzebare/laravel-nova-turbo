<?php

declare(strict_types=1);

namespace Shahabzebare\NovaTurbo\Services;

/**
 * Handles caching of Nova resource relationships and metadata.
 *
 * Uses PHP array file pattern (like Laravel's config:cache) for
 * optimal performance with opcache.
 */
class MetadataCache
{
    /**
     * Cache version - increment this when cache structure changes.
     * This ensures stale caches are invalidated after package updates.
     */
    public const CACHE_VERSION = 1;

    protected string $cachePath;

    public function __construct()
    {
        $this->cachePath = app()->bootstrapPath('cache/nova-turbo.php');
    }

    /**
     * Check if the cache file exists.
     */
    public function exists(): bool
    {
        return file_exists($this->cachePath);
    }

    /**
     * Check if the cache is valid (exists and version matches).
     */
    public function isValid(): bool
    {
        if (! $this->exists()) {
            return false;
        }

        $data = require $this->cachePath;

        return isset($data['version']) && $data['version'] === self::CACHE_VERSION;
    }

    /**
     * Get all cached data.
     *
     * @return array{version: int, relationships: array, metadata: array, generated_at: string}|null
     */
    public function get(): ?array
    {
        if (! $this->isValid()) {
            return null;
        }

        return require $this->cachePath;
    }

    /**
     * Get the relationships map.
     *
     * @return array<string, array<int, class-string>>
     */
    public function getRelationships(): array
    {
        return $this->get()['relationships'] ?? [];
    }

    /**
     * Get the resource metadata for frontend.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getResourceMetadata(): array
    {
        return $this->get()['metadata'] ?? [];
    }

    /**
     * Store cache data as a PHP array file.
     *
     * @param  array<string, array<int, class-string>>  $relationships
     * @param  array<int, array<string, mixed>>  $metadata
     */
    public function store(array $relationships, array $metadata): void
    {
        $data = [
            'version' => self::CACHE_VERSION,
            'relationships' => $relationships,
            'metadata' => $metadata,
            'generated_at' => now()->toIso8601String(),
        ];

        $content = '<?php return '.var_export($data, true).';'.PHP_EOL;

        file_put_contents($this->cachePath, $content);

        // Invalidate opcache for this file
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($this->cachePath, true);
        }
    }

    /**
     * Clear the cache file.
     */
    public function clear(): void
    {
        if ($this->exists()) {
            @unlink($this->cachePath);

            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($this->cachePath, true);
            }
        }
    }

    /**
     * Get the cache file path.
     */
    public function getPath(): string
    {
        return $this->cachePath;
    }
}
