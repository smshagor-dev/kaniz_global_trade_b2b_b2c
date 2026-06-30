<?php

namespace App\Services\Search\Drivers;

use Illuminate\Support\Arr;
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
                'dynamic_templates' => [
                    [
                        'filters_string_template' => [
                            'path_match' => 'filters.*',
                            'match_mapping_type' => 'string',
                            'mapping' => ['type' => 'keyword'],
                        ],
                    ],
                    [
                        'metadata_string_template' => [
                            'path_match' => 'metadata.*',
                            'match_mapping_type' => 'string',
                            'mapping' => ['type' => 'keyword'],
                        ],
                    ],
                ],
                'properties' => [
                    'title' => ['type' => 'text'],
                    'summary' => ['type' => 'text'],
                    'keywords' => ['type' => 'text'],
                    'search_text' => ['type' => 'text'],
                    'type' => ['type' => 'keyword'],
                    'entity_subtype' => ['type' => 'keyword'],
                    'visibility' => ['type' => 'keyword'],
                    'is_active' => ['type' => 'boolean'],
                    'model_type' => ['type' => 'keyword'],
                    'model_id' => ['type' => 'long'],
                    'filters' => ['type' => 'object', 'dynamic' => true],
                    'metadata' => ['type' => 'object', 'dynamic' => true],
                    'rank_exact' => ['type' => 'float'],
                    'rank_popularity' => ['type' => 'float'],
                    'rank_sales' => ['type' => 'float'],
                    'rank_verified' => ['type' => 'float'],
                    'rank_featured' => ['type' => 'float'],
                    'rank_supplier_score' => ['type' => 'float'],
                    'rank_rating' => ['type' => 'float'],
                    'rank_trade_volume' => ['type' => 'float'],
                    'rank_response_rate' => ['type' => 'float'],
                    'rank_recency' => ['type' => 'float'],
                    'rank_ai_score' => ['type' => 'float'],
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
        $countResponse = $this->client()->get($this->baseUrl() . '/' . $indexName . '/_count');

        return [
            'ok' => $response->successful() && $countResponse->successful(),
            'provider' => 'opensearch',
            'index' => $indexName,
            'status' => data_get($response->json(), 'status'),
            'document_count' => (int) data_get($countResponse->json(), 'count', 0),
            'message' => $response->successful() ? null : $response->body(),
        ];
    }

    protected function buildFilters(array $payload): array
    {
        $filters = [];

        if (!empty($payload['types'])) {
            $filters[] = ['terms' => ['type' => array_values((array) $payload['types'])]];
        }

        if (!empty($payload['visibility'])) {
            $filters[] = ['terms' => ['visibility' => array_values((array) $payload['visibility'])]];
        }

        if (array_key_exists('is_active', $payload)) {
            $filters[] = ['term' => ['is_active' => (bool) $payload['is_active']]];
        }

        foreach ((array) ($payload['filters'] ?? []) as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $field = 'filters.' . $key;

            if (is_array($value) && !Arr::isAssoc($value)) {
                $filters[] = ['terms' => [$field => array_values($value)]];
                continue;
            }

            $filters[] = ['term' => [$field => $value]];
        }

        return $filters;
    }
}
