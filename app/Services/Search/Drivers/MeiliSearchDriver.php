<?php

namespace App\Services\Search\Drivers;

use RuntimeException;

class MeiliSearchDriver extends AbstractHttpSearchDriver
{
    public function search(array $payload): array
    {
        $this->ensureConfigured();

        $response = $this->client()->post(
            $this->baseUrl() . '/indexes/' . $payload['index_name'] . '/search',
            [
                'q' => (string) ($payload['query'] ?? ''),
                'limit' => (int) ($payload['limit'] ?? 20),
                'filter' => $this->buildFilters($payload),
            ]
        )->throw();

        $body = $response->json();

        return [
            'hits' => data_get($body, 'hits', []),
            'total' => (int) (data_get($body, 'estimatedTotalHits') ?? count(data_get($body, 'hits', []))),
        ];
    }

    public function autocomplete(array $payload): array
    {
        return $this->search(array_merge($payload, ['limit' => $payload['limit'] ?? 8]));
    }

    public function index(string $indexName, string $documentId, array $document): void
    {
        $this->ensureConfigured();
        $this->client()->put($this->baseUrl() . '/indexes/' . $indexName . '/documents', [
            array_merge($document, ['engine_document_id' => $documentId]),
        ])->throw();
    }

    public function bulkIndex(string $indexName, array $documents): void
    {
        $this->ensureConfigured();
        $payload = [];

        foreach ($documents as $documentId => $document) {
            $payload[] = array_merge($document, ['engine_document_id' => $documentId]);
        }

        $this->client()->put($this->baseUrl() . '/indexes/' . $indexName . '/documents', $payload)->throw();
    }

    public function delete(string $indexName, string $documentId): void
    {
        $this->ensureConfigured();
        $this->client()->delete($this->baseUrl() . '/indexes/' . $indexName . '/documents/' . $documentId);
    }

    public function createIndex(string $indexName, array $schema = []): void
    {
        $this->ensureConfigured();
        $this->client()->post($this->baseUrl() . '/indexes', [
            'uid' => $indexName,
            'primaryKey' => 'engine_document_id',
        ]);
    }

    public function deleteIndex(string $indexName): void
    {
        $this->ensureConfigured();
        $this->client()->delete($this->baseUrl() . '/indexes/' . $indexName);
    }

    public function health(string $indexName): array
    {
        if ($this->baseUrl() === '') {
            return ['ok' => false, 'provider' => 'meilisearch', 'message' => 'Provider is not configured.'];
        }

        $response = $this->client()->get($this->baseUrl() . '/health');

        return [
            'ok' => $response->successful() && data_get($response->json(), 'status') === 'available',
            'provider' => 'meilisearch',
            'index' => $indexName,
            'status' => data_get($response->json(), 'status'),
        ];
    }

    protected function buildFilters(array $payload): array
    {
        $filters = [];

        if (!empty($payload['types'])) {
            $filters[] = 'type IN [' . collect((array) $payload['types'])->map(fn ($type) => '"' . $type . '"')->implode(', ') . ']';
        }

        return $filters;
    }
}
