<?php

namespace App\Services\Search\Drivers;

use App\Models\SearchDocument;
use App\Services\Search\SearchEngineInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DatabaseSearchDriver implements SearchEngineInterface
{
    public function search(array $payload): array
    {
        $query = $this->normalize((string) ($payload['query'] ?? ''));
        $rawQuery = $this->normalizeOriginal((string) ($payload['query'] ?? ''));
        $limit = (int) ($payload['limit'] ?? config('search.default_limit', 20));
        $types = array_values(array_filter((array) ($payload['types'] ?? [])));

        $documents = SearchDocument::query()
            ->when($types, fn ($builder) => $builder->whereIn('type', $types))
            ->when(array_key_exists('is_active', $payload), fn ($builder) => $builder->where('is_active', (bool) $payload['is_active']), fn ($builder) => $builder->where('is_active', true))
            ->when(!empty($payload['allowed_ids']), fn ($builder) => $builder->whereIn('id', $payload['allowed_ids']))
            ->when(!empty($payload['visibility']), fn ($builder) => $builder->whereIn('visibility', (array) $payload['visibility']))
            ->get();

        $scored = $documents
            ->map(function (SearchDocument $document) use ($query, $rawQuery, $payload) {
                $score = $this->scoreDocument($document, $query, $rawQuery, $payload);

                return $score > 0 ? ['score' => $score, 'document' => $document] : null;
            })
            ->filter()
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        return [
            'hits' => $scored->map(fn ($item) => $item['document']->toArray() + ['_score' => $item['score']])->all(),
            'total' => $scored->count(),
        ];
    }

    public function autocomplete(array $payload): array
    {
        $query = $this->normalizeOriginal((string) ($payload['query'] ?? ''));
        $limit = (int) ($payload['limit'] ?? config('search.autocomplete_limit', 8));
        $filters = (array) ($payload['filters'] ?? []);

        $documents = SearchDocument::query()
            ->when(!empty($payload['types']), fn ($builder) => $builder->whereIn('type', (array) $payload['types']))
            ->when(!empty($payload['visibility']), fn ($builder) => $builder->whereIn('visibility', (array) $payload['visibility']))
            ->where('is_active', true)
            ->get();

        $hits = $documents
            ->filter(function (SearchDocument $document) use ($query, $filters) {
                $title = Str::lower((string) $document->title);
                $keywords = Str::lower((string) $document->keywords);

                if (!(Str::startsWith($title, $query) || Str::contains($keywords, $query))) {
                    return false;
                }

                foreach ($filters as $key => $value) {
                    $documentValue = data_get($document->filters ?? [], $key);

                    if (is_array($value)) {
                        if (!in_array($documentValue, $value, true)) {
                            return false;
                        }

                        continue;
                    }

                    if ((string) $documentValue !== (string) $value) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc(fn (SearchDocument $document) => $document->rank_popularity + $document->rank_sales + $document->rank_verified)
            ->take($limit)
            ->map(fn (SearchDocument $document) => $document->toArray())
            ->values()
            ->all();

        return ['hits' => $hits, 'total' => count($hits)];
    }

    public function index(string $indexName, string $documentId, array $document): void
    {
        SearchDocument::updateOrCreate(
            ['engine_document_id' => $documentId],
            array_merge($document, [
                'engine_document_id' => $documentId,
                'index_name' => $indexName,
                'last_indexed_at' => Carbon::now(),
            ])
        );
    }

    public function bulkIndex(string $indexName, array $documents): void
    {
        foreach ($documents as $documentId => $document) {
            $this->index($indexName, (string) $documentId, $document);
        }
    }

    public function delete(string $indexName, string $documentId): void
    {
        SearchDocument::where('index_name', $indexName)
            ->where('engine_document_id', $documentId)
            ->delete();
    }

    public function createIndex(string $indexName, array $schema = []): void
    {
    }

    public function deleteIndex(string $indexName): void
    {
        SearchDocument::where('index_name', $indexName)->delete();
    }

    public function health(string $indexName): array
    {
        return [
            'ok' => true,
            'provider' => 'database',
            'index' => $indexName,
            'document_count' => SearchDocument::where('index_name', $indexName)->count(),
        ];
    }

    protected function scoreDocument(SearchDocument $document, string $query, string $rawQuery, array $payload): float
    {
        $title = $this->normalizeOriginal((string) $document->title);
        $haystack = $this->normalize((string) $document->search_text . ' ' . $document->keywords . ' ' . json_encode($document->filters));
        $score = 0.0;

        if ($rawQuery !== '' && $title === $rawQuery) {
            $score += 1000;
        }

        if ($rawQuery !== '' && Str::startsWith($title, $rawQuery)) {
            $score += 400;
        }

        if ($rawQuery !== '' && Str::contains($title, $rawQuery)) {
            $score += 250;
        }

        foreach (array_filter(explode(' ', $query)) as $term) {
            if (Str::contains($haystack, $term)) {
                $score += 90;
            } elseif ($this->levenshteinContains($haystack, $term)) {
                $score += 35;
            }
        }

        $filters = (array) ($payload['filters'] ?? []);
        foreach ($filters as $key => $value) {
            $documentValue = data_get($document->filters ?? [], $key);
            if (is_array($value)) {
                if (in_array($documentValue, $value, true)) {
                    $score += 20;
                }
            } elseif ((string) $documentValue === (string) $value) {
                $score += 20;
            }
        }

        return $score
            + ($document->rank_exact * 10)
            + ($document->rank_popularity * 4)
            + ($document->rank_sales * 3)
            + ($document->rank_verified * 30)
            + ($document->rank_featured * 15)
            + ($document->rank_supplier_score * 2)
            + ($document->rank_rating * 2)
            + ($document->rank_trade_volume * 2)
            + ($document->rank_response_rate * 2)
            + ($document->rank_recency * 2)
            + ($document->rank_ai_score * 5);
    }

    protected function levenshteinContains(string $haystack, string $term): bool
    {
        foreach (array_slice(array_filter(explode(' ', $haystack)), 0, 50) as $word) {
            if (abs(strlen($word) - strlen($term)) > 2) {
                continue;
            }

            if (levenshtein($word, $term) <= 1) {
                return true;
            }
        }

        return false;
    }

    protected function normalize(string $value): string
    {
        return preg_replace('/\s+/', ' ', Str::lower(Str::ascii($value ?? ''))) ?? '';
    }

    protected function normalizeOriginal(string $value): string
    {
        return preg_replace('/\s+/', ' ', Str::lower($value)) ?? '';
    }
}
