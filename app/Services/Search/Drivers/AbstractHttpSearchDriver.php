<?php

namespace App\Services\Search\Drivers;

use App\Services\Search\SearchEngineInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class AbstractHttpSearchDriver implements SearchEngineInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    protected function baseUrl(): string
    {
        return rtrim((string) ($this->config['base_url'] ?? ''), '/');
    }

    protected function client(): PendingRequest
    {
        $client = Http::timeout(15)->acceptJson();

        if (!empty($this->config['username']) || !empty($this->config['password'])) {
            $client = $client->withBasicAuth(
                (string) ($this->config['username'] ?? ''),
                (string) ($this->config['password'] ?? '')
            );
        }

        if (!empty($this->config['api_key'])) {
            $client = $client->withToken((string) $this->config['api_key']);
        }

        return $client;
    }

    protected function ensureConfigured(): void
    {
        if ($this->baseUrl() === '') {
            throw new RuntimeException('Search provider is not configured.');
        }
    }
}
