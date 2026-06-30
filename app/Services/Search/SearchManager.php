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
        $config = $this->providerConfig($provider);

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

    public function providerConfig(string $provider): array
    {
        $config = (array) config('search.providers.' . $provider, []);

        $overrides = [
            'base_url' => get_setting('search_' . $provider . '_base_url', ''),
            'username' => get_setting('search_' . $provider . '_username', ''),
            'password' => get_setting('search_' . $provider . '_password', ''),
            'api_key' => get_setting('search_' . $provider . '_api_key', ''),
        ];

        foreach ($overrides as $key => $value) {
            if ($value !== null && $value !== '') {
                $config[$key] = $value;
            }
        }

        return $config;
    }

    public function healthReport(?string $provider = null): array
    {
        $provider ??= $this->activeProvider();
        $indexName = $this->indexName();

        try {
            $primary = $this->driver($provider)->health($indexName);
        } catch (Throwable $throwable) {
            $primary = [
                'ok' => false,
                'provider' => $provider,
                'index' => $indexName,
                'message' => $throwable->getMessage(),
            ];
        }

        $fallbackProvider = $this->fallbackProvider();
        $usingFallback = !($primary['ok'] ?? false) && $provider !== $fallbackProvider;

        return [
            'provider' => $provider,
            'index' => $indexName,
            'primary' => $primary,
            'fallback_provider' => $fallbackProvider,
            'using_fallback' => $usingFallback,
            'fallback' => $usingFallback ? $this->driver($fallbackProvider)->health($indexName) : null,
        ];
    }

    public function createIndex(?string $provider = null): void
    {
        $provider ??= $this->activeProvider();
        $this->driver($provider)->createIndex($this->indexName());
    }

    public function deleteIndex(?string $provider = null): void
    {
        $provider ??= $this->activeProvider();
        $this->driver($provider)->deleteIndex($this->indexName());
    }

    public function indexName(): string
    {
        return (string) get_setting('search_index_name', config('search.index_name', 'marketplace'));
    }
}
