<?php

namespace App\Console\Commands;

use App\Services\Search\SearchModelRegistry;
use App\Services\Search\SearchService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class SearchReindexCommand extends Command
{
    protected $signature = 'search:reindex
        {legacyEntity? : Legacy fully qualified model class or entity alias}
        {--entity=all : Entity alias to reindex}
        {--id= : Reindex a single model id}
        {--chunk=100 : Chunk size for chunkById processing}
        {--queue : Dispatch chunk jobs instead of indexing synchronously}
        {--resume : Resume the latest incomplete reindex run}
        {--run= : Resume a specific reindex run id}
        {--retry-failures : Backward-compatible alias for retrying unresolved failures}
        {--dry-run : Show entity counts without indexing}';

    protected $description = 'Rebuild marketplace enterprise search documents safely in chunks.';

    public function handle(SearchService $searchService): int
    {
        $entity = $this->resolveEntity();
        $chunkSize = max((int) $this->option('chunk'), 1);
        $onlyId = $this->option('id') ? (int) $this->option('id') : null;

        try {
            if ($this->option('retry-failures')) {
                $count = $searchService->retryFailures(
                    (bool) $this->option('queue'),
                    $this->option('run') ? (int) $this->option('run') : null
                );
                $this->info('Retried ' . $count . ' failed search indexing records.');

                return self::SUCCESS;
            }

            if ($this->option('dry-run')) {
                $run = $searchService->dryRun($entity, $onlyId, $chunkSize);
                $estimate = (array) $run->summary;

                $this->info(sprintf(
                    'Dry run complete for "%s". Estimated %d records across %d model groups. Run #%d.',
                    $entity,
                    (int) ($estimate['total'] ?? 0),
                    count((array) ($estimate['models'] ?? [])),
                    $run->id
                ));

                foreach ((array) ($estimate['models'] ?? []) as $modelClass => $count) {
                    $this->line(sprintf('- %s: %d', class_basename($modelClass), $count));
                }

                return self::SUCCESS;
            }

            $run = $searchService->reindex(
                $entity,
                (bool) $this->option('queue'),
                $onlyId,
                $chunkSize,
                (bool) $this->option('resume'),
                $this->option('run') ? (int) $this->option('run') : null,
                function (array $progress): void {
                    $mode = $progress['queued'] ? 'queued' : 'indexed';
                    $chunkTotal = $progress['queued'] ? $progress['queued_chunks'] : $progress['processed_chunks'];

                    $this->line(sprintf(
                        '[Run #%d] %s chunk %d %s from %s (size=%d, last_id=%d, processed=%d, failed=%d)',
                        $progress['run_id'],
                        class_basename($progress['model_class']),
                        $chunkTotal,
                        $mode,
                        class_basename($progress['model_class']),
                        $progress['chunk_size'],
                        $progress['last_processed_id'],
                        $progress['processed_models'],
                        $progress['failed_models']
                    ));
                }
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $message = sprintf(
            'Run #%d finished with status "%s". Processed=%d, failed=%d, queued_chunks=%d.',
            $run->id,
            $run->status,
            $run->processed_models,
            $run->failed_models,
            $run->queued_chunks
        );

        if ($run->status === 'paused') {
            $this->warn($message . ' Resume with php artisan search:reindex --resume --run=' . $run->id);

            return self::SUCCESS;
        }

        $this->info($message);

        return self::SUCCESS;
    }

    protected function resolveEntity(): string
    {
        $legacyEntity = $this->argument('legacyEntity');
        $entity = (string) ($this->option('entity') ?: $legacyEntity ?: 'all');

        if (in_array($entity, SearchModelRegistry::models(), true)) {
            return $entity;
        }

        return $entity;
    }
}
