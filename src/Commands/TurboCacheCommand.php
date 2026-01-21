<?php

namespace Shahabzebare\NovaTurbo\Commands;

use Illuminate\Console\Command;
use Laravel\Nova\Events\ServingNova;
use Shahabzebare\NovaTurbo\Services\MetadataCache;
use Shahabzebare\NovaTurbo\Services\RelationshipMapper;
use Shahabzebare\NovaTurbo\Services\ResourceScanner;

class TurboCacheCommand extends Command
{
    protected $signature = 'nova:turbo-cache {--clear : Clear the cache only}';

    protected $description = 'Cache Nova resource relationships and metadata for turbo mode';

    public function handle(
        ResourceScanner $scanner,
        RelationshipMapper $mapper,
        MetadataCache $cache
    ): int {
        if ($this->option('clear')) {
            $cache->clear();
            $this->components->info('Nova turbo cache cleared.');

            return Command::SUCCESS;
        }

        // Dispatch ServingNova event for proper Nova initialization
        ServingNova::dispatch(app(), request());

        $this->components->info('Scanning Nova resources...');
        $resources = $scanner->scan();

        if ($resources->isEmpty()) {
            $this->components->warn('No Nova resources found. Check your resource_paths config.');

            return Command::FAILURE;
        }

        $this->components->info('Mapping resource relationships...');
        $relationships = $mapper->map($resources);

        $this->components->info('Generating frontend metadata...');
        $metadata = $this->generateMetadata($resources);

        $this->components->info('Writing cache...');
        $cache->store($relationships, $metadata);

        $this->components->info("Cached {$resources->count()} resources.");
        $this->components->bulletList([
            "Cache file: {$cache->getPath()}",
        ]);

        return Command::SUCCESS;
    }

    /**
     * Generate metadata for frontend JavaScript.
     */
    protected function generateMetadata($resources): array
    {
        return $resources->map(function ($class) {
            return [
                'uriKey' => $class::uriKey(),
                // Cast to string to handle PendingTranslation objects
                'label' => (string) $class::label(),
                'singularLabel' => (string) $class::singularLabel(),
                'createButtonLabel' => (string) $class::createButtonLabel(),
                'updateButtonLabel' => (string) $class::updateButtonLabel(),
                'authorizedToCreate' => true, // Runtime check
                'searchable' => $class::searchable(),
                'tableStyle' => $class::tableStyle(),
                'showColumnBorders' => $class::showColumnBorders(),
                'debounce' => $class::$debounce * 1000,
                'clickAction' => $class::clickAction(),
                'perPageOptions' => $class::perPageOptions(),
            ];
        })->values()->all();
    }
}
