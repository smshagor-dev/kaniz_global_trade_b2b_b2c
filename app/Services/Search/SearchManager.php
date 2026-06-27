<?php

namespace App\Services\Search;

use App\Services\Search\Drivers\DatabaseSearchDriver;
use App\Services\Search\Drivers\ElasticSearchDriver;
use App\Services\Search\Drivers\MeiliSearchDriver;
use App\Services\Search\Drivers\OpenSearchDriver;
use InvalidArgumentException;
use Throwable;

class SearchManager
{
    public function driver(?string $provider = null): SearchEngineInterface
    {
        $provider ??= $this->activeProvider();
        $config = (array) config('search.providers.' . $provider, []);

        return match ($provider) {
            'database' => new DatabaseSearchDriver(),
            'opensearch' => new OpenSearchDriver($config),
            'elasticsearch' => new ElasticSearchDriver($config),
            'meilisearch' => new MeiliSearchDriver($config),
            default => throw new InvalidArgumentException('Unsupported search provider: ' . $provider),
        };
    }

    public function activeProvider(): string
    {
        return (string) get_setting('search_provider', config('search.provider', 'database'));
    }

    public function fallbackProvider(): string
    {
        return (string) config('search.fallback_provider', 'database');
    }

    public function resilientDriver(?string $provider = null): SearchEngineInterface
    {
        $provider ??= $this->activeProvider();

        try {
            $driver = $this->driver($provider);
            $health = $driver->health($this->indexName());

            if (!($health['ok'] ?? false) && $provider !== $this->fallbackProvider()) {
                return $this->driver($this->fallbackProvider());
            }

            return $driver;
        } catch (Throwable $throwable) {
            if ($provider === $this->fallbackProvider()) {
                throw $throwable;
            }

            return $this->driver($this->fallbackProvider());
        }
    }

    public function indexName(): string
    {
        return (string) get_setting('search_index_name', config('search.index_name', 'marketplace'));
    }
}
