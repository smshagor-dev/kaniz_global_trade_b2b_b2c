<?php

namespace App\Jobs;

use App\Models\SearchIndexingRun;
use App\Services\Search\SearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReindexSearchChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $modelClass,
        protected array $modelIds,
        protected int $runId
    ) {
    }

    public function handle(SearchService $searchService): void
    {
        $failed = 0;

        foreach ($this->modelIds as $modelId) {
            if (!class_exists($this->modelClass)) {
                $failed++;
                continue;
            }

            $model = $this->modelClass::find($modelId);
            if (!$model) {
                $failed++;
                continue;
            }

            try {
                $searchService->indexModel($model, $this->runId);
            } catch (\Throwable $throwable) {
                $failed++;
            }
        }

        $run = SearchIndexingRun::find($this->runId);
        if (!$run) {
            return;
        }

        $run->increment('processed_chunks');
        $run->increment('processed_models', count($this->modelIds));
        if ($failed > 0) {
            $run->increment('failed_models', $failed);
        }

        $run->refresh();
        if ($run->processed_chunks >= $run->queued_chunks && in_array($run->status, ['queued', 'dispatching', 'paused'], true)) {
            $run->update([
                'status' => $run->processed_models >= $run->total_models ? 'completed' : 'paused',
                'current_model_class' => $run->processed_models >= $run->total_models ? null : $run->current_model_class,
                'last_processed_id' => $run->processed_models >= $run->total_models ? null : $run->last_processed_id,
                'finished_at' => $run->processed_models >= $run->total_models ? now() : null,
            ]);
        }
    }

    public function modelClass(): string
    {
        return $this->modelClass;
    }

    public function modelIds(): array
    {
        return $this->modelIds;
    }

    public function runId(): int
    {
        return $this->runId;
    }
}
