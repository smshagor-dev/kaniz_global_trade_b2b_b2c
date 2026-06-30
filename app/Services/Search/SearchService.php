<?php

namespace App\Services\Search;

use App\Jobs\ReindexSearchChunkJob;
use App\Jobs\SyncSearchDocumentJob;
use App\Models\B2BCompany;
use App\Models\SearchAnalyticsEvent;
use App\Models\SearchDocument;
use App\Models\SearchIndexingFailure;
use App\Models\SearchIndexingRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class SearchService
{
    public const DEFAULT_CHUNK_SIZE = 100;

    public function __construct(
        protected SearchManager $manager,
        protected SearchDocumentFactory $factory
    ) {
    }

    public function search(string $query, array $options = [], ?User $user = null): array
    {
        $started = microtime(true);
        $provider = $options['provider'] ?? $this->manager->activeProvider();
        $driver = $this->manager->resilientDriver($provider);
        $actualProvider = $this->providerName($driver);
        $visibility = $this->allowedVisibility($options, $user);
        $types = $this->expandRequestedTypes((array) ($options['types'] ?? []), $user, $options);
        $filters = (array) ($options['filters'] ?? []);

        $payload = [
            'query' => $this->expandQuery($query),
            'limit' => (int) ($options['limit'] ?? config('search.default_limit', 20)),
            'types' => $types,
            'filters' => $filters,
            'visibility' => $visibility,
            'index_name' => $this->manager->indexName(),
            'is_active' => true,
        ];

        $result = $driver->search($payload);
        $hits = collect($result['hits'] ?? [])
            ->filter(fn ($hit) => $this->canViewHit($hit, $user, $options))
            ->values();

        $grouped = $hits->groupBy(fn ($hit) => $this->groupName($hit))
            ->map(fn (Collection $group) => $group->values()->all())
            ->all();

        $response = [
            'query' => $query,
            'provider' => $actualProvider,
            'total' => $hits->count(),
            'groups' => $grouped,
            'results' => $hits->all(),
        ];

        $this->recordEvent('search', $query, [
            'provider' => $actualProvider,
            'result_count' => $hits->count(),
            'response_time_ms' => (int) round((microtime(true) - $started) * 1000),
            'filters' => $filters,
            'metadata' => array_merge(['types' => $types], (array) ($options['metadata'] ?? [])),
        ], $user);

        return $response;
    }

    public function autocomplete(string $query, array $options = [], ?User $user = null): array
    {
        $provider = $options['provider'] ?? $this->manager->activeProvider();
        $driver = $this->manager->resilientDriver($provider);

        $hits = collect($driver->autocomplete([
            'query' => $this->expandQuery($query),
            'limit' => (int) ($options['limit'] ?? config('search.autocomplete_limit', 8)),
            'types' => $this->expandRequestedTypes((array) ($options['types'] ?? []), $user, $options),
            'visibility' => $this->allowedVisibility($options, $user),
            'index_name' => $this->manager->indexName(),
        ])['hits'] ?? [])
            ->filter(fn ($hit) => $this->canViewHit($hit, $user, $options))
            ->map(fn ($hit) => Arr::only($hit, ['id', 'engine_document_id', 'type', 'entity_subtype', 'title', 'subtitle', 'url']))
            ->values()
            ->all();

        return ['query' => $query, 'suggestions' => $hits];
    }

    public function indexModel(Model $model, ?int $runId = null): void
    {
        $document = $this->factory->build($model);
        $documentId = $this->documentId($model);

        if ($document === []) {
            $this->deleteModel($model);

            return;
        }

        try {
            $this->manager->driver('database')->index($this->manager->indexName(), $documentId, $document);
        } catch (Throwable $throwable) {
            $this->captureFailure('index', $model, $throwable, $document, $runId, 'database');
            throw $throwable;
        }

        $activeProvider = $this->manager->activeProvider();
        if ($activeProvider !== 'database') {
            try {
                $this->manager->driver($activeProvider)->index($this->manager->indexName(), $documentId, $document);
            } catch (Throwable $throwable) {
                $this->captureFailure('index', $model, $throwable, $document, $runId, $activeProvider);
            }
        }

        SearchIndexingFailure::query()
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->whereNull('resolved_at')
            ->where(function ($query) use ($activeProvider) {
                $query->where('provider', 'database');
                if ($activeProvider !== 'database') {
                    $query->orWhere('provider', $activeProvider);
                }
            })
            ->update(['resolved_at' => now()]);
    }

    public function deleteModel(Model $model): void
    {
        $documentId = $this->documentId($model);
        $this->manager->driver('database')->delete($this->manager->indexName(), $documentId);

        $activeProvider = $this->manager->activeProvider();
        if ($activeProvider !== 'database') {
            try {
                $this->manager->driver($activeProvider)->delete($this->manager->indexName(), $documentId);
            } catch (Throwable $throwable) {
                $this->captureFailure('delete', $model, $throwable, ['engine_document_id' => $documentId], null, $activeProvider);
            }
        }
    }

    public function estimateReindex(string $entity = 'all', ?int $onlyId = null): array
    {
        $models = SearchModelRegistry::resolve($entity);
        if ($models === []) {
            throw new InvalidArgumentException('Unknown searchable entity alias: ' . $entity);
        }

        $summary = [];
        $total = 0;

        foreach ($models as $class) {
            $count = $this->baseReindexQuery($class, $onlyId)->count();
            $summary[$class] = $count;
            $total += $count;
        }

        return [
            'entity' => $entity,
            'total' => $total,
            'models' => $summary,
        ];
    }

    public function dryRun(string $entity = 'all', ?int $onlyId = null, int $chunkSize = self::DEFAULT_CHUNK_SIZE): SearchIndexingRun
    {
        $estimate = $this->withEstimatedChunks(
            $this->estimateReindex($entity, $onlyId),
            $chunkSize
        );

        return SearchIndexingRun::create([
            'entity' => $entity,
            'provider' => $this->manager->activeProvider(),
            'chunk_size' => $chunkSize,
            'is_queue' => false,
            'is_dry_run' => true,
            'status' => 'dry_run',
            'total_models' => $estimate['total'],
            'processed_models' => 0,
            'failed_models' => 0,
            'queued_chunks' => 0,
            'processed_chunks' => 0,
            'summary' => $estimate,
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }

    public function reindex(
        string $entity = 'all',
        bool $queue = false,
        ?int $onlyId = null,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE,
        bool $resume = false,
        ?int $runId = null,
        ?callable $progressCallback = null
    ): SearchIndexingRun {
        $models = SearchModelRegistry::resolve($entity);
        if ($models === []) {
            throw new InvalidArgumentException('Unknown searchable entity alias: ' . $entity);
        }

        $estimate = $this->withEstimatedChunks(
            $this->estimateReindex($entity, $onlyId),
            $chunkSize
        );
        $run = $resume
            ? $this->resumeRun($entity, $runId, $chunkSize, $queue, $estimate)
            : $this->createRun($entity, $chunkSize, $queue, $estimate);

        $maxSyncChunks = max((int) config('search.reindex.max_sync_chunks', 25), 1);
        $maxQueueChunks = max((int) config('search.reindex.max_queue_chunks', 100), 1);
        $processedChunks = 0;
        $paused = false;
        $resumeMode = $resume && !empty($run->current_model_class);
        $resumeReached = !$resumeMode;

        foreach ($models as $class) {
            if ($resumeMode && !$resumeReached) {
                if ($class !== $run->current_model_class) {
                    continue;
                }

                $resumeReached = true;
            }

            $afterId = $resume && $class === $run->current_model_class && $run->last_processed_id
                ? (int) $run->last_processed_id
                : null;
            $keyName = (new $class())->getKeyName();

            $run->forceFill([
                'current_model_class' => $class,
                'last_processed_id' => $afterId,
            ])->save();

            $this->baseReindexQuery($class, $onlyId, $afterId)
                ->chunkById($chunkSize, function ($records) use (
                    $class,
                    $keyName,
                    $queue,
                    $run,
                    $progressCallback,
                    $maxSyncChunks,
                    $maxQueueChunks,
                    &$processedChunks,
                    &$paused
                ) {
                    $ids = $records->pluck($keyName)->map(fn ($id) => (int) $id)->values()->all();
                    $lastId = (int) end($ids);

                    $successCount = 0;
                    $failureCount = 0;

                    if ($queue) {
                        ReindexSearchChunkJob::dispatch($class, $ids, $run->id)
                            ->onConnection(config('search.queue_connection', 'database'))
                            ->onQueue(config('search.queue', 'default'));
                        $run->increment('queued_chunks');
                    } else {
                        foreach ($records as $record) {
                            try {
                                $this->indexModel($record, $run->id);
                                $successCount++;
                            } catch (Throwable $throwable) {
                                $failureCount++;
                            }
                        }

                        $run->increment('processed_models', count($ids));
                        if ($failureCount > 0) {
                            $run->increment('failed_models', $failureCount);
                        }
                        $run->increment('processed_chunks');
                    }

                    $run->forceFill([
                        'current_model_class' => $class,
                        'last_processed_id' => $lastId,
                    ])->save();

                    $processedChunks++;

                    if ($progressCallback) {
                        $currentRun = $run->fresh();
                        $progressCallback([
                            'run_id' => $run->id,
                            'model_class' => $class,
                            'chunk_size' => count($ids),
                            'last_processed_id' => $lastId,
                            'queued' => $queue,
                            'processed_chunks' => $currentRun->processed_chunks,
                            'queued_chunks' => $currentRun->queued_chunks,
                            'processed_models' => $currentRun->processed_models,
                            'failed_models' => $currentRun->failed_models,
                            'success_count' => $successCount,
                            'failure_count' => $failureCount,
                        ]);
                    }

                    $currentRun = $run->fresh();

                    if (
                        !$queue
                        && $processedChunks >= $maxSyncChunks
                        && $currentRun->processed_models < $run->total_models
                    ) {
                        $paused = true;

                        return false;
                    }

                    if (
                        $queue
                        && $processedChunks >= $maxQueueChunks
                        && $currentRun->queued_chunks < (int) data_get($currentRun->summary, 'estimated_chunks', 0)
                    ) {
                        $paused = true;

                        return false;
                    }
                }, $keyName);

            if ($paused) {
                break;
            }

            $run->forceFill([
                'current_model_class' => $class,
                'last_processed_id' => null,
            ])->save();
        }

        $run->refresh();

        if ($paused) {
            $run->update([
                'status' => 'paused',
                'summary' => $estimate,
            ]);

            return $run->fresh();
        }

        if ($queue) {
            $run->update([
                'status' => $run->queued_chunks > $run->processed_chunks ? 'queued' : 'completed',
                'finished_at' => $run->queued_chunks > $run->processed_chunks ? null : now(),
                'summary' => $estimate,
            ]);

            if ($run->queued_chunks === 0) {
                $run->forceFill([
                    'current_model_class' => null,
                    'last_processed_id' => null,
                ])->save();
            }

            return $run->fresh();
        }

        $run->update([
            'status' => 'completed',
            'current_model_class' => null,
            'last_processed_id' => null,
            'finished_at' => now(),
            'summary' => $estimate,
        ]);

        return $run->fresh();
    }

    public function retryFailures(bool $queue = true, ?int $runId = null): int
    {
        $count = 0;

        $failures = SearchIndexingFailure::query()
            ->whereNull('resolved_at')
            ->when($runId, fn ($query) => $query->where('run_id', $runId))
            ->orderBy('id')
            ->get()
            ->unique(fn (SearchIndexingFailure $failure) => $failure->model_type . ':' . $failure->model_id);

        foreach ($failures as $failure) {
            if (!class_exists($failure->model_type)) {
                continue;
            }

            if ($queue) {
                SyncSearchDocumentJob::dispatch($failure->model_type, (int) $failure->model_id, $failure->run_id ?: $runId)
                    ->onConnection(config('search.queue_connection', 'database'))
                    ->onQueue(config('search.queue', 'default'));
            } else {
                $model = $failure->model_type::find($failure->model_id);
                if ($model) {
                    try {
                        $this->indexModel($model, $failure->run_id ?: $runId);
                    } catch (Throwable $throwable) {
                        continue;
                    }
                }
            }

            $count++;
        }

        return $count;
    }

    public function latestRun(): ?SearchIndexingRun
    {
        return SearchIndexingRun::query()->latest('id')->first();
    }

    public function recordClick(string $documentId, string $query, ?User $user = null): void
    {
        $document = SearchDocument::where('engine_document_id', $documentId)->first();

        $this->recordEvent('click', $query, [
            'provider' => $this->manager->activeProvider(),
            'document_id' => optional($document)->id,
            'metadata' => [
                'engine_document_id' => $documentId,
                'type' => optional($document)->type,
            ],
        ], $user);
    }

    public function recordConversion(string $query, array $metadata = [], ?User $user = null): void
    {
        $this->recordEvent('conversion', $query, [
            'provider' => $this->manager->activeProvider(),
            'metadata' => $metadata,
        ], $user);
    }

    public function recordCustomEvent(string $type, string $query, array $attributes = [], ?User $user = null): void
    {
        $this->recordEvent($type, $query, $attributes, $user);
    }

    public function analyticsSummary(): array
    {
        $searches = SearchAnalyticsEvent::where('event_type', 'search');
        $clicks = SearchAnalyticsEvent::where('event_type', 'click');
        $conversions = SearchAnalyticsEvent::where('event_type', 'conversion');

        $popularSearches = SearchAnalyticsEvent::query()
            ->select('query', DB::raw('COUNT(*) as total'))
            ->where('event_type', 'search')
            ->groupBy('query')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $zeroResults = SearchAnalyticsEvent::query()
            ->select('query', DB::raw('COUNT(*) as total'))
            ->where('event_type', 'search')
            ->where('result_count', 0)
            ->groupBy('query')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $searchCount = (int) $searches->count();
        $clickCount = (int) $clicks->count();
        $conversionCount = (int) $conversions->count();

        return [
            'searches' => $searchCount,
            'clicks' => $clickCount,
            'conversions' => $conversionCount,
            'average_response_time_ms' => (float) SearchAnalyticsEvent::where('event_type', 'search')->avg('response_time_ms'),
            'ctr' => $searchCount > 0 ? round(($clickCount / $searchCount) * 100, 2) : 0,
            'conversion_rate' => $searchCount > 0 ? round(($conversionCount / $searchCount) * 100, 2) : 0,
            'abandonment_rate' => $searchCount > 0 ? round((max($searchCount - $clickCount, 0) / $searchCount) * 100, 2) : 0,
            'popular_searches' => $popularSearches,
            'zero_result_searches' => $zeroResults,
            'top_filters' => $this->topFilters(),
        ];
    }

    protected function topFilters(): array
    {
        $counts = [];

        SearchAnalyticsEvent::query()
            ->where('event_type', 'search')
            ->whereNotNull('filters')
            ->get()
            ->each(function (SearchAnalyticsEvent $event) use (&$counts) {
                foreach ((array) $event->filters as $key => $value) {
                    $label = $key . ':' . (is_array($value) ? implode('|', $value) : $value);
                    $counts[$label] = ($counts[$label] ?? 0) + 1;
                }
            });

        arsort($counts);

        return array_slice($counts, 0, 10, true);
    }

    protected function allowedVisibility(array $options, ?User $user): array
    {
        if (!empty($options['include_private']) && $user) {
            return ['public', 'restricted', 'private'];
        }

        if ($user) {
            return ['public', 'restricted'];
        }

        return ['public'];
    }

    protected function expandRequestedTypes(array $types, ?User $user, array $options): array
    {
        if ($types !== []) {
            return $types;
        }

        if (!empty($options['include_private']) && $user) {
            return SearchDocument::query()->distinct()->pluck('type')->all();
        }

        return (array) config('search.public_types', []);
    }

    protected function canViewHit(array $hit, ?User $user, array $options): bool
    {
        $visibility = $hit['visibility'] ?? 'public';

        if ($visibility === 'public') {
            return true;
        }

        if (!$user) {
            return false;
        }

        if (($user->user_type ?? null) === 'admin') {
            return true;
        }

        $metadata = (array) ($hit['metadata'] ?? []);

        if ($visibility === 'private') {
            return in_array((int) $user->id, [
                (int) ($metadata['buyer_user_id'] ?? 0),
                (int) ($metadata['supplier_user_id'] ?? 0),
            ], true);
        }

        $accessibleCompanyIds = $this->accessibleCompanyIds($user);

        return count(array_intersect($accessibleCompanyIds, array_filter([
            (int) ($metadata['buyer_company_id'] ?? 0),
            (int) ($metadata['supplier_company_id'] ?? 0),
            (int) ($metadata['company_id'] ?? 0),
        ]))) > 0;
    }

    protected function accessibleCompanyIds(User $user): array
    {
        return B2BCompany::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->merge(
                DB::table('b2b_company_members')
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->pluck('b2b_company_id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected function groupName(array $hit): string
    {
        $type = (string) ($hit['type'] ?? 'other');
        $subtype = (string) ($hit['entity_subtype'] ?? '');

        return match ($type) {
            'company' => $subtype !== '' ? Str::title(str_replace('_', ' ', $subtype)) : 'Companies',
            'wholesale_product' => 'Wholesale Products',
            'product' => 'Products',
            'brand' => 'Brands',
            'category' => 'Categories',
            'rfq' => 'RFQs',
            'hs_code' => 'HS Codes',
            'port' => 'Ports',
            'country' => 'Countries',
            'city' => 'Cities',
            'freight_forwarder' => 'Freight Forwarders',
            'purchase_order' => 'Purchase Orders',
            'invoice' => 'Invoices',
            'shipment' => 'Shipments',
            'container_shipment' => 'Container Shipments',
            'trade_document' => 'Trade Documents',
            default => 'Other',
        };
    }

    protected function recordEvent(string $type, string $query, array $attributes, ?User $user): void
    {
        SearchAnalyticsEvent::create([
            'event_type' => $type,
            'query' => $query,
            'provider' => $attributes['provider'] ?? $this->manager->activeProvider(),
            'document_id' => $attributes['document_id'] ?? null,
            'session_id' => session()->getId(),
            'user_id' => $user?->id,
            'result_count' => $attributes['result_count'] ?? null,
            'response_time_ms' => $attributes['response_time_ms'] ?? null,
            'filters' => $attributes['filters'] ?? [],
            'metadata' => $attributes['metadata'] ?? [],
        ]);
    }

    protected function documentId(Model $model): string
    {
        return Str::slug(str_replace('\\', '-', get_class($model))) . '-' . $model->getKey();
    }

    protected function captureFailure(
        string $operation,
        Model $model,
        Throwable $throwable,
        array $payload = [],
        ?int $runId = null,
        ?string $provider = null
    ): void {
        $provider ??= $this->manager->activeProvider();

        $existing = SearchIndexingFailure::query()
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->where('operation', $operation)
            ->where('provider', $provider)
            ->first();

        SearchIndexingFailure::updateOrCreate(
            [
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'operation' => $operation,
                'provider' => $provider,
            ],
            [
                'run_id' => $runId,
                'index_name' => $this->manager->indexName(),
                'message' => $throwable->getMessage(),
                'payload' => $payload,
                'failed_at' => now(),
                'resolved_at' => null,
                'attempts' => ((int) optional($existing)->attempts) + 1,
            ]
        );
    }

    protected function providerName($driver): string
    {
        $baseName = class_basename($driver);

        return match ($baseName) {
            'DatabaseSearchDriver' => 'database',
            default => (string) Str::of($baseName)
                ->replaceLast('Driver', '')
                ->snake(),
        };
    }

    protected function expandQuery(string $query): string
    {
        $query = trim($query);
        $variants = [$query];

        foreach ((array) config('search.synonyms', []) as $term => $synonyms) {
            if (Str::contains(Str::lower($query), Str::lower($term))) {
                $variants = array_merge($variants, (array) $synonyms);
            }
        }

        return implode(' ', array_unique(array_filter($variants)));
    }

    protected function baseReindexQuery(string $class, ?int $onlyId = null, ?int $afterId = null): Builder
    {
        $query = $class::query()
            ->when($onlyId, fn ($builder) => $builder->whereKey($onlyId))
            ->orderBy((new $class())->getKeyName());

        if ($afterId) {
            $query->where((new $class())->getQualifiedKeyName(), '>', $afterId);
        }

        return $query;
    }

    protected function createRun(string $entity, int $chunkSize, bool $queue, array $estimate): SearchIndexingRun
    {
        return SearchIndexingRun::create([
            'entity' => $entity,
            'provider' => $this->manager->activeProvider(),
            'chunk_size' => $chunkSize,
            'is_queue' => $queue,
            'is_dry_run' => false,
            'status' => $queue ? 'dispatching' : 'running',
            'total_models' => $estimate['total'],
            'processed_models' => 0,
            'failed_models' => 0,
            'queued_chunks' => 0,
            'processed_chunks' => 0,
            'summary' => $estimate,
            'started_at' => now(),
        ]);
    }

    protected function resumeRun(string $entity, ?int $runId, int $chunkSize, bool $queue, array $estimate): SearchIndexingRun
    {
        $run = SearchIndexingRun::query()
            ->when($runId, fn ($query) => $query->whereKey($runId))
            ->when(!$runId, fn ($query) => $query->where('entity', $entity))
            ->whereIn('status', ['paused', 'running', 'dispatching'])
            ->latest('id')
            ->first();

        if (!$run) {
            throw new InvalidArgumentException('No resumable search indexing run was found.');
        }

        $run->update([
            'chunk_size' => $chunkSize,
            'is_queue' => $queue,
            'status' => $queue ? 'dispatching' : 'running',
            'provider' => $this->manager->activeProvider(),
            'summary' => $estimate,
            'finished_at' => null,
        ]);

        return $run->fresh();
    }

    protected function withEstimatedChunks(array $estimate, int $chunkSize): array
    {
        $estimate['estimated_chunks'] = collect((array) ($estimate['models'] ?? []))
            ->sum(fn ($count) => (int) ceil(((int) $count) / max($chunkSize, 1)));

        return $estimate;
    }
}
