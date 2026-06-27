<?php

namespace App\Services\Search\Drivers;

use RuntimeException;

class OpenSearchDriver extends AbstractHttpSearchDriver
{
    public function search(array $payload): array
    {
        $this->ensureConfigured();

        $response = $this->client()->post($this->baseUrl() . '/' . $payload['index_name'] . '/_search', [
            'size' => (int) ($payload['limit'] ?? 20),
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'multi_match' => [
                                'query' => (string) ($payload['query'] ?? ''),
                                'fields' => ['title^5', 'keywords^4', 'summary^2', 'search_text'],
                                'fuzziness' => 'AUTO',
                            ],
                        ],
                    ],
                    'filter' => $this->buildFilters($payload),
                ],
            ],
            'sort' => [
                ['_score' => 'desc'],
                ['rank_popularity' => 'desc'],
                ['rank_sales' => 'desc'],
                ['rank_recency' => 'desc'],
            ],
        ])->throw();

        $body = $response->json();

        return [
            'hits' => collect(data_get($body, 'hits.hits', []))
                ->map(fn ($hit) => $hit['_source'] + ['_score' => $hit['_score'] ?? 0])
                ->all(),
            'total' => (int) data_get($body, 'hits.total.value', 0),
        ];
    }

    public function autocomplete(array $payload): array
    {
        return $this->search(array_merge($payload, ['limit' => $payload['limit'] ?? 8]));
    }

    public function index(string $indexName, string $documentId, array $document): void
    {
        $this->ensureConfigured();
        $this->client()->put($this->baseUrl() . '/' . $indexName . '/_doc/' . $documentId, $document)->throw();
    }

    public function bulkIndex(string $indexName, array $documents): void
    {
        $this->ensureConfigured();
        $body = '';

        foreach ($documents as $documentId => $document) {
            $body .= json_encode(['index' => ['_index' => $indexName, '_id' => $documentId]]) . "\n";
            $body .= json_encode($document) . "\n";
        }

        $response = $this->client()
            ->withBody($body, 'application/x-ndjson')
            ->post($this->baseUrl() . '/_bulk');

        if ($response->failed() || data_get($response->json(), 'errors')) {
            throw new RuntimeException('OpenSearch bulk indexing failed.');
        }
    }

    public function delete(string $indexName, string $documentId): void
    {
        $this->ensureConfigured();
        $this->client()->delete($this->baseUrl() . '/' . $indexName . '/_doc/' . $documentId);
    }

    public function createIndex(string $indexName, array $schema = []): void
    {
        $this->ensureConfigured();
        $this->client()->put($this->baseUrl() . '/' . $indexName, [
            'settings' => [
                'analysis' => [
                    'analyzer' => [
                        'default' => [
                            'type' => 'standard',
                        ],
                    ],
                ],
            ],
            'mappings' => [
                'properties' => [
                    'title' => ['type' => 'text'],
                    'summary' => ['type' => 'text'],
                    'keywords' => ['type' => 'text'],
                    'search_text' => ['type' => 'text'],
                    'type' => ['type' => 'keyword'],
                    'visibility' => ['type' => 'keyword'],
                    'filters' => ['type' => 'object', 'enabled' => true],
                    'metadata' => ['type' => 'object', 'enabled' => true],
                ],
            ],
        ]);
    }

    public function deleteIndex(string $indexName): void
    {
        $this->ensureConfigured();
        $this->client()->delete($this->baseUrl() . '/' . $indexName);
    }

    public function health(string $indexName): array
    {
        if ($this->baseUrl() === '') {
            return ['ok' => false, 'provider' => 'opensearch', 'message' => 'Provider is not configured.'];
        }

        $response = $this->client()->get($this->baseUrl() . '/_cluster/health/' . $indexName);

        return [
            'ok' => $response->successful(),
            'provider' => 'opensearch',
            'index' => $indexName,
            'status' => data_get($response->json(), 'status'),
            'document_count' => data_get($response->json(), 'number_of_data_nodes'),
        ];
    }

    protected function buildFilters(array $payload): array
    {
        $filters = [];

        foreach ((array) ($payload['types'] ?? []) as $type) {
            $filters[] = ['term' => ['type' => $type]];
        }

        return $filters;
    }
}
