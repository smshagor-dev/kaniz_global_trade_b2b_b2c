<?php

namespace App\Jobs;

use App\Services\Search\SearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncSearchDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $modelClass,
        protected int $modelId,
        protected ?int $runId = null
    )
    {
    }

    public function handle(SearchService $searchService): void
    {
        if (!class_exists($this->modelClass)) {
            return;
        }

        $model = $this->modelClass::find($this->modelId);
        if (!$model) {
            return;
        }

        $searchService->indexModel($model, $this->runId);
    }

    public function modelClass(): string
    {
        return $this->modelClass;
    }

    public function modelId(): int
    {
        return $this->modelId;
    }

    public function runId(): ?int
    {
        return $this->runId;
    }
}
